<?php
declare(strict_types=1);

class OcupacionalModel
{
    /** Os exames da NR-7, na ordem em que acontecem na vida do vínculo. */
    public const TIPOS = [
        'admissional'  => 'Admissional',
        'periodico'    => 'Periódico',
        'retorno'      => 'Retorno ao trabalho',
        'mudanca_risco' => 'Mudança de risco',
        'demissional'  => 'Demissional',
    ];

    /** A conclusão é do médico do trabalho; a enfermagem entrega a triagem. */
    public const CONCLUSOES = [
        'pendente'       => 'Aguardando médico',
        'apto'           => 'Apto',
        'apto_restricao' => 'Apto com restrição',
        'inapto'         => 'Inapto',
    ];

    /** Como o atestado chegou ao setor. */
    public const FORMAS = [
        'presencial' => 'Presencial',
        'sistema'    => 'Sistema institucional',
        'email'      => 'E-mail institucional',
        'outro'      => 'Outro',
    ];

    /**
     * O cabeçalho do comprovante de entrega.
     *
     * Fica aqui, e não no banco, porque é a identificação da casa e não muda
     * de uma entrega para outra. Preencha o CNPJ uma vez: em branco, o papel
     * sai com linha para completar à mão.
     */
    public const HOSPITAL = 'Hospital Santo Expedito';
    public const HOSPITAL_CNPJ = '';

