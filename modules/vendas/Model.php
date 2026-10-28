<?php
declare(strict_types=1);

require_once BASE_PATH . '/modules/cadastro/Model.php';

class VendasModel
{
    /** O funil, do primeiro contato ao desfecho. */
    public const STATUS = [
        'em_negociacao' => 'Em negociação',
        'fechada'       => 'Fechada',
        'perdida'       => 'Perdida',
    ];

    private PDO $pdo;
    private CadastroModel $cadastro;

    public function __construct()
    {
        $this->pdo = getPDO();
        $this->cadastro = new CadastroModel();
    }

    // ── Empresas parceiras ───────────────────────────────────────────────

    public function empresas(bool $soAtivas = true): array
    {
        return $this->pdo->query(
            'SELECT e.*, (SELECT COUNT(*) FROM vd_vendedores v WHERE v.empresa_id = e.id) AS vendedores
               FROM vd_empresas e'
            . ($soAtivas ? ' WHERE e.ativo = 1' : '')
            . ' ORDER BY e.nome'
        )->fetchAll();
    }

    public function empresaPorId(int $id): array|false
    {
        $stmt = $this->pdo->prepare('SELECT * FROM vd_empresas WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function nomeEmpresaExiste(string $nome, int $excetoId = 0): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM vd_empresas WHERE nome = ? AND id <> ?');
        $stmt->execute([$nome, $excetoId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function salvarEmpresa(int $id, array $d): int
    {
        $vals = [
            'nome'       => $d['nome'],
            'cnpj'       => $d['cnpj']    !== '' ? $d['cnpj']    : null,
            'contato'    => $d['contato'] !== '' ? $d['contato'] : null,
            'email'      => $d['email']   !== '' ? $d['email']   : null,
            'observacao' => $d['observacao'] !== '' ? $d['observacao'] : null,
            'ativo'      => $d['ativo'],
        ];

        if ($id > 0) {
            $campos = implode(' = ?, ', array_keys($vals)) . ' = ?';
            $this->pdo->prepare("UPDATE vd_empresas SET {$campos} WHERE id = ?")
                      ->execute([...array_values($vals), $id]);
            return $id;
        }

        $colunas = implode(', ', array_keys($vals));
        $marcas  = implode(', ', array_fill(0, count($vals), '?'));
        $this->pdo->prepare("INSERT INTO vd_empresas ({$colunas}) VALUES ({$marcas})")
                  ->execute(array_values($vals));

        return (int) $this->pdo->lastInsertId();
    }

    public function excluirEmpresa(int $id): bool
    {
        return $this->pdo->prepare('DELETE FROM vd_empresas WHERE id = ?')->execute([$id]);
    }

    // ── Vendedores ───────────────────────────────────────────────────────

    public function vendedores(bool $soAtivos = false): array
    {
        return $this->pdo->query(
            'SELECT v.*, e.nome AS empresa, u.nome AS usuario,
                    (SELECT COUNT(*) FROM vd_propostas p WHERE p.vendedor_id = v.id) AS propostas,
                    (SELECT COUNT(*) FROM vd_propostas p
                      WHERE p.vendedor_id = v.id AND p.status = \'fechada\') AS fechadas
               FROM vd_vendedores v
               JOIN vd_empresas e ON e.id = v.empresa_id
               LEFT JOIN usuarios u ON u.id = v.usuario_id'
            . ($soAtivos ? ' WHERE v.ativo = 1' : '')
            . ' ORDER BY e.nome, v.nome'
        )->fetchAll();
    }

    public function vendedorPorId(int $id): array|false
    {
        $stmt = $this->pdo->prepare(
            'SELECT v.*, e.nome AS empresa FROM vd_vendedores v
               JOIN vd_empresas e ON e.id = v.empresa_id
              WHERE v.id = ?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * O vendedor que corresponde a quem está logado.
     *
     * É o que faz um vendedor com login ver apenas as vendas dele: a tela
     * não pergunta nada, ela já sabe.
     */
    public function vendedorDoUsuario(?int $usuarioId): array|false
    {
        if (!$usuarioId) {
            return false;
        }

        $stmt = $this->pdo->prepare('SELECT * FROM vd_vendedores WHERE usuario_id = ?');
        $stmt->execute([$usuarioId]);
        return $stmt->fetch();
    }

    public function cpfRepetido(int $empresaId, string $cpf, int $excetoId = 0): int
    {
        if ($cpf === '') {
            return 0;
        }

        $stmt = $this->pdo->prepare(
            'SELECT id FROM vd_vendedores WHERE empresa_id = ? AND cpf = ? AND id <> ?'
        );
        $stmt->execute([$empresaId, $cpf, $excetoId]);
        return (int) $stmt->fetchColumn();
    }

    public function usuarioJaVendedor(int $usuarioId, int $excetoId = 0): int
    {
        if ($usuarioId <= 0) {
            return 0;
        }

        $stmt = $this->pdo->prepare('SELECT id FROM vd_vendedores WHERE usuario_id = ? AND id <> ?');
        $stmt->execute([$usuarioId, $excetoId]);
        return (int) $stmt->fetchColumn();
    }

    public function salvarVendedor(int $id, array $d): int
    {
        $vals = [
            'empresa_id'       => $d['empresa_id'],
            'nome'             => $d['nome'],
            'cpf'              => $d['cpf']     !== '' ? $d['cpf']     : null,
            'contato'          => $d['contato'] !== '' ? $d['contato'] : null,
            'email'            => $d['email']   !== '' ? $d['email']   : null,
            'comissao_percent' => $d['comissao_percent'],
            'usuario_id'       => $d['usuario_id'] ?: null,
            'ativo'            => $d['ativo'],
            'observacao'       => $d['observacao'] !== '' ? $d['observacao'] : null,
        ];

        if ($id > 0) {
            $campos = implode(' = ?, ', array_keys($vals)) . ' = ?';
            $this->pdo->prepare("UPDATE vd_vendedores SET {$campos} WHERE id = ?")
                      ->execute([...array_values($vals), $id]);
            return $id;
        }

        $colunas = implode(', ', array_keys($vals));
        $marcas  = implode(', ', array_fill(0, count($vals), '?'));
        $this->pdo->prepare("INSERT INTO vd_vendedores ({$colunas}) VALUES ({$marcas})")
                  ->execute(array_values($vals));

        return (int) $this->pdo->lastInsertId();
    }

    public function excluirVendedor(int $id): bool
    {
        return $this->pdo->prepare('DELETE FROM vd_vendedores WHERE id = ?')->execute([$id]);
    }

    /**
     * Põe o mesmo percentual em todos os vendedores.
     *
     * Vale para as vendas DAQUI PARA FRENTE: o percentual das propostas já
     * fechadas foi congelado nelas no dia do fechamento, e esta mudança não
     * o alcança — que é o ponto de ter congelado.
     *
     * @return int quantos vendedores mudaram
     */
    public function comissaoParaTodos(float $percent, bool $incluirInativos = false): int
    {
        $sql = 'UPDATE vd_vendedores SET comissao_percent = ? WHERE comissao_percent <> ?'
             . ($incluirInativos ? '' : ' AND ativo = 1');

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$percent, $percent]);

        return $stmt->rowCount();
    }

    public function propostasDe(int $vendedorId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM vd_propostas WHERE vendedor_id = ?');
        $stmt->execute([$vendedorId]);
        return (int) $stmt->fetchColumn();
    }

    // ── Propostas ────────────────────────────────────────────────────────

    private function onde(array $f, array &$params): string
    {
        $w = [];

        if (($f['busca'] ?? '') !== '') {
            $like    = '%' . like_escape($f['busca']) . '%';
            $digitos = preg_replace('/\D/', '', $f['busca']) ?? '';
            $likeCpf = $digitos !== '' ? '%' . like_escape($digitos) . '%' : null;

            $w[] = "(p.titular_nome LIKE ? ESCAPE '!' OR p.numero LIKE ? ESCAPE '!'
                     OR v.nome LIKE ? ESCAPE '!'"
                 . ($likeCpf !== null ? " OR p.titular_cpf LIKE ? ESCAPE '!'" : '') . ')';

            array_push($params, $like, $like, $like);
            if ($likeCpf !== null) {
                $params[] = $likeCpf;
            }
        }

        if (!empty($f['vendedor_id'])) {
            $w[] = 'p.vendedor_id = ?';
            $params[] = (int) $f['vendedor_id'];
        }

        if (!empty($f['empresa_id'])) {
            $w[] = 'v.empresa_id = ?';
            $params[] = (int) $f['empresa_id'];
        }

        if (($f['status'] ?? '') !== '' && isset(self::STATUS[$f['status']])) {
            $w[] = 'p.status = ?';
            $params[] = $f['status'];
        }

        if (!empty($f['de'])) {
            $w[] = 'p.data_proposta >= ?';
            $params[] = $f['de'];
        }

        if (!empty($f['ate'])) {
            $w[] = 'p.data_proposta <= ?';
            $params[] = $f['ate'];
        }

        return $w ? ' WHERE ' . implode(' AND ', $w) : '';
    }

    public function listar(array $f, int $limite, int $offset): array
    {
        $params = [];
        $sql = 'SELECT p.*, v.nome AS vendedor, v.comissao_percent AS percent_atual,
                       e.nome AS empresa, c.status AS contrato_status
                  FROM vd_propostas p
                  JOIN vd_vendedores v ON v.id = p.vendedor_id
                  JOIN vd_empresas   e ON e.id = v.empresa_id
                  LEFT JOIN pl_contratos c ON c.id = p.contrato_id'
             . $this->onde($f, $params)
             . ' ORDER BY p.data_proposta DESC, p.id DESC
                 LIMIT ? OFFSET ?';

        $stmt = $this->pdo->prepare($sql);

        // Tudo posicional: o PDO recusa misturar `?` com `:nome`.
        $i = 1;
        foreach ($params as $valor) {
            $stmt->bindValue($i++, $valor);
        }
        $stmt->bindValue($i++, $limite, PDO::PARAM_INT);
        $stmt->bindValue($i,   $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function contar(array $f): int
    {
        $params = [];
        $sql = 'SELECT COUNT(*) FROM vd_propostas p
                  JOIN vd_vendedores v ON v.id = p.vendedor_id'
             . $this->onde($f, $params);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /** Os números do topo da lista, respeitando o filtro de vendedor. */
    public function panorama(array $f): array
    {
        $params = [];
        $sql = "SELECT
                   COUNT(*) AS propostas,
                   SUM(p.status = 'em_negociacao') AS em_negociacao,
                   SUM(p.status = 'fechada')       AS fechadas,
                   SUM(p.status = 'perdida')       AS perdidas,
                   SUM(p.status = 'fechada' AND YEAR(p.data_fechamento) = YEAR(CURDATE())
                       AND MONTH(p.data_fechamento) = MONTH(CURDATE())) AS fechadas_mes,
                   SUM(CASE WHEN p.status = 'fechada' THEN p.vidas ELSE 0 END) AS vidas
                 FROM vd_propostas p
                 JOIN vd_vendedores v ON v.id = p.vendedor_id"
             . $this->onde($f, $params);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (array) $stmt->fetch();
    }

    public function propostaPorId(int $id): array|false
    {
        $stmt = $this->pdo->prepare(
            'SELECT p.*, v.nome AS vendedor, v.comissao_percent AS percent_atual,
                    v.usuario_id AS vendedor_usuario, e.nome AS empresa, e.id AS empresa_id,
                    c.numero_proposta AS contrato_numero, c.status AS contrato_status
               FROM vd_propostas p
               JOIN vd_vendedores v ON v.id = p.vendedor_id
               JOIN vd_empresas   e ON e.id = v.empresa_id
               LEFT JOIN pl_contratos c ON c.id = p.contrato_id
              WHERE p.id = ?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function proximaSequencia(int $ano): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COALESCE(MAX(sequencia), 0) + 1 FROM vd_propostas WHERE ano = ?'
        );
        $stmt->execute([$ano]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Grava a proposta. Na inclusão, o número é do sistema.
     *
     * A sequência sai de MAX(sequencia) do ano: ano com proposta excluída no
     * meio continua avançando, em vez de repetir um número que o vendedor já
     * falou ao telefone. Duas pessoas gravando ao mesmo tempo pegam o mesmo
     * MAX — quem perde a corrida esbarra na chave única e tenta o seguinte.
     */
    public function salvarProposta(int $id, array $d): int
    {
        $vals = [
            'vendedor_id'       => $d['vendedor_id'],
            'titular_nome'      => $d['titular_nome'],
            'titular_cpf'       => $d['titular_cpf'] !== '' ? $d['titular_cpf'] : null,
            'titular_nascimento' => $d['titular_nascimento'] ?: null,
            'titular_categoria' => $d['titular_categoria'] !== '' ? $d['titular_categoria'] : null,
            'contato'           => $d['contato'] !== '' ? $d['contato'] : null,
            'email'             => $d['email']   !== '' ? $d['email']   : null,
            'tipo'              => $d['tipo'],
            'vidas'             => max(1, (int) $d['vidas']),
            'data_proposta'     => $d['data_proposta'],
            'observacao'        => $d['observacao'] !== '' ? $d['observacao'] : null,
            'usuario_id'        => Auth::id(),
            'usuario_nome'      => Auth::nome(),
        ];

        if ($id > 0) {
            $campos = implode(' = ?, ', array_keys($vals)) . ' = ?';
            $this->pdo->prepare("UPDATE vd_propostas SET {$campos} WHERE id = ?")
                      ->execute([...array_values($vals), $id]);
            return $id;
        }

        $ano = (int) date('Y', strtotime($d['data_proposta']));

        for ($tentativa = 0; $tentativa < 5; $tentativa++) {
            $linha = $vals + [
                'ano'       => $ano,
                'sequencia' => $this->proximaSequencia($ano),
                'status'    => 'em_negociacao',
            ];
            $linha['numero'] = sprintf('%04d/%04d', $ano, $linha['sequencia']);

            $colunas = implode(', ', array_keys($linha));
            $marcas  = implode(', ', array_fill(0, count($linha), '?'));

            try {
                $this->pdo->prepare("INSERT INTO vd_propostas ({$colunas}) VALUES ({$marcas})")
                          ->execute(array_values($linha));
                return (int) $this->pdo->lastInsertId();
            } catch (PDOException $e) {
                // 23000 = chave única: outra pessoa gravou este número agora.
                if ($e->getCode() !== '23000') {
                    throw $e;
                }
            }
        }

        throw new RuntimeException('Não consegui gerar um número de proposta livre.');
    }

    public function excluirProposta(int $id): bool
    {
        return $this->pdo->prepare('DELETE FROM vd_propostas WHERE id = ?')->execute([$id]);
    }

    // ── As vidas da família ──────────────────────────────────────────────

    public function pessoas(int $propostaId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM vd_proposta_pessoas WHERE proposta_id = ? ORDER BY nome'
        );
        $stmt->execute([$propostaId]);
        return $stmt->fetchAll();
    }

    public function pessoaPorId(int $id): array|false
    {
        $stmt = $this->pdo->prepare(
            'SELECT p.*, pr.vendedor_id, pr.status AS proposta_status, pr.numero
               FROM vd_proposta_pessoas p
               JOIN vd_propostas pr ON pr.id = p.proposta_id
              WHERE p.id = ?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function salvarPessoa(int $id, array $d): int
    {
        $vals = [
            'proposta_id' => $d['proposta_id'],
            'nome'        => $d['nome'],
            'cpf'         => $d['cpf'] !== '' ? $d['cpf'] : null,
            'nascimento'  => $d['nascimento'] ?: null,
            'parentesco'  => $d['parentesco'] !== '' ? $d['parentesco'] : null,
            'guarda_judicial'       => $d['guarda_judicial'],
            'tutela_curatela'       => $d['tutela_curatela'],
            'dependencia_economica' => $d['dependencia_economica'],
            'uniao_estavel'         => $d['uniao_estavel'],
            'emancipado'            => $d['emancipado'],
            'vinculo_comprovado'    => $d['vinculo_comprovado'],
            'observacao'  => $d['observacao'] !== '' ? $d['observacao'] : null,
        ];

        if ($id > 0) {
            $campos = implode(' = ?, ', array_keys($vals)) . ' = ?';
            $this->pdo->prepare("UPDATE vd_proposta_pessoas SET {$campos} WHERE id = ?")
                      ->execute([...array_values($vals), $id]);
            $novoId = $id;
        } else {
            $colunas = implode(', ', array_keys($vals));
            $marcas  = implode(', ', array_fill(0, count($vals), '?'));
            $this->pdo->prepare("INSERT INTO vd_proposta_pessoas ({$colunas}) VALUES ({$marcas})")
                      ->execute(array_values($vals));
            $novoId = (int) $this->pdo->lastInsertId();
        }

        $this->ajustarVidas((int) $d['proposta_id']);
        return $novoId;
    }

    public function excluirPessoa(int $id): bool
    {
        $pessoa = $this->pessoaPorId($id);
        $feito = $this->pdo->prepare('DELETE FROM vd_proposta_pessoas WHERE id = ?')->execute([$id]);

        if ($pessoa) {
            $this->ajustarVidas((int) $pessoa['proposta_id']);
        }

        return $feito;
    }

    /**
     * Mantém `vidas` coerente com a lista.
     *
     * O campo continua sendo a previsão da venda — na primeira conversa o
     * vendedor sabe quantos são antes de saber os nomes. Mas cadastrou mais
     * gente do que previu, a previsão sobe sozinha: contar errado o que já
     * está escrito ali seria pior do que não contar.
     */
    public function ajustarVidas(int $propostaId): void
    {
        $this->pdo->prepare(
            'UPDATE vd_propostas p
                SET p.vidas = GREATEST(p.vidas,
                        1 + (SELECT COUNT(*) FROM vd_proposta_pessoas x WHERE x.proposta_id = p.id))
              WHERE p.id = ?'
        )->execute([$propostaId]);
    }

    // ── O fechamento: a proposta vira contrato ───────────────────────────

    /**
     * Fecha a venda criando o contrato e o titular no Cadastro.
     *
     * Em transação: contrato sem titular, ou proposta apontando para um
     * contrato que não nasceu, seriam dois jeitos de ficar pela metade.
     *
     * O plano do titular sai do motor de elegibilidade do Cadastro — e
     * daí em diante vale a regra de lá, inclusive a de que o plano do
     * titular manda na família.
     *
     * @return array{contrato_id:int, beneficiario_id:int}
     */
    public function fechar(int $id, string $dataFechamento): array
    {
        $p = $this->propostaPorId($id);

        if (!$p || $p['status'] === 'fechada') {
            throw new RuntimeException('Proposta não encontrada ou já fechada.');
        }

        $this->pdo->beginTransaction();

        try {
            // O número da proposta vira o número do contrato — mas ele é
            // único lá, e o Cadastro pode já ter usado. Batendo, o contrato
            // nasce sem número em vez de a venda não fechar.
            $numero = $p['numero'];
            $existe = $this->pdo->prepare('SELECT COUNT(*) FROM pl_contratos WHERE numero_proposta = ?');
            $existe->execute([$numero]);
            if ((int) $existe->fetchColumn() > 0) {
                $numero = '';
            }

            $contratoId = $this->cadastro->salvarContrato(0, [
                'numero_proposta' => $numero,
                'tipo'            => $p['tipo'],
                'data_adesao'     => $dataFechamento,
                'status'          => 'em_analise',
                'observacao'      => sprintf('Venda %s — %s (%s).', $p['numero'],
                                             $p['vendedor'], $p['empresa']),
            ]);

            $titular = [
                'tipo'       => 'titular',
                'categoria'  => (string) ($p['titular_categoria'] ?? ''),
                'nascimento' => $p['titular_nascimento'],
            ];
            $avaliacao = Elegibilidade::avaliar($titular, $p['tipo']);
            $plano = $avaliacao['plano'] ? $this->cadastro->planoPorNome($avaliacao['plano']) : false;

            $beneficiarioId = $this->cadastro->salvarBeneficiario(0, [
                'contrato_id' => $contratoId,
                'tipo'        => 'titular',
                'nome'        => $p['titular_nome'],
                'cpf'         => (string) ($p['titular_cpf'] ?? ''),
                'nascimento'  => $p['titular_nascimento'],
                'sexo'        => '',
                'nome_mae'    => '',
                'cns'         => '',
                'contato'     => (string) ($p['contato'] ?? ''),
                'email'       => (string) ($p['email'] ?? ''),
                'categoria'   => (string) ($p['titular_categoria'] ?? ''),
                'matricula'   => '',
                'parentesco'  => '',
                'guarda_judicial'       => 0,
                'tutela_curatela'       => 0,
                'dependencia_economica' => 0,
                'uniao_estavel'         => 0,
                'emancipado'            => 0,
                'vinculo_comprovado'    => 0,
                'plano_id'       => $plano ? (int) $plano['id'] : 0,
                'plano_sugerido' => (string) ($avaliacao['plano'] ?? ''),
                'data_inclusao'  => $dataFechamento,
                'data_exclusao'  => null,
                'motivo_exclusao' => '',
                'status'         => 'ativo',
                'observacao'     => '',
            ]);

            // As vidas cadastradas na proposta viram dependentes do contrato.
            // O plano é o do titular, que é a regra da casa no Cadastro; o
            // que a elegibilidade indicaria fica em `plano_sugerido`, e é ele
            // que deixa a divergência visível na conferência.
            $dependentes = 0;
            foreach ($this->pessoas($id) as $pessoa) {
                $dados = [
                    'tipo'       => 'dependente',
                    'parentesco' => (string) ($pessoa['parentesco'] ?? ''),
                    'nascimento' => $pessoa['nascimento'],
                ];
                foreach (array_keys(Elegibilidade::CONDICOES) as $condicao) {
                    $dados[$condicao] = (int) ($pessoa[$condicao] ?? 0);
                }

                $aval = Elegibilidade::avaliar($dados, $p['tipo']);

                $this->cadastro->salvarBeneficiario(0, [
                    'contrato_id' => $contratoId,
                    'tipo'        => 'dependente',
                    'nome'        => $pessoa['nome'],
                    'cpf'         => (string) ($pessoa['cpf'] ?? ''),
                    'nascimento'  => $pessoa['nascimento'],
                    'sexo'        => '',
                    'nome_mae'    => '',
                    'cns'         => '',
                    'contato'     => '',
                    'email'       => '',
                    'categoria'   => '',
                    'matricula'   => '',
                    'parentesco'  => (string) ($pessoa['parentesco'] ?? ''),
                    'guarda_judicial'       => (int) $pessoa['guarda_judicial'],
                    'tutela_curatela'       => (int) $pessoa['tutela_curatela'],
                    'dependencia_economica' => (int) $pessoa['dependencia_economica'],
                    'uniao_estavel'         => (int) $pessoa['uniao_estavel'],
                    'emancipado'            => (int) $pessoa['emancipado'],
                    'vinculo_comprovado'    => (int) $pessoa['vinculo_comprovado'],
                    'plano_id'       => $plano ? (int) $plano['id'] : 0,
                    'plano_sugerido' => (string) ($aval['plano'] ?? ''),
                    'data_inclusao'  => $dataFechamento,
                    'data_exclusao'  => null,
                    'motivo_exclusao' => '',
                    'status'         => 'ativo',
                    'observacao'     => (string) ($pessoa['observacao'] ?? ''),
                ]);

                $dependentes++;
            }

            // O percentual é copiado do vendedor agora: renegociar a comissão
            // depois não pode mexer no que já foi vendido.
            $this->pdo->prepare(
                "UPDATE vd_propostas
                    SET status = 'fechada', data_fechamento = ?, contrato_id = ?,
                        comissao_percent = ?, motivo_perda = NULL
                  WHERE id = ?"
            )->execute([$dataFechamento, $contratoId, $p['percent_atual'], $id]);

            $this->pdo->commit();

            return ['contrato_id' => $contratoId, 'beneficiario_id' => $beneficiarioId,
                    'dependentes' => $dependentes];
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function perder(int $id, string $motivo): bool
    {
        return $this->pdo->prepare(
            "UPDATE vd_propostas SET status = 'perdida', motivo_perda = ?,
                    data_fechamento = NULL
              WHERE id = ? AND status <> 'fechada'"
        )->execute([$motivo !== '' ? $motivo : null, $id]);
    }

    public function reabrir(int $id): bool
    {
        return $this->pdo->prepare(
            "UPDATE vd_propostas SET status = 'em_negociacao', motivo_perda = NULL
              WHERE id = ? AND status = 'perdida'"
        )->execute([$id]);
    }

    // ── Comissão e desempenho ────────────────────────────────────────────

    /**
     * A mensalidade de hoje do contrato que a venda gerou.
     *
     * Não é guardada na proposta de propósito: a família cresce, a faixa
     * etária muda no aniversário, e a mensalidade sobe sozinha. Guardar o
     * valor do dia do fechamento criaria uma segunda verdade que envelhece.
     */
    public function mensalidadeDoContrato(?int $contratoId): float
    {
        if (!$contratoId) {
            return 0.0;
        }

        $conta = $this->cadastro->mensalidade($this->cadastro->beneficiarios($contratoId));
        return (float) $conta['total'];
    }

    /** Mensalidade e comissão de uma proposta fechada. */
    public function comissao(array $proposta): array
    {
        $mensalidade = $this->mensalidadeDoContrato($proposta['contrato_id'] ?? null);
        $percent = (float) ($proposta['comissao_percent'] ?? 0);

        return [
            'mensalidade' => $mensalidade,
            'percent'     => $percent,
            'valor'       => round($mensalidade * $percent / 100, 2),
        ];
    }

    /**
     * O desempenho por vendedor.
     *
     * A comissão é calculada aqui, contrato a contrato, e não somada de uma
     * coluna gravada: é a mensalidade de hoje vezes o percentual congelado
     * no fechamento.
     */
    public function desempenho(array $f = []): array
    {
        $params = [];
        $sql = 'SELECT v.id, v.nome AS vendedor, v.ativo, v.comissao_percent AS percent_atual,
                       e.nome AS empresa,
                       p.id AS proposta_id, p.status, p.vidas, p.contrato_id,
                       p.comissao_percent, p.data_fechamento
                  FROM vd_vendedores v
                  JOIN vd_empresas e ON e.id = v.empresa_id
                  LEFT JOIN vd_propostas p ON p.vendedor_id = v.id';

        $onde = [];
        if (!empty($f['vendedor_id'])) {
            $onde[] = 'v.id = ?';
            $params[] = (int) $f['vendedor_id'];
        }
        if (!empty($f['de'])) {
            $onde[] = '(p.id IS NULL OR p.data_proposta >= ?)';
            $params[] = $f['de'];
        }
        if (!empty($f['ate'])) {
            $onde[] = '(p.id IS NULL OR p.data_proposta <= ?)';
            $params[] = $f['ate'];
        }
        if ($onde) {
            $sql .= ' WHERE ' . implode(' AND ', $onde);
        }

        $sql .= ' ORDER BY e.nome, v.nome';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $porVendedor = [];

        foreach ($stmt->fetchAll() as $l) {
            $id = (int) $l['id'];

            if (!isset($porVendedor[$id])) {
                $porVendedor[$id] = [
                    'id'            => $id,
                    'vendedor'      => $l['vendedor'],
                    'empresa'       => $l['empresa'],
                    'ativo'         => (int) $l['ativo'],
                    'percent_atual' => (float) $l['percent_atual'],
                    'propostas'     => 0,
                    'fechadas'      => 0,
                    'perdidas'      => 0,
                    'em_negociacao' => 0,
                    'vidas'         => 0,
                    'mensalidade'   => 0.0,
                    'comissao'      => 0.0,
                ];
            }

            if (!$l['proposta_id']) {
                continue;
            }

            $v = &$porVendedor[$id];
            $v['propostas']++;

            if ($l['status'] === 'fechada') {
                $v['fechadas']++;
                $v['vidas'] += (int) $l['vidas'];

                $mensalidade = $this->mensalidadeDoContrato($l['contrato_id'] ? (int) $l['contrato_id'] : null);
                $v['mensalidade'] += $mensalidade;
                $v['comissao'] += round($mensalidade * (float) $l['comissao_percent'] / 100, 2);
            } elseif ($l['status'] === 'perdida') {
                $v['perdidas']++;
            } else {
                $v['em_negociacao']++;
            }

            unset($v);
        }

        return array_values($porVendedor);
    }

    /** Para o <select> do formulário. */
    public function vendedoresParaEscolha(): array
    {
        return $this->pdo->query(
            'SELECT v.id, v.nome, v.comissao_percent, e.nome AS empresa
               FROM vd_vendedores v
               JOIN vd_empresas e ON e.id = v.empresa_id
              WHERE v.ativo = 1
              ORDER BY e.nome, v.nome'
        )->fetchAll();
    }

    /** Usuários do sistema, para amarrar o login de um vendedor. */
    public function usuarios(): array
    {
        return $this->pdo->query(
            'SELECT id, nome FROM usuarios WHERE ativo = 1 ORDER BY nome'
        )->fetchAll();
    }
}
