<?php
declare(strict_types=1);

/**
 * OPME — órteses, próteses e materiais especiais.
 *
 * O que a planilha chamava de linha é, no sistema, duas coisas: a NOTA
 * FISCAL (paciente, cirurgia, fornecedor, valor) e as PARCELAS dela. A
 * planilha repetia a nota inteira em cada mês em que caía uma parcela —
 * 630 linhas para 265 notas.
 */
class OpmeModel
{
    public const SITUACOES = [
        'normal'    => 'Normal',
        'cancelada' => 'NF cancelada',
        'doacao'    => 'Doação',
    ];

    /** Hospitais que aparecem na planilha — o campo aceita outros. */
    public const HOSPITAIS = ['HSE', 'São Lucas Santos', 'Santa Casa'];

    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = getPDO();
    }

    // ── Fornecedores ──────────────────────────────────────────────────────

    public function fornecedores(bool $somenteAtivos = true, string $busca = ''): array
    {
        $sql = 'SELECT f.*,
                       (SELECT COUNT(*) FROM opme_notas n WHERE n.fornecedor_id = f.id) AS qtd_notas,
                       (SELECT COALESCE(SUM(n.valor_total), 0) FROM opme_notas n
                         WHERE n.fornecedor_id = f.id AND n.situacao = \'normal\') AS valor_notas,
                       (SELECT COALESCE(SUM(p.valor), 0)
                          FROM opme_parcelas p
                          JOIN opme_notas n ON n.id = p.nota_id
                         WHERE n.fornecedor_id = f.id AND n.situacao = \'normal\' AND p.pago = 0) AS a_pagar
                  FROM opme_fornecedores f';
        $where  = [];
        $params = [];

        if ($somenteAtivos) {
            $where[] = 'f.ativo = 1';
        }
        if ($busca !== '') {
            $where[] = "(f.nome LIKE :b0 ESCAPE '!' OR f.cnpj LIKE :b1 ESCAPE '!' OR f.contato LIKE :b2 ESCAPE '!')";
            $like = '%' . like_escape($busca) . '%';
            $params[':b0'] = $like;
            $params[':b1'] = $like;
            $params[':b2'] = $like;
        }
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY f.ativo DESC, f.nome';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function fornecedor(int $id): array|false
    {
        $stmt = $this->pdo->prepare('SELECT * FROM opme_fornecedores WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function salvarFornecedor(array $d, int $id = 0): int
    {
        $campos = [
            $d['nome'], $d['cnpj'] ?: null, $d['contato'] ?: null,
            $d['telefone'] ?: null, $d['email'] ?: null,
            $d['observacao'] ?: null, $d['ativo'],
        ];

        if ($id > 0) {
            $stmt = $this->pdo->prepare(
                'UPDATE opme_fornecedores
                    SET nome = ?, cnpj = ?, contato = ?, telefone = ?, email = ?, observacao = ?, ativo = ?
                  WHERE id = ?'
            );
            $stmt->execute([...$campos, $id]);
            return $id;
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO opme_fornecedores (nome, cnpj, contato, telefone, email, observacao, ativo)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute($campos);
        return (int) $this->pdo->lastInsertId();
    }

    public function excluirFornecedor(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM opme_fornecedores WHERE id = ?');
        return $stmt->execute([$id]);
    }

    public function fornecedorNomeExiste(string $nome, int $excluir = 0): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM opme_fornecedores WHERE nome = ? AND id <> ?');
        $stmt->execute([$nome, $excluir]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /** Fornecedor com nota lançada não pode ser excluído. */
    public function fornecedorTemNota(int $id): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM opme_notas WHERE fornecedor_id = ?');
        $stmt->execute([$id]);
        return (int) $stmt->fetchColumn() > 0;
    }

    // ── Notas ─────────────────────────────────────────────────────────────

    /**
     * Lista de notas com o resumo das parcelas de cada uma. As somas saem em
     * subconsulta e não em JOIN: com JOIN, uma nota de quatro parcelas
     * multiplicaria o valor total por quatro.
     */
    public function notas(array $filtro = [], int $limite = 100, int $offset = 0): array
    {
        [$where, $params] = $this->filtroNotas($filtro);

        $sql = 'SELECT n.*, f.nome AS fornecedor,
                       (SELECT COUNT(*)                FROM opme_parcelas p WHERE p.nota_id = n.id) AS parcelas,
                       (SELECT COALESCE(SUM(p.valor),0) FROM opme_parcelas p WHERE p.nota_id = n.id) AS soma_parcelas,
                       (SELECT COALESCE(SUM(p.valor),0) FROM opme_parcelas p WHERE p.nota_id = n.id AND p.pago = 1) AS pago,
                       (SELECT MIN(p.vencimento) FROM opme_parcelas p WHERE p.nota_id = n.id AND p.pago = 0) AS proximo_venc
                  FROM opme_notas n
                  JOIN opme_fornecedores f ON f.id = n.fornecedor_id'
             . $where
             . ' ORDER BY n.data_cirurgia DESC, n.id DESC
                 LIMIT ' . max(1, $limite) . ' OFFSET ' . max(0, $offset);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function contarNotas(array $filtro = []): int
    {
        [$where, $params] = $this->filtroNotas($filtro);

        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM opme_notas n JOIN opme_fornecedores f ON f.id = n.fornecedor_id' . $where
        );
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Totais do filtro atual, para o rodapé da lista.
     *
     * São duas consultas e não uma: o mesmo WHERE precisaria aparecer duas
     * vezes na mesma instrução, e com prepares nativos um placeholder
     * nomeado não pode se repetir.
     */
    public function totaisNotas(array $filtro = []): array
    {
        [$where, $params] = $this->filtroNotas($filtro);

        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) AS notas, COALESCE(SUM(n.valor_total), 0) AS valor_total
               FROM opme_notas n
               JOIN opme_fornecedores f ON f.id = n.fornecedor_id' . $where
        );
        $stmt->execute($params);
        $totais = $stmt->fetch() ?: ['notas' => 0, 'valor_total' => 0];

        $stmt = $this->pdo->prepare(
            "SELECT COALESCE(SUM(p.valor), 0)
               FROM opme_parcelas p
               JOIN opme_notas n        ON n.id = p.nota_id
               JOIN opme_fornecedores f ON f.id = n.fornecedor_id"
            . ($where !== '' ? $where . ' AND ' : ' WHERE ')
            . "p.pago = 0 AND n.situacao = 'normal'"
        );
        $stmt->execute($params);
        $totais['a_pagar'] = (float) $stmt->fetchColumn();

        return $totais;
    }

    private function filtroNotas(array $f): array
    {
        $where  = [];
        $params = [];

        if (!empty($f['busca'])) {
            $where[] = "(n.paciente LIKE :b0 ESCAPE '!' OR n.numero_nf LIKE :b1 ESCAPE '!'
                         OR n.medico LIKE :b2 ESCAPE '!' OR f.nome LIKE :b3 ESCAPE '!')";
            $like = '%' . like_escape((string) $f['busca']) . '%';
            $params[':b0'] = $like;
            $params[':b1'] = $like;
            $params[':b2'] = $like;
            $params[':b3'] = $like;
        }

        if (!empty($f['fornecedor_id'])) {
            $where[] = 'n.fornecedor_id = :forn';
            $params[':forn'] = (int) $f['fornecedor_id'];
        }

        if (!empty($f['situacao']) && isset(self::SITUACOES[$f['situacao']])) {
            $where[] = 'n.situacao = :sit';
            $params[':sit'] = $f['situacao'];
        }

        if (!empty($f['ano'])) {
            $where[] = 'YEAR(n.data_cirurgia) = :ano';
            $params[':ano'] = (int) $f['ano'];
        }

        if (!empty($f['em_aberto'])) {
            $where[] = 'EXISTS (SELECT 1 FROM opme_parcelas p WHERE p.nota_id = n.id AND p.pago = 0)';
        }

        return [$where ? ' WHERE ' . implode(' AND ', $where) : '', $params];
    }

    public function nota(int $id): array|false
    {
        $stmt = $this->pdo->prepare(
            'SELECT n.*, f.nome AS fornecedor
               FROM opme_notas n JOIN opme_fornecedores f ON f.id = n.fornecedor_id
              WHERE n.id = ?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function salvarNota(array $d, int $id = 0): int
    {
        $campos = [
            $d['fornecedor_id'], $d['numero_nf'], $d['paciente'],
            $d['data_cirurgia'] ?: null, $d['hospital'] ?: null, $d['medico'] ?: null,
            $d['valor_total'], $d['data_nf'] ?: null, $d['total_parcelas'],
            $d['situacao'], $d['encaminhado_hse'], $d['observacao'] ?: null,
        ];

        if ($id > 0) {
            $stmt = $this->pdo->prepare(
                'UPDATE opme_notas
                    SET fornecedor_id = ?, numero_nf = ?, paciente = ?, data_cirurgia = ?,
                        hospital = ?, medico = ?, valor_total = ?, data_nf = ?,
                        total_parcelas = ?, situacao = ?, encaminhado_hse = ?, observacao = ?
                  WHERE id = ?'
            );
            $stmt->execute([...$campos, $id]);
            return $id;
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO opme_notas
                (fornecedor_id, numero_nf, paciente, data_cirurgia, hospital, medico,
                 valor_total, data_nf, total_parcelas, situacao, encaminhado_hse, observacao, usuario_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([...$campos, Auth::id()]);
        return (int) $this->pdo->lastInsertId();
    }

    public function excluirNota(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM opme_notas WHERE id = ?');
        return $stmt->execute([$id]);
    }

    public function notaExiste(int $fornecedorId, string $numero, string $paciente, int $excluir = 0): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM opme_notas
              WHERE fornecedor_id = ? AND numero_nf = ? AND paciente = ? AND id <> ?'
        );
        $stmt->execute([$fornecedorId, $numero, $paciente, $excluir]);
        return (int) $stmt->fetchColumn() > 0;
    }

    // ── Parcelas ──────────────────────────────────────────────────────────

    public function parcelas(int $notaId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM opme_parcelas WHERE nota_id = ? ORDER BY vencimento, numero'
        );
        $stmt->execute([$notaId]);
        return $stmt->fetchAll();
    }

    public function parcela(int $id): array|false
    {
        $stmt = $this->pdo->prepare(
            'SELECT p.*, n.paciente, n.numero_nf, n.situacao, f.nome AS fornecedor
               FROM opme_parcelas p
               JOIN opme_notas n        ON n.id = p.nota_id
               JOIN opme_fornecedores f ON f.id = n.fornecedor_id
              WHERE p.id = ?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Regrava as parcelas de uma nota. Vem tudo junto do formulário, então é
     * transação: ou a nota fica com o conjunto novo inteiro, ou continua com
     * o antigo. Parcela já paga mantém a baixa se o vencimento não mudou.
     */
    public function salvarParcelas(int $notaId, array $parcelas): void
    {
        $this->pdo->beginTransaction();
        try {
            $antigas = [];
            foreach ($this->parcelas($notaId) as $p) {
                $antigas[$p['vencimento']] = $p;
            }

            $this->pdo->prepare('DELETE FROM opme_parcelas WHERE nota_id = ?')->execute([$notaId]);

            $stmt = $this->pdo->prepare(
                'INSERT INTO opme_parcelas
                    (nota_id, numero, vencimento, valor, pago, data_pagamento, observacao, usuario_id)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );

            foreach ($parcelas as $p) {
                if (empty($p['vencimento'])) {
                    continue;
                }
                $antiga = $antigas[$p['vencimento']] ?? null;
                $stmt->execute([
                    $notaId,
                    $p['numero'],
                    $p['vencimento'],
                    $p['valor'],
                    $antiga['pago'] ?? 0,
                    $antiga['data_pagamento'] ?? null,
                    $antiga['observacao'] ?? null,
                    Auth::id(),
                ]);
            }

            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function marcarPaga(int $id, bool $paga, ?string $data = null): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE opme_parcelas SET pago = ?, data_pagamento = ? WHERE id = ?'
        );
        return $stmt->execute([$paga ? 1 : 0, $paga ? ($data ?: date('Y-m-d')) : null, $id]);
    }

    /**
     * Renumera as parcelas de uma nota pela ordem de vencimento.
     *
     * A planilha vinha com o rótulo errado em algumas notas — a linha era
     * copiada de um mês para o outro e o "3/4" ficava para trás. A data
     * sempre esteve certa, então é ela que manda.
     */
    public function renumerar(int $notaId): int
    {
        $parcelas = $this->parcelas($notaId);
        $stmt = $this->pdo->prepare('UPDATE opme_parcelas SET numero = ? WHERE id = ?');

        $mudou = 0;
        foreach (array_values($parcelas) as $i => $p) {
            $novo = $i + 1;
            if ((int) $p['numero'] !== $novo) {
                $stmt->execute([$novo, $p['id']]);
                $mudou++;
            }
        }
        return $mudou;
    }

    /** A numeração está fora de ordem? É o que acende o aviso na tela da nota. */
    public function numeracaoConfusa(array $parcelas): bool
    {
        $vistos = [];
        foreach (array_values($parcelas) as $i => $p) {
            $n = (int) $p['numero'];
            if (isset($vistos[$n]) || $n !== $i + 1) {
                return true;
            }
            $vistos[$n] = true;
        }
        return false;
    }

    // ── Vencimentos do mês ────────────────────────────────────────────────

    /**
     * A aba mensal da planilha, agora calculada: as parcelas que vencem no
     * mês, com os dados da nota junto.
     */
    public function vencimentos(int $ano, int $mes, array $filtro = []): array
    {
        $sql = 'SELECT p.*, n.paciente, n.data_cirurgia, n.hospital, n.medico,
                       n.numero_nf, n.valor_total, n.data_nf, n.total_parcelas,
                       n.situacao, n.encaminhado_hse, n.observacao AS nota_observacao,
                       f.nome AS fornecedor
                  FROM opme_parcelas p
                  JOIN opme_notas n        ON n.id = p.nota_id
                  JOIN opme_fornecedores f ON f.id = n.fornecedor_id
                 WHERE YEAR(p.vencimento) = :ano AND MONTH(p.vencimento) = :mes';
        $params = [':ano' => $ano, ':mes' => $mes];

        if (!empty($filtro['fornecedor_id'])) {
            $sql .= ' AND n.fornecedor_id = :forn';
            $params[':forn'] = (int) $filtro['fornecedor_id'];
        }
        if (!empty($filtro['somente_abertas'])) {
            $sql .= ' AND p.pago = 0';
        }

        $sql .= ' ORDER BY p.vencimento, f.nome, n.paciente';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Totais de um mês: o que vence, o que já foi pago e o que falta. */
    public function totaisMes(int $ano, int $mes): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) AS parcelas,
                    COALESCE(SUM(p.valor), 0) AS total,
                    COALESCE(SUM(CASE WHEN p.pago = 1 THEN p.valor ELSE 0 END), 0) AS pago,
                    COALESCE(SUM(CASE WHEN p.pago = 0 THEN p.valor ELSE 0 END), 0) AS aberto,
                    COUNT(DISTINCT n.fornecedor_id) AS fornecedores
               FROM opme_parcelas p
               JOIN opme_notas n ON n.id = p.nota_id
              WHERE YEAR(p.vencimento) = ? AND MONTH(p.vencimento) = ?
                AND n.situacao = 'normal'"
        );
        $stmt->execute([$ano, $mes]);
        return $stmt->fetch() ?: [];
    }

    /** Soma por fornecedor no mês — é assim que o pagamento é organizado. */
    public function porFornecedorNoMes(int $ano, int $mes): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT f.nome AS fornecedor, COUNT(*) AS parcelas,
                    SUM(p.valor) AS total,
                    SUM(CASE WHEN p.pago = 0 THEN p.valor ELSE 0 END) AS aberto
               FROM opme_parcelas p
               JOIN opme_notas n        ON n.id = p.nota_id
               JOIN opme_fornecedores f ON f.id = n.fornecedor_id
              WHERE YEAR(p.vencimento) = ? AND MONTH(p.vencimento) = ?
                AND n.situacao = 'normal'
              GROUP BY f.id, f.nome
              ORDER BY total DESC"
        );
        $stmt->execute([$ano, $mes]);
        return $stmt->fetchAll();
    }

    /** Meses que têm parcela — alimenta o seletor da tela de vencimentos. */
    public function mesesComParcela(): array
    {
        return $this->pdo->query(
            'SELECT DISTINCT YEAR(vencimento) AS ano, MONTH(vencimento) AS mes
               FROM opme_parcelas ORDER BY ano, mes'
        )->fetchAll();
    }

    public function anosDeCirurgia(): array
    {
        return $this->pdo->query(
            'SELECT DISTINCT YEAR(data_cirurgia) AS ano FROM opme_notas
              WHERE data_cirurgia IS NOT NULL ORDER BY ano DESC'
        )->fetchAll(PDO::FETCH_COLUMN);
    }

    /** Nomes já usados nos campos livres, para o datalist do formulário. */
    public function medicos(): array
    {
        return $this->pdo->query(
            "SELECT DISTINCT medico FROM opme_notas
              WHERE medico IS NOT NULL AND medico <> '' ORDER BY medico"
        )->fetchAll(PDO::FETCH_COLUMN);
    }

    public function hospitais(): array
    {
        $doBanco = $this->pdo->query(
            "SELECT DISTINCT hospital FROM opme_notas
              WHERE hospital IS NOT NULL AND hospital <> '' ORDER BY hospital"
        )->fetchAll(PDO::FETCH_COLUMN);

        return array_values(array_unique(array_merge(self::HOSPITAIS, $doBanco)));
    }

    // ── Painel ────────────────────────────────────────────────────────────

    /** Números do topo da lista: o retrato do que está em aberto. */
    public function panorama(): array
    {
        $hoje = date('Y-m-d');

        $stmt = $this->pdo->prepare(
            "SELECT
                (SELECT COUNT(*) FROM opme_notas WHERE situacao = 'normal') AS notas,
                (SELECT COALESCE(SUM(p.valor), 0) FROM opme_parcelas p
                   JOIN opme_notas n ON n.id = p.nota_id
                  WHERE p.pago = 0 AND n.situacao = 'normal') AS a_pagar,
                (SELECT COALESCE(SUM(p.valor), 0) FROM opme_parcelas p
                   JOIN opme_notas n ON n.id = p.nota_id
                  WHERE p.pago = 0 AND p.vencimento < ? AND n.situacao = 'normal') AS vencido,
                (SELECT COUNT(*) FROM opme_parcelas p
                   JOIN opme_notas n ON n.id = p.nota_id
                  WHERE p.pago = 0 AND p.vencimento < ? AND n.situacao = 'normal') AS qtd_vencida"
        );
        $stmt->execute([$hoje, $hoje]);
        return $stmt->fetch() ?: [];
    }
}