    /** Os riscos do PGR que a ficha marca. */
    public const RISCOS = [
        'risco_ruido'       => 'Ruído',
        'risco_quimico'     => 'Químico',
        'risco_biologico'   => 'Biológico',
        'risco_ergonomico'  => 'Ergonômico',
        'risco_psicossocial' => 'Psicossocial',
    ];

    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = getPDO();
    }

    // ── Prontuários ──────────────────────────────────────────────────────

    /**
     * O WHERE dos filtros, montado uma vez e reaproveitado pela listagem e
     * pela contagem — assim o total do rodapé nunca discorda da tela.
     */
    private function onde(array $f, array &$params): string
    {
        $w = [];

        if (($f['busca'] ?? '') !== '') {
            $like = '%' . like_escape($f['busca']) . '%';
            // O CPF é guardado só com dígitos: quem digita "123.456" na busca
            // não acharia nada sem esta segunda forma do termo.
            $digitos = preg_replace('/\D/', '', $f['busca']) ?? '';
            $likeCpf = $digitos !== '' ? '%' . like_escape($digitos) . '%' : null;

            $w[] = "(t.nome LIKE ? ESCAPE '!' OR t.funcao LIKE ? ESCAPE '!'
                     OR t.setor LIKE ? ESCAPE '!'"
                 . ($likeCpf !== null ? " OR t.cpf LIKE ? ESCAPE '!'" : '') . ')';

            array_push($params, $like, $like, $like);
            if ($likeCpf !== null) {
                $params[] = $likeCpf;
            }
        }

        if (!empty($f['empresa_id'])) {
            $w[] = 't.empresa_id = ?';
            $params[] = (int) $f['empresa_id'];
        }

        if (($f['tipo'] ?? '') !== '' && isset(self::TIPOS[$f['tipo']])) {
            $w[] = 'p.tipo = ?';
            $params[] = $f['tipo'];
        }

        if (($f['conclusao'] ?? '') !== '' && isset(self::CONCLUSOES[$f['conclusao']])) {
            $w[] = 'p.conclusao = ?';
            $params[] = $f['conclusao'];
        }

        if (!empty($f['de'])) {
            $w[] = 'p.data_exame >= ?';
            $params[] = $f['de'];
        }

        if (!empty($f['ate'])) {
            $w[] = 'p.data_exame <= ?';
            $params[] = $f['ate'];
        }

        // "Só o que está vencendo": validade já vencida ou a vencer em 30 dias.
        // É a pergunta que o PCMSO faz o ano inteiro — quem precisa refazer.
        if (!empty($f['vencendo'])) {
            $w[] = 'p.validade IS NOT NULL AND p.validade <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)';
        }

        if (!empty($f['pendentes'])) {
            $w[] = "p.conclusao = 'pendente'";
        }

        return $w ? ' WHERE ' . implode(' AND ', $w) : '';
    }

    public function listar(array $f, int $limite, int $offset): array
    {
        $params = [];
        $sql = 'SELECT p.*, t.nome AS trabalhador, t.cpf, t.funcao, t.setor,
                       e.nome AS empresa, e.id AS empresa_id
                  FROM so_prontuarios p
                  JOIN so_trabalhadores t ON t.id = p.trabalhador_id
                  JOIN so_empresas e      ON e.id = t.empresa_id'
             . $this->onde($f, $params)
             . ' ORDER BY p.data_exame DESC, p.id DESC
                 LIMIT ? OFFSET ?';

        $stmt = $this->pdo->prepare($sql);

        // Tudo posicional: o PDO recusa misturar `?` com `:nome` na mesma
        // instrução, e os filtros já entram como `?`.
        $i = 1;
        foreach ($params as $v) {
            $stmt->bindValue($i++, $v);
        }
        $stmt->bindValue($i++, $limite, PDO::PARAM_INT);
        $stmt->bindValue($i,   $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function contar(array $f): int
    {
        $params = [];
        $sql = 'SELECT COUNT(*)
                  FROM so_prontuarios p
                  JOIN so_trabalhadores t ON t.id = p.trabalhador_id
                  JOIN so_empresas e      ON e.id = t.empresa_id'
             . $this->onde($f, $params);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /** Os números do topo da lista. */
    public function panorama(): array
    {
        return (array) $this->pdo->query(
            "SELECT
                COUNT(*)                                             AS fichas,
                SUM(conclusao = 'pendente')                          AS pendentes,
                SUM(validade IS NOT NULL AND validade < CURDATE())   AS vencidas,
                SUM(validade IS NOT NULL AND validade >= CURDATE()
                    AND validade <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)) AS a_vencer
               FROM so_prontuarios"
        )->fetch();
    }

    public function buscarPorId(int $id): array|false
    {
        $stmt = $this->pdo->prepare(
            'SELECT p.*, t.nome AS trabalhador, t.cpf, t.nascimento, t.funcao,
                    t.setor, t.admissao, t.empresa_id, e.nome AS empresa
               FROM so_prontuarios p
               JOIN so_trabalhadores t ON t.id = p.trabalhador_id
               JOIN so_empresas e      ON e.id = t.empresa_id
              WHERE p.id = ?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /** As fichas de um trabalhador, da mais recente para a mais antiga. */
    public function historico(int $trabalhadorId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM so_prontuarios
              WHERE trabalhador_id = ?
              ORDER BY data_exame DESC, id DESC'
        );
        $stmt->execute([$trabalhadorId]);
        return $stmt->fetchAll();
    }

    public function criar(array $d): int
    {
        $valores = $this->valores($d);
        $colunas = implode(', ', array_keys($valores));
        $marcas  = implode(', ', array_fill(0, count($valores), '?'));

        $stmt = $this->pdo->prepare(
            "INSERT INTO so_prontuarios ({$colunas}) VALUES ({$marcas})"
        );
        $stmt->execute(array_values($valores));

        return (int) $this->pdo->lastInsertId();
    }

    public function atualizar(int $id, array $d): bool
    {
        $valores = $this->valores($d);
        $campos  = implode(' = ?, ', array_keys($valores)) . ' = ?';

        $stmt = $this->pdo->prepare("UPDATE so_prontuarios SET {$campos} WHERE id = ?");
        return $stmt->execute([...array_values($valores), $id]);
    }

    /**
     * Os campos gravados, com nome, num lugar só.
     *
     * Mapa em vez de lista posicional: são quase trinta colunas, e uma
     * lista nessa altura erra sozinha na primeira vez que alguém inserir
     * um campo no meio.
     */
    private function valores(array $d): array
    {
        return [
            'trabalhador_id'   => $d['trabalhador_id'],
            'tipo'             => $d['tipo'],
            'data_exame'       => $d['data_exame'],

            'hist_profissional' => $d['hist_profissional'] !== '' ? $d['hist_profissional'] : null,
            'risco_ruido'       => $d['risco_ruido'],
            'risco_quimico'     => $d['risco_quimico'],
            'risco_biologico'   => $d['risco_biologico'],
            'risco_ergonomico'  => $d['risco_ergonomico'],
            'risco_psicossocial' => $d['risco_psicossocial'],
            'epi'               => $d['epi'],
            'antecedentes'      => $d['antecedentes'] !== '' ? $d['antecedentes'] : null,

            'pa_sistolica'  => $d['pa_sistolica']  ?: null,
            'pa_diastolica' => $d['pa_diastolica'] ?: null,
            'fc'            => $d['fc']     ?: null,
            'peso'          => $d['peso']   ?: null,
            'altura'        => $d['altura'] ?: null,
            'acuidade_od'   => $d['acuidade_od'] !== '' ? $d['acuidade_od'] : null,
            'acuidade_oe'   => $d['acuidade_oe'] !== '' ? $d['acuidade_oe'] : null,
            'queixas'       => $d['queixas'] !== '' ? $d['queixas'] : null,

            'audiometria_em'  => $d['audiometria_em']  ?: null,
            'espirometria_em' => $d['espirometria_em'] ?: null,
            'laboratorial_em' => $d['laboratorial_em'] ?: null,
            'outros_exames'   => $d['outros_exames']  !== '' ? $d['outros_exames'] : null,
            'obs_enfermagem'  => $d['obs_enfermagem'] !== '' ? $d['obs_enfermagem'] : null,

            'encaminhado_em' => $d['encaminhado_em'] ?: null,
            'conclusao'      => $d['conclusao'],
            // A restrição só existe quando a conclusão é "apto com restrição".
            // Guardá-la fora disso deixaria no papel uma restrição que o
            // médico não impôs.
            'restricoes'     => $d['conclusao'] === 'apto_restricao' && $d['restricoes'] !== ''
                                ? $d['restricoes'] : null,
            'validade'       => $d['validade'] ?: null,

            'enfermeiro_nome'  => $d['enfermeiro_nome']  !== '' ? $d['enfermeiro_nome'] : null,
            'enfermeiro_coren' => $d['enfermeiro_coren'] !== '' ? $d['enfermeiro_coren'] : null,
            'medico_nome'      => $d['medico_nome'] !== '' ? $d['medico_nome'] : null,
            'medico_crm'       => $d['medico_crm']  !== '' ? $d['medico_crm']  : null,
            'ciencia_em'       => $d['ciencia_em'] ?: null,

            'usuario_id'   => Auth::id(),
            'usuario_nome' => Auth::nome(),
        ];
    }

    public function excluir(int $id): bool
    {
        return $this->pdo->prepare('DELETE FROM so_prontuarios WHERE id = ?')->execute([$id]);
    }

    // ── Trabalhadores ────────────────────────────────────────────────────

    public function trabalhadores(array $f = []): array
    {
        $w = [];
        $params = [];

        if (($f['busca'] ?? '') !== '') {
            $like = '%' . like_escape($f['busca']) . '%';
            $digitos = preg_replace('/\D/', '', $f['busca']) ?? '';

            $w[] = "(t.nome LIKE ? ESCAPE '!' OR t.funcao LIKE ? ESCAPE '!'
                     OR t.setor LIKE ? ESCAPE '!'"
                 . ($digitos !== '' ? " OR t.cpf LIKE ? ESCAPE '!'" : '') . ')';
            array_push($params, $like, $like, $like);
            if ($digitos !== '') {
                $params[] = '%' . like_escape($digitos) . '%';
            }
        }

        if (!empty($f['empresa_id'])) {
            $w[] = 't.empresa_id = ?';
            $params[] = (int) $f['empresa_id'];
        }

        if (!empty($f['so_ativos'])) {
            $w[] = 't.ativo = 1';
        }

        $sql = 'SELECT t.*, e.nome AS empresa,
                       (SELECT COUNT(*) FROM so_prontuarios p WHERE p.trabalhador_id = t.id) AS fichas,
                       (SELECT MAX(p.data_exame) FROM so_prontuarios p WHERE p.trabalhador_id = t.id) AS ultimo_exame,
                       (SELECT MIN(p.validade) FROM so_prontuarios p
                         WHERE p.trabalhador_id = t.id AND p.validade IS NOT NULL
                           AND p.validade >= CURDATE()) AS proxima_validade
                  FROM so_trabalhadores t
                  JOIN so_empresas e ON e.id = t.empresa_id'
             . ($w ? ' WHERE ' . implode(' AND ', $w) : '')
             . ' ORDER BY t.nome';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Para o <select> do formulário da ficha. */
    public function trabalhadoresParaEscolha(): array
    {
        return $this->pdo->query(
            'SELECT t.id, t.nome, t.cpf, t.matricula, t.funcao, t.setor, e.nome AS empresa
               FROM so_trabalhadores t
               JOIN so_empresas e ON e.id = t.empresa_id
              WHERE t.ativo = 1
              ORDER BY e.nome, t.nome'
        )->fetchAll();
    }

    public function trabalhadorPorId(int $id): array|false
    {
        $stmt = $this->pdo->prepare(
            'SELECT t.*, e.nome AS empresa
               FROM so_trabalhadores t
               JOIN so_empresas e ON e.id = t.empresa_id
              WHERE t.id = ?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /** Já existe este CPF nesta empresa? */
    public function cpfRepetido(int $empresaId, string $cpf, int $excetoId = 0): int
    {
        if ($cpf === '') {
            return 0;
        }

        $stmt = $this->pdo->prepare(
            'SELECT id FROM so_trabalhadores
              WHERE empresa_id = ? AND cpf = ? AND id <> ?'
        );
        $stmt->execute([$empresaId, $cpf, $excetoId]);
        return (int) $stmt->fetchColumn();
    }

    public function salvarTrabalhador(int $id, array $d): int
    {
        $valores = [
            'empresa_id' => $d['empresa_id'],
            'nome'       => $d['nome'],
            'cpf'        => $d['cpf'] !== '' ? $d['cpf'] : null,
            'matricula'  => $d['matricula'] !== '' ? $d['matricula'] : null,
            'nascimento' => $d['nascimento'] ?: null,
            'funcao'     => $d['funcao'] !== '' ? $d['funcao'] : null,
            'setor'      => $d['setor']  !== '' ? $d['setor']  : null,
            'admissao'   => $d['admissao'] ?: null,
            'ativo'      => $d['ativo'],
            'observacao' => $d['observacao'] !== '' ? $d['observacao'] : null,
            'usuario_id'   => Auth::id(),
            'usuario_nome' => Auth::nome(),
        ];

        if ($id > 0) {
            $campos = implode(' = ?, ', array_keys($valores)) . ' = ?';
            $this->pdo->prepare("UPDATE so_trabalhadores SET {$campos} WHERE id = ?")
                      ->execute([...array_values($valores), $id]);
            return $id;
        }

        $colunas = implode(', ', array_keys($valores));
        $marcas  = implode(', ', array_fill(0, count($valores), '?'));
        $this->pdo->prepare("INSERT INTO so_trabalhadores ({$colunas}) VALUES ({$marcas})")
                  ->execute(array_values($valores));

        return (int) $this->pdo->lastInsertId();
    }

    /** Quantas fichas dependem deste trabalhador. */
    public function fichasDe(int $trabalhadorId): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM so_prontuarios WHERE trabalhador_id = ?'
        );
        $stmt->execute([$trabalhadorId]);
        return (int) $stmt->fetchColumn();
    }

    public function excluirTrabalhador(int $id): bool
    {
        return $this->pdo->prepare('DELETE FROM so_trabalhadores WHERE id = ?')->execute([$id]);
    }

    // ── Entrega de atestado médico ───────────────────────────────────────

    private function ondeAtestados(array $f, array &$params): string
    {
        $w = [];

        if (($f['busca'] ?? '') !== '') {
            $like = '%' . like_escape($f['busca']) . '%';
            $digitos = preg_replace('/\D/', '', $f['busca']) ?? '';
            $likeCpf = $digitos !== '' ? '%' . like_escape($digitos) . '%' : null;

            // O protocolo entra na busca porque é por ele que a pessoa
            // volta ao balcão perguntando: "protocolo 2026/0014".
            $w[] = "(t.nome LIKE ? ESCAPE '!' OR t.matricula LIKE ? ESCAPE '!'
                     OR a.protocolo LIKE ? ESCAPE '!'"
                 . ($likeCpf !== null ? " OR t.cpf LIKE ? ESCAPE '!'" : '') . ')';

            array_push($params, $like, $like, $like);
            if ($likeCpf !== null) {
                $params[] = $likeCpf;
            }
        }

        if (!empty($f['empresa_id'])) {
            $w[] = 't.empresa_id = ?';
            $params[] = (int) $f['empresa_id'];
        }

        if (($f['forma'] ?? '') !== '' && isset(self::FORMAS[$f['forma']])) {
            $w[] = 'a.forma = ?';
            $params[] = $f['forma'];
        }

        if (!empty($f['de'])) {
            $w[] = 'a.entrega_data >= ?';
            $params[] = $f['de'];
        }

        if (!empty($f['ate'])) {
            $w[] = 'a.entrega_data <= ?';
            $params[] = $f['ate'];
        }

        // Quem ainda não assinou o recebimento: é a pilha de papel em cima
        // da mesa, e a pergunta que a coordenação faz no fim do dia.
        if (!empty($f['sem_recebimento'])) {
            $w[] = 'a.recebido_por IS NULL';
        }

        // Quem deveria ter voltado e o retorno já passou.
        if (!empty($f['afastados'])) {
            $w[] = 'a.retorno IS NOT NULL AND a.retorno >= CURDATE()';
        }

        return $w ? ' WHERE ' . implode(' AND ', $w) : '';
    }

    public function listarAtestados(array $f, int $limite, int $offset): array
    {
        $params = [];
        $sql = 'SELECT a.*, t.nome AS trabalhador, t.cpf, t.matricula, t.funcao, t.setor,
                       e.nome AS empresa, e.id AS empresa_id
                  FROM so_atestados a
                  JOIN so_trabalhadores t ON t.id = a.trabalhador_id
                  JOIN so_empresas e      ON e.id = t.empresa_id'
             . $this->ondeAtestados($f, $params)
             . ' ORDER BY a.entrega_data DESC, a.id DESC
                 LIMIT ? OFFSET ?';

        $stmt = $this->pdo->prepare($sql);

        $i = 1;
        foreach ($params as $v) {
            $stmt->bindValue($i++, $v);
        }
        $stmt->bindValue($i++, $limite, PDO::PARAM_INT);
        $stmt->bindValue($i,   $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function contarAtestados(array $f): int
    {
        $params = [];
        $sql = 'SELECT COUNT(*)
                  FROM so_atestados a
                  JOIN so_trabalhadores t ON t.id = a.trabalhador_id
                  JOIN so_empresas e      ON e.id = t.empresa_id'
             . $this->ondeAtestados($f, $params);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /** Os números do topo da lista de atestados. */
    public function panoramaAtestados(): array
    {
        return (array) $this->pdo->query(
            'SELECT
                COUNT(*)                                          AS entregas,
                SUM(YEAR(entrega_data) = YEAR(CURDATE())
                    AND MONTH(entrega_data) = MONTH(CURDATE()))   AS no_mes,
                SUM(recebido_por IS NULL)                         AS sem_recebimento,
                SUM(retorno IS NOT NULL AND retorno >= CURDATE()) AS afastados,
                SUM(dias)                                         AS dias_total
               FROM so_atestados'
        )->fetch();
    }

    public function atestadoPorId(int $id): array|false
    {
        $stmt = $this->pdo->prepare(
            'SELECT a.*, t.nome AS trabalhador, t.cpf, t.matricula, t.nascimento,
                    t.funcao, t.setor, t.empresa_id, e.nome AS empresa, e.cnpj AS empresa_cnpj
               FROM so_atestados a
               JOIN so_trabalhadores t ON t.id = a.trabalhador_id
               JOIN so_empresas e      ON e.id = t.empresa_id
              WHERE a.id = ?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /** As entregas de um trabalhador, da mais recente para a mais antiga. */
    public function atestadosDe(int $trabalhadorId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM so_atestados
              WHERE trabalhador_id = ?
              ORDER BY entrega_data DESC, id DESC'
        );
        $stmt->execute([$trabalhadorId]);
        return $stmt->fetchAll();
    }

    /**
     * Grava a entrega. Na inclusão, o protocolo é do sistema.
     *
     * A sequência sai de MAX(sequencia) do ano, e não de uma contagem: ano
     * com uma entrega excluída no meio continua avançando, em vez de repetir
     * um número que já foi impresso e entregue a alguém. Duas atendentes
     * gravando ao mesmo tempo pegariam o mesmo MAX — quem perder a corrida
     * esbarra na chave única (ano, sequencia) e tenta o número seguinte.
     */
    public function salvarAtestado(int $id, array $d): int
    {
        $valores = [
            'trabalhador_id'  => $d['trabalhador_id'],
            'emissao'         => $d['emissao'],
            'inicio'          => $d['inicio'] ?: null,
            'dias'            => $d['dias'] ?: null,
            'retorno'         => $d['retorno'] ?: null,
            'medico_nome'     => $d['medico_nome']     !== '' ? $d['medico_nome']     : null,
            'medico_registro' => $d['medico_registro'] !== '' ? $d['medico_registro'] : null,

            'entrega_data' => $d['entrega_data'],
            'entrega_hora' => $d['entrega_hora'] ?: null,
            'forma'        => $d['forma'],
            // A descrição do "outro" só faz sentido quando a forma é outro:
            // guardada fora disso, reapareceria numa entrega presencial.
            'forma_outro'  => $d['forma'] === 'outro' && $d['forma_outro'] !== ''
                              ? $d['forma_outro'] : null,

            'recebido_por'  => $d['recebido_por'] !== '' ? $d['recebido_por'] : null,
            'recebido_data' => $d['recebido_data'] ?: null,
            'recebido_hora' => $d['recebido_hora'] ?: null,

            'observacao' => $d['observacao'] !== '' ? $d['observacao'] : null,

            'usuario_id'   => Auth::id(),
            'usuario_nome' => Auth::nome(),
        ];

        if ($id > 0) {
            $campos = implode(' = ?, ', array_keys($valores)) . ' = ?';
            $this->pdo->prepare("UPDATE so_atestados SET {$campos} WHERE id = ?")
                      ->execute([...array_values($valores), $id]);
            return $id;
        }

        $ano = (int) date('Y', strtotime($d['entrega_data']));

        for ($tentativa = 0; $tentativa < 5; $tentativa++) {
            $seq = $this->proximaSequencia($ano);

            $linha = $valores + [
                'ano'       => $ano,
                'sequencia' => $seq,
                'protocolo' => sprintf('%04d/%04d', $ano, $seq),
            ];

            $colunas = implode(', ', array_keys($linha));
            $marcas  = implode(', ', array_fill(0, count($linha), '?'));

            try {
                $this->pdo->prepare("INSERT INTO so_atestados ({$colunas}) VALUES ({$marcas})")
                          ->execute(array_values($linha));

                return (int) $this->pdo->lastInsertId();
            } catch (PDOException $e) {
                // 23000 = violação de chave única: outra pessoa gravou este
                // número no intervalo. Qualquer outro erro é problema de
                // verdade e sobe.
                if ($e->getCode() !== '23000') {
                    throw $e;
                }
            }
        }

        throw new RuntimeException('Não consegui gerar um número de protocolo livre.');
    }

    public function proximaSequencia(int $ano): int
    {
        $stmt = $this->pdo->prepare('SELECT COALESCE(MAX(sequencia), 0) + 1 FROM so_atestados WHERE ano = ?');
        $stmt->execute([$ano]);
        return (int) $stmt->fetchColumn();
    }

    public function excluirAtestado(int $id): bool
    {
        return $this->pdo->prepare('DELETE FROM so_atestados WHERE id = ?')->execute([$id]);
    }

    /** Quantas entregas dependem deste trabalhador. */
    public function atestadosContagem(int $trabalhadorId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM so_atestados WHERE trabalhador_id = ?');
        $stmt->execute([$trabalhadorId]);
        return (int) $stmt->fetchColumn();
    }

    // ── Empresas ─────────────────────────────────────────────────────────

    public function empresas(bool $soAtivas = true): array
    {
        return $this->pdo->query(
            'SELECT * FROM so_empresas'
            . ($soAtivas ? ' WHERE ativo = 1' : '')
            . ' ORDER BY nome'
        )->fetchAll();
    }

    /** Quantos trabalhadores cada empresa tem. */
    public function usoEmpresas(): array
    {
        $linhas = $this->pdo->query(
            'SELECT empresa_id AS id, COUNT(*) AS total
               FROM so_trabalhadores GROUP BY empresa_id'
        )->fetchAll();

        $mapa = [];
        foreach ($linhas as $l) {
            $mapa[(int) $l['id']] = (int) $l['total'];
        }
        return $mapa;
    }

    public function empresaPorId(int $id): array|false
    {
        $stmt = $this->pdo->prepare('SELECT * FROM so_empresas WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function nomeEmpresaExiste(string $nome, int $excetoId = 0): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM so_empresas WHERE nome = ? AND id <> ?');
        $stmt->execute([$nome, $excetoId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function salvarEmpresa(int $id, array $d): bool
    {
        $vals = [
            $d['nome'],
            $d['cnpj']    !== '' ? $d['cnpj']    : null,
            $d['contato'] !== '' ? $d['contato'] : null,
            $d['observacao'] !== '' ? $d['observacao'] : null,
            $d['ativo'],
        ];

        if ($id > 0) {
            $vals[] = $id;
            return $this->pdo->prepare(
                'UPDATE so_empresas SET nome = ?, cnpj = ?, contato = ?, observacao = ?, ativo = ?
                  WHERE id = ?'
            )->execute($vals);
        }

        return $this->pdo->prepare(
            'INSERT INTO so_empresas (nome, cnpj, contato, observacao, ativo)
             VALUES (?, ?, ?, ?, ?)'
        )->execute($vals);
    }

    public function excluirEmpresa(int $id): bool
    {
        return $this->pdo->prepare('DELETE FROM so_empresas WHERE id = ?')->execute([$id]);
    }
}
