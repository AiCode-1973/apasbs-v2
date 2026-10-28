<?php
declare(strict_types=1);

require_once BASE_PATH . '/modules/cadastro/Elegibilidade.php';

class CadastroModel
{
    public const STATUS = [
        'em_analise' => 'Em análise',
        'ativo'      => 'Ativo',
        'suspenso'   => 'Suspenso',
        'cancelado'  => 'Cancelado',
    ];

    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = getPDO();
    }

    // ── Contratos ────────────────────────────────────────────────────────

    private function onde(array $f, array &$params): string
    {
        $w = [];

        if (($f['busca'] ?? '') !== '') {
            $like    = '%' . like_escape($f['busca']) . '%';
            $digitos = preg_replace('/\D/', '', $f['busca']) ?? '';
            $likeCpf = $digitos !== '' ? '%' . like_escape($digitos) . '%' : null;

            // Busca pelo contrato e por qualquer pessoa dentro dele: quem
            // liga é o dependente tanto quanto o titular.
            $w[] = "(c.numero_proposta LIKE ? ESCAPE '!'
                     OR EXISTS (SELECT 1 FROM pl_beneficiarios b
                                 WHERE b.contrato_id = c.id
                                   AND (b.nome LIKE ? ESCAPE '!'"
                 . ($likeCpf !== null ? " OR b.cpf LIKE ? ESCAPE '!'" : '') . ')))';

            array_push($params, $like, $like);
            if ($likeCpf !== null) {
                $params[] = $likeCpf;
            }
        }

        if (($f['status'] ?? '') !== '' && isset(self::STATUS[$f['status']])) {
            $w[] = 'c.status = ?';
            $params[] = $f['status'];
        }

        if (($f['tipo'] ?? '') !== '' && isset(Elegibilidade::TIPOS_CONTRATO[$f['tipo']])) {
            $w[] = 'c.tipo = ?';
            $params[] = $f['tipo'];
        }

        return $w ? ' WHERE ' . implode(' AND ', $w) : '';
    }

    public function listar(array $f, int $limite, int $offset): array
    {
        $params = [];
        $sql = "SELECT c.*,
                       t.nome AS titular, t.cpf AS titular_cpf, t.categoria,
                       (SELECT COUNT(*) FROM pl_beneficiarios b
                         WHERE b.contrato_id = c.id AND b.status = 'ativo') AS pessoas,
                       (SELECT COUNT(*) FROM pl_beneficiarios b
                         WHERE b.contrato_id = c.id) AS cadastradas,
                       " . ($this->moduloDeclaracoes()
                            ? '(SELECT COUNT(*) FROM ds_declaracoes d WHERE d.contrato_id = c.id)'
                            : '0') . " AS declaracoes
                  FROM pl_contratos c
                  LEFT JOIN pl_beneficiarios t
                         ON t.contrato_id = c.id AND t.tipo = 'titular'"
             . $this->onde($f, $params)
             . ' ORDER BY c.created_at DESC, c.id DESC
                 LIMIT ? OFFSET ?';

        $stmt = $this->pdo->prepare($sql);

        // Tudo posicional: o PDO recusa misturar `?` com `:nome`.
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
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM pl_contratos c' . $this->onde($f, $params)
        );
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function panorama(): array
    {
        return (array) $this->pdo->query(
            "SELECT COUNT(*) AS contratos,
                    SUM(status = 'ativo')      AS ativos,
                    SUM(status = 'em_analise') AS em_analise,
                    (SELECT COUNT(*) FROM pl_beneficiarios WHERE status = 'ativo') AS pessoas
               FROM pl_contratos"
        )->fetch();
    }

    public function contratoPorId(int $id): array|false
    {
        $stmt = $this->pdo->prepare('SELECT * FROM pl_contratos WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function propostaExiste(string $numero, int $excetoId = 0): bool
    {
        if ($numero === '') {
            return false;
        }

        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM pl_contratos WHERE numero_proposta = ? AND id <> ?'
        );
        $stmt->execute([$numero, $excetoId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function salvarContrato(int $id, array $d): int
    {
        $vals = [
            'numero_proposta' => $d['numero_proposta'] !== '' ? $d['numero_proposta'] : null,
            'tipo'            => $d['tipo'],
            'data_adesao'     => $d['data_adesao'] ?: null,
            'status'          => $d['status'],
            'observacao'      => $d['observacao'] !== '' ? $d['observacao'] : null,
            'usuario_id'      => Auth::id(),
            'usuario_nome'    => Auth::nome(),
        ];

        if ($id > 0) {
            $campos = implode(' = ?, ', array_keys($vals)) . ' = ?';
            $this->pdo->prepare("UPDATE pl_contratos SET {$campos} WHERE id = ?")
                      ->execute([...array_values($vals), $id]);
            return $id;
        }

        $colunas = implode(', ', array_keys($vals));
        $marcas  = implode(', ', array_fill(0, count($vals), '?'));
        $this->pdo->prepare("INSERT INTO pl_contratos ({$colunas}) VALUES ({$marcas})")
                  ->execute(array_values($vals));

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Remove o contrato e as pessoas dele numa tacada só. Quem chama já
     * conferiu que não há declaração de saúde — essa é documento assinado e
     * trava a exclusão (o banco também recusaria, pela chave estrangeira).
     *
     * @return int quantas pessoas saíram junto
     */
    public function excluirContrato(int $id): int
    {
        $this->pdo->beginTransaction();

        try {
            $st = $this->pdo->prepare('DELETE FROM pl_beneficiarios WHERE contrato_id = ?');
            $st->execute([$id]);
            $pessoas = $st->rowCount();

            $this->pdo->prepare('DELETE FROM pl_contratos WHERE id = ?')->execute([$id]);

            $this->pdo->commit();
            return $pessoas;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * O módulo de declarações é instalado à parte (ds_declaracoes.sql). Sem as
     * tabelas dele, não há declaração nenhuma — e a pergunta não pode dar erro.
     */
    private ?bool $temDs = null;

    private function moduloDeclaracoes(): bool
    {
        if ($this->temDs === null) {
            $st = $this->pdo->prepare(
                "SELECT COUNT(*) FROM information_schema.tables
                  WHERE table_schema = DATABASE() AND table_name IN ('ds_declaracoes', 'ds_respostas')"
            );
            $st->execute();
            $this->temDs = (int) $st->fetchColumn() === 2;
        }

        return $this->temDs;
    }

    /** Quantas declarações de saúde o contrato tem. */
    public function declaracoesDoContrato(int $contratoId): int
    {
        if (!$this->moduloDeclaracoes()) {
            return 0;
        }

        $st = $this->pdo->prepare('SELECT COUNT(*) FROM ds_declaracoes WHERE contrato_id = ?');
        $st->execute([$contratoId]);
        return (int) $st->fetchColumn();
    }

    /** A pessoa já respondeu alguma declaração de saúde? */
    public function temRespostas(int $beneficiarioId): bool
    {
        if (!$this->moduloDeclaracoes()) {
            return false;
        }

        $st = $this->pdo->prepare('SELECT 1 FROM ds_respostas WHERE beneficiario_id = ? LIMIT 1');
        $st->execute([$beneficiarioId]);
        return (bool) $st->fetchColumn();
    }

    // ── Beneficiários ────────────────────────────────────────────────────

    /** Todos do contrato, titular primeiro. */
    public function beneficiarios(int $contratoId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT b.*, p.nome AS plano, p.coparticipacao
               FROM pl_beneficiarios b
               LEFT JOIN pl_planos p ON p.id = b.plano_id
              WHERE b.contrato_id = ?
              ORDER BY b.tipo = 'titular' DESC, b.nome"
        );
        $stmt->execute([$contratoId]);
        return $stmt->fetchAll();
    }

    public function beneficiarioPorId(int $id): array|false
    {
        $stmt = $this->pdo->prepare(
            'SELECT b.*, p.nome AS plano, c.tipo AS tipo_contrato, c.numero_proposta
               FROM pl_beneficiarios b
               LEFT JOIN pl_planos p    ON p.id = b.plano_id
               JOIN pl_contratos c      ON c.id = b.contrato_id
              WHERE b.id = ?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function titularDe(int $contratoId): array|false
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM pl_beneficiarios
              WHERE contrato_id = ? AND tipo = 'titular' LIMIT 1"
        );
        $stmt->execute([$contratoId]);
        return $stmt->fetch();
    }

    /** O mesmo CPF já está neste contrato? */
    public function cpfNoContrato(int $contratoId, string $cpf, int $excetoId = 0): int
    {
        if ($cpf === '') {
            return 0;
        }

        $stmt = $this->pdo->prepare(
            'SELECT id FROM pl_beneficiarios
              WHERE contrato_id = ? AND cpf = ? AND id <> ? LIMIT 1'
        );
        $stmt->execute([$contratoId, $cpf, $excetoId]);
        return (int) $stmt->fetchColumn();
    }

    /** Onde mais este CPF aparece — a mesma pessoa em outro contrato. */
    public function cpfEmOutroContrato(string $cpf, int $contratoId): array|false
    {
        if ($cpf === '') {
            return false;
        }

        $stmt = $this->pdo->prepare(
            'SELECT b.id, b.nome, b.contrato_id, c.numero_proposta
               FROM pl_beneficiarios b
               JOIN pl_contratos c ON c.id = b.contrato_id
              WHERE b.cpf = ? AND b.contrato_id <> ? LIMIT 1'
        );
        $stmt->execute([$cpf, $contratoId]);
        return $stmt->fetch();
    }

    public function salvarBeneficiario(int $id, array $d): int
    {
        $vals = [
            'contrato_id' => $d['contrato_id'],
            'tipo'        => $d['tipo'],
            'nome'        => $d['nome'],
            'cpf'         => $d['cpf'] !== '' ? $d['cpf'] : null,
            'nascimento'  => $d['nascimento'] ?: null,
            'sexo'        => $d['sexo'] !== '' ? $d['sexo'] : null,
            'nome_mae'    => $d['nome_mae'] !== '' ? $d['nome_mae'] : null,
            'cns'         => $d['cns'] !== '' ? $d['cns'] : null,
            'contato'     => $d['contato'] !== '' ? $d['contato'] : null,
            'email'       => $d['email'] !== '' ? $d['email'] : null,

            // Categoria só no titular, parentesco só no dependente: guardar
            // os dois embaralharia o sentido da linha.
            'categoria'  => $d['tipo'] === 'titular' && $d['categoria'] !== '' ? $d['categoria'] : null,
            'matricula'  => $d['tipo'] === 'titular' && $d['matricula'] !== '' ? $d['matricula'] : null,
            'parentesco' => $d['tipo'] === 'dependente' && $d['parentesco'] !== '' ? $d['parentesco'] : null,

            'guarda_judicial'       => $d['guarda_judicial'],
            'tutela_curatela'       => $d['tutela_curatela'],
            'dependencia_economica' => $d['dependencia_economica'],
            'uniao_estavel'         => $d['uniao_estavel'],
            'emancipado'            => $d['emancipado'],
            'vinculo_comprovado'    => $d['vinculo_comprovado'],

            'plano_id'       => $d['plano_id'] ?: null,
            'plano_sugerido' => $d['plano_sugerido'] !== '' ? $d['plano_sugerido'] : null,

            'data_inclusao'   => $d['data_inclusao'] ?: null,
            'data_exclusao'   => $d['status'] === 'inativo' ? ($d['data_exclusao'] ?: null) : null,
            'motivo_exclusao' => $d['status'] === 'inativo' && $d['motivo_exclusao'] !== ''
                                 ? $d['motivo_exclusao'] : null,
            'status'          => $d['status'],
            'observacao'      => $d['observacao'] !== '' ? $d['observacao'] : null,

            'usuario_id'   => Auth::id(),
            'usuario_nome' => Auth::nome(),
        ];

        if ($id > 0) {
            $campos = implode(' = ?, ', array_keys($vals)) . ' = ?';
            $this->pdo->prepare("UPDATE pl_beneficiarios SET {$campos} WHERE id = ?")
                      ->execute([...array_values($vals), $id]);
            return $id;
        }

        $colunas = implode(', ', array_keys($vals));
        $marcas  = implode(', ', array_fill(0, count($vals), '?'));
        $this->pdo->prepare("INSERT INTO pl_beneficiarios ({$colunas}) VALUES ({$marcas})")
                  ->execute(array_values($vals));

        return (int) $this->pdo->lastInsertId();
    }

    public function excluirBeneficiario(int $id): bool
    {
        return $this->pdo->prepare('DELETE FROM pl_beneficiarios WHERE id = ?')->execute([$id]);
    }

    // ── Planos e preços ──────────────────────────────────────────────────

    public function planos(bool $soAtivos = true): array
    {
        return $this->pdo->query(
            'SELECT * FROM pl_planos' . ($soAtivos ? ' WHERE ativo = 1' : '') . ' ORDER BY ordem, nome'
        )->fetchAll();
    }

    public function planoPorNome(string $nome): array|false
    {
        $stmt = $this->pdo->prepare('SELECT * FROM pl_planos WHERE nome = ?');
        $stmt->execute([$nome]);
        return $stmt->fetch();
    }

    /**
     * A tabela de preços que vale numa data.
     *
     * Para cada plano, a vigência mais recente que já começou. Reajuste
     * futuro fica guardado e só passa a valer quando a data chegar.
     *
     * @return array<int, array<int, float>>  [plano_id][faixa] = valor
     */
    public function precosVigentes(?string $em = null): array
    {
        $em = $em ?: date('Y-m-d');

        $stmt = $this->pdo->prepare(
            'SELECT pr.plano_id, pr.faixa, pr.valor
               FROM pl_precos pr
               JOIN (SELECT plano_id, MAX(vigencia) AS vigencia
                       FROM pl_precos WHERE vigencia <= ?
                      GROUP BY plano_id) atual
                 ON atual.plano_id = pr.plano_id AND atual.vigencia = pr.vigencia'
        );
        $stmt->execute([$em]);

        $tabela = [];
        foreach ($stmt->fetchAll() as $l) {
            $tabela[(int) $l['plano_id']][(int) $l['faixa']] = (float) $l['valor'];
        }
        return $tabela;
    }

    /** As vigências de cada plano, para a tela de preços. */
    public function vigencias(): array
    {
        return $this->pdo->query(
            'SELECT pr.plano_id, p.nome AS plano, pr.vigencia, COUNT(*) AS faixas,
                    MIN(pr.valor) AS menor, MAX(pr.valor) AS maior,
                    MAX(pr.usuario_nome) AS usuario_nome
               FROM pl_precos pr
               JOIN pl_planos p ON p.id = pr.plano_id
              GROUP BY pr.plano_id, pr.vigencia
              ORDER BY p.ordem, pr.vigencia DESC'
        )->fetchAll();
    }

    /** Os valores de um plano numa vigência, para edição. */
    public function precosDe(int $planoId, string $vigencia): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT faixa, valor FROM pl_precos
              WHERE plano_id = ? AND vigencia = ? ORDER BY faixa'
        );
        $stmt->execute([$planoId, $vigencia]);

        $valores = [];
        foreach ($stmt->fetchAll() as $l) {
            $valores[(int) $l['faixa']] = (float) $l['valor'];
        }
        return $valores;
    }

    /** Grava as dez faixas de um plano numa vigência, de uma vez. */
    public function salvarPrecos(int $planoId, string $vigencia, array $valores): void
    {
        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO pl_precos (plano_id, vigencia, faixa, valor, usuario_id, usuario_nome)
                 VALUES (?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE valor = VALUES(valor),
                                         usuario_id = VALUES(usuario_id),
                                         usuario_nome = VALUES(usuario_nome)'
            );

            foreach ($valores as $faixa => $valor) {
                $stmt->execute([
                    $planoId, $vigencia, (int) $faixa, $valor, Auth::id(), Auth::nome(),
                ]);
            }

            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function excluirVigencia(int $planoId, string $vigencia): bool
    {
        return $this->pdo->prepare(
            'DELETE FROM pl_precos WHERE plano_id = ? AND vigencia = ?'
        )->execute([$planoId, $vigencia]);
    }

    // ── O plano do titular manda no contrato ─────────────────────────────

    /**
     * Quem está fora do plano do titular.
     *
     * A regra da APAS: o contrato é da família, e o plano é do contrato —
     * mudou o do titular, muda o de todo mundo. Esta consulta é o "quem
     * ainda não mudou", e alimenta tanto a tela de confirmação quanto o
     * aviso no contrato.
     *
     * Inativos entram na conta: eles voltam, e voltariam no plano antigo.
     */
    public function desalinhados(int $contratoId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT b.*, p.nome AS plano, t.plano_id AS plano_titular
               FROM pl_beneficiarios b
               JOIN pl_beneficiarios t ON t.contrato_id = b.contrato_id AND t.tipo = 'titular'
               LEFT JOIN pl_planos p ON p.id = b.plano_id
              WHERE b.contrato_id = ?
                AND b.tipo = 'dependente'
                AND t.plano_id IS NOT NULL
                AND (b.plano_id IS NULL OR b.plano_id <> t.plano_id)
              ORDER BY b.nome"
        );
        $stmt->execute([$contratoId]);
        return $stmt->fetchAll();
    }

    /**
     * O que muda, em pessoas e em dinheiro, se os dependentes forem para o
     * plano do titular. Nada é gravado aqui — isto é o que a tela de
     * confirmação mostra antes do "sim".
     */
    public function simularAlinhamento(int $contratoId, ?string $em = null): array
    {
        $titular = $this->titularDe($contratoId);
        $planoId = (int) ($titular['plano_id'] ?? 0);

        if ($planoId <= 0) {
            return ['plano' => null, 'plano_id' => 0, 'linhas' => [],
                    'antes' => 0.0, 'depois' => 0.0];
        }

        $precos  = $this->precosVigentes($em);
        $pessoas = $this->beneficiarios($contratoId);
        $nomes   = [];

        foreach ($this->planos(false) as $pl) {
            $nomes[(int) $pl['id']] = $pl['nome'];
        }

        $linhas = [];
        $antes  = 0.0;
        $depois = 0.0;

        foreach ($pessoas as $b) {
            // Inativo não paga, nem antes nem depois — mas continua na lista
            // de quem muda de plano, para não voltar no plano velho.
            $conta = ($b['status'] ?? 'ativo') === 'ativo';

            $idade = Elegibilidade::idade($b['nascimento'] ?? null, $em);
            $faixa = Elegibilidade::faixa($idade);
            $de    = (int) ($b['plano_id'] ?? 0);
            $para  = $b['tipo'] === 'titular' ? $de : $planoId;

            $valorDe   = ($faixa !== null && $de   > 0) ? ($precos[$de][$faixa]   ?? null) : null;
            $valorPara = ($faixa !== null && $para > 0) ? ($precos[$para][$faixa] ?? null) : null;

            if ($conta) {
                $antes  += (float) $valorDe;
                $depois += (float) $valorPara;
            }

            if ($de === $para) {
                continue;   // titular e quem já está alinhado
            }

            $linhas[] = [
                'id'         => (int) $b['id'],
                'nome'       => $b['nome'],
                'status'     => $b['status'],
                'parentesco' => $b['parentesco'],
                'idade'      => $idade,
                'de'         => $de > 0 ? ($nomes[$de] ?? '—') : null,
                'para'       => $nomes[$para] ?? '—',
                'valor_de'   => $valorDe,
                'valor_para' => $valorPara,
            ];
        }

        return [
            'plano'    => $nomes[$planoId] ?? null,
            'plano_id' => $planoId,
            'linhas'   => $linhas,
            'antes'    => $antes,
            'depois'   => $depois,
        ];
    }

    /**
     * Põe todos os dependentes no plano do titular.
     *
     * Sem exceção, por decisão da APAS: mesmo quem a regra de elegibilidade
     * indicaria noutro plano — menor sob guarda, por exemplo — acompanha o
     * titular. O `plano_sugerido` continua gravado como estava, e é ele que
     * deixa visível que a escolha divergiu da regra.
     *
     * @return int quantas pessoas mudaram
     */
    public function alinharPlanos(int $contratoId): int
    {
        $titular = $this->titularDe($contratoId);
        $planoId = (int) ($titular['plano_id'] ?? 0);

        if ($planoId <= 0) {
            return 0;
        }

        $stmt = $this->pdo->prepare(
            "UPDATE pl_beneficiarios
                SET plano_id = ?
              WHERE contrato_id = ?
                AND tipo = 'dependente'
                AND (plano_id IS NULL OR plano_id <> ?)"
        );
        $stmt->execute([$planoId, $contratoId, $planoId]);

        return $stmt->rowCount();
    }

    // ── Mensalidade ──────────────────────────────────────────────────────

    /**
     * O que a família paga por mês.
     *
     * A faixa etária de cada pessoa é calculada na data de referência, e
     * não guardada: ela muda sozinha no aniversário, e é por isso que a
     * mensalidade de uma família sobe sem ninguém mexer no cadastro.
     *
     * Só entra quem está ativo — excluído não paga.
     */
    public function mensalidade(array $beneficiarios, ?string $em = null): array
    {
        $precos = $this->precosVigentes($em);
        $linhas = [];
        $total  = 0.0;

        foreach ($beneficiarios as $b) {
            if (($b['status'] ?? 'ativo') !== 'ativo') {
                continue;
            }

            $idade = Elegibilidade::idade($b['nascimento'] ?? null, $em);
            $faixa = Elegibilidade::faixa($idade);
            $plano = (int) ($b['plano_id'] ?? 0);
            $valor = ($faixa !== null && $plano > 0) ? ($precos[$plano][$faixa] ?? null) : null;

            if ($valor !== null) {
                $total += $valor;
            }

            $linhas[] = [
                'id'     => (int) $b['id'],
                'nome'   => $b['nome'],
                'tipo'   => $b['tipo'],
                'plano'  => $b['plano'] ?? null,
                'idade'  => $idade,
                'faixa'  => $faixa,
                'valor'  => $valor,
                // Sem valor, o motivo: é o que a tela precisa dizer para a
                // pessoa saber o que fazer.
                'falta'  => $valor !== null ? null : match (true) {
                    $plano === 0     => 'sem plano definido',
                    $faixa === null  => 'sem data de nascimento',
                    default          => 'sem preço na tabela vigente',
                },
            ];
        }

        return ['linhas' => $linhas, 'total' => $total];
    }
}
