<?php
declare(strict_types=1);

class FechamentoModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = getPDO();
    }

    /**
     * Fechamentos ordenados do mais recente para o mais antigo.
     * Traz o total do mês anterior na mesma linha, para a lista poder mostrar
     * a variação sem uma segunda consulta por registro.
     */
    public function listar(int $ano = 0): array
    {
        $sql = 'SELECT f.*, u.nome AS usuario_nome,
                       (SELECT a.total_atendimentos
                          FROM central_fechamentos a
                         WHERE (a.ano * 12 + a.mes) = (f.ano * 12 + f.mes) - 1
                         LIMIT 1) AS total_anterior,
                       (SELECT COALESCE(SUM(fm.quantidade), 0)
                          FROM central_fechamento_motivos fm
                         WHERE fm.fechamento_id = f.id) AS soma_motivos,
                       (SELECT COUNT(*)
                          FROM central_fechamento_motivos fm
                         WHERE fm.fechamento_id = f.id AND fm.quantidade > 0) AS qtd_motivos
                  FROM central_fechamentos f
                  LEFT JOIN usuarios u ON u.id = f.usuario_id';

        if ($ano > 0) {
            $sql .= ' WHERE f.ano = :ano';
        }

        $sql .= ' ORDER BY f.ano DESC, f.mes DESC';

        $stmt = $this->pdo->prepare($sql);
        if ($ano > 0) {
            $stmt->bindValue(':ano', $ano, PDO::PARAM_INT);
        }
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /** Anos que já têm lançamento, para alimentar o filtro. */
    public function anosLancados(): array
    {
        return array_map(
            'intval',
            $this->pdo->query('SELECT DISTINCT ano FROM central_fechamentos ORDER BY ano DESC')
                      ->fetchAll(PDO::FETCH_COLUMN)
        );
    }

    public function buscarPorId(int $id): array|false
    {
        $stmt = $this->pdo->prepare('SELECT * FROM central_fechamentos WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function criar(array $dados): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO central_fechamentos (ano, mes, total_atendimentos, observacao, usuario_id)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $dados['ano'],
            $dados['mes'],
            $dados['total_atendimentos'],
            $dados['observacao'] !== '' ? $dados['observacao'] : null,
            Auth::id(),
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function atualizar(int $id, array $dados): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE central_fechamentos
                SET ano = ?, mes = ?, total_atendimentos = ?, observacao = ?, usuario_id = ?
              WHERE id = ?'
        );
        return $stmt->execute([
            $dados['ano'],
            $dados['mes'],
            $dados['total_atendimentos'],
            $dados['observacao'] !== '' ? $dados['observacao'] : null,
            Auth::id(),
            $id,
        ]);
    }

    public function excluir(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM central_fechamentos WHERE id = ?');
        return $stmt->execute([$id]);
    }

    /** A competência já foi lançada? (ignora o próprio registro na edição) */
    public function competenciaExiste(int $ano, int $mes, int $excludeId = 0): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM central_fechamentos WHERE ano = ? AND mes = ? AND id <> ?'
        );
        $stmt->execute([$ano, $mes, $excludeId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /** Soma dos atendimentos de um ano (ou de todos, quando ano = 0). */
    public function totalDoAno(int $ano = 0): int
    {
        if ($ano > 0) {
            $stmt = $this->pdo->prepare('SELECT COALESCE(SUM(total_atendimentos), 0) FROM central_fechamentos WHERE ano = ?');
            $stmt->execute([$ano]);
            return (int) $stmt->fetchColumn();
        }

        return (int) $this->pdo->query('SELECT COALESCE(SUM(total_atendimentos), 0) FROM central_fechamentos')->fetchColumn();
    }

    // ── Quantidades por motivo ────────────────────────────────────────────

    /**
     * Motivos que o formulário deve exibir: todos os ativos, mais os inativos
     * que já tenham quantidade lançada neste fechamento (senão o número
     * sumiria da tela e seria apagado sem o operador perceber).
     */
    public function motivosDoFormulario(int $fechamentoId = 0): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT m.id, m.codigo, m.nome, m.descricao, m.ativo
               FROM central_motivos m
              WHERE m.ativo = 1
                 OR EXISTS (SELECT 1 FROM central_fechamento_motivos fm
                             WHERE fm.motivo_id = m.id AND fm.fechamento_id = :fech)
              ORDER BY m.codigo'
        );
        $stmt->bindValue(':fech', $fechamentoId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /** Quantidades lançadas, indexadas por motivo_id. */
    public function quantidades(int $fechamentoId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT motivo_id, quantidade FROM central_fechamento_motivos WHERE fechamento_id = ?'
        );
        $stmt->execute([$fechamentoId]);

        $mapa = [];
        foreach ($stmt->fetchAll() as $linha) {
            $mapa[(int) $linha['motivo_id']] = (int) $linha['quantidade'];
        }
        return $mapa;
    }

    /** Detalhamento do fechamento com o nome de cada motivo, para leitura. */
    public function detalhamento(int $fechamentoId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT m.codigo, m.nome, fm.quantidade
               FROM central_fechamento_motivos fm
               JOIN central_motivos m ON m.id = fm.motivo_id
              WHERE fm.fechamento_id = ? AND fm.quantidade > 0
              ORDER BY fm.quantidade DESC, m.codigo'
        );
        $stmt->execute([$fechamentoId]);
        return $stmt->fetchAll();
    }

    /**
     * Substitui as quantidades do fechamento. Motivo zerado não vira linha —
     * a tabela guarda só o que foi de fato lançado.
     */
    public function salvarQuantidades(int $fechamentoId, array $quantidades): void
    {
        $validos = array_map(
            'intval',
            $this->pdo->query('SELECT id FROM central_motivos')->fetchAll(PDO::FETCH_COLUMN)
        );

        $this->pdo->beginTransaction();

        try {
            $del = $this->pdo->prepare('DELETE FROM central_fechamento_motivos WHERE fechamento_id = ?');
            $del->execute([$fechamentoId]);

            $ins = $this->pdo->prepare(
                'INSERT INTO central_fechamento_motivos (fechamento_id, motivo_id, quantidade)
                 VALUES (?, ?, ?)'
            );

            foreach ($quantidades as $motivoId => $qtd) {
                $motivoId = (int) $motivoId;
                $qtd      = (int) $qtd;

                if ($qtd <= 0 || !in_array($motivoId, $validos, true)) {
                    continue;
                }

                $ins->execute([$fechamentoId, $motivoId, $qtd]);
            }

            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /** Último fechamento lançado — usado como referência no formulário. */
    public function ultimo(): array|false
    {
        return $this->pdo->query(
            'SELECT * FROM central_fechamentos ORDER BY ano DESC, mes DESC LIMIT 1'
        )->fetch();
    }
}
