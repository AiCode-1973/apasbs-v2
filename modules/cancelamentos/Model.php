<?php
declare(strict_types=1);

/**
 * Mensagens que a atendente escolhe ao desmarcar um exame.
 *
 * Nada a ver com os "Motivos de fechamento de atendimento" (central_motivos),
 * que pertencem ao módulo de Fechamentos da Central.
 */
class CancelamentoMotivoModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = getPDO();
    }

    public function listar(string $busca = ''): array
    {
        $sql = 'SELECT m.*,
                       (SELECT COUNT(*) FROM central_agenda_marcacoes ma
                         WHERE ma.motivo_id = m.id) AS qtd_usos
                  FROM central_cancelamento_motivos m';
        $params = [];

        if ($busca !== '') {
            $sql .= " WHERE m.nome LIKE ? ESCAPE '!'";
            $params[] = '%' . like_escape($busca) . '%';
        }

        $sql .= ' ORDER BY m.ativo DESC, m.ordem, m.nome';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Só as mensagens ativas, na ordem de exibição — é o que a atendente vê. */
    public function ativos(): array
    {
        return $this->pdo->query(
            'SELECT id, nome FROM central_cancelamento_motivos
              WHERE ativo = 1 ORDER BY ordem, nome'
        )->fetchAll();
    }

    public function buscarPorId(int $id): array|false
    {
        $stmt = $this->pdo->prepare('SELECT * FROM central_cancelamento_motivos WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function criar(array $dados): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO central_cancelamento_motivos (nome, ordem, ativo, usuario_id)
             VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$dados['nome'], $dados['ordem'], $dados['ativo'], Auth::id()]);
        return (int) $this->pdo->lastInsertId();
    }

    public function atualizar(int $id, array $dados): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE central_cancelamento_motivos SET nome = ?, ordem = ?, ativo = ? WHERE id = ?'
        );
        return $stmt->execute([$dados['nome'], $dados['ordem'], $dados['ativo'], $id]);
    }

    public function excluir(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM central_cancelamento_motivos WHERE id = ?');
        return $stmt->execute([$id]);
    }

    /** A mensagem já foi usada em algum cancelamento? */
    public function possuiUsos(int $id): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM central_agenda_marcacoes WHERE motivo_id = ?'
        );
        $stmt->execute([$id]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function nomeExiste(string $nome, int $excludeId = 0): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM central_cancelamento_motivos WHERE nome = ? AND id <> ?'
        );
        $stmt->execute([$nome, $excludeId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /** Próxima ordem livre, deixando folga de 10 para encaixar no meio depois. */
    public function proximaOrdem(): int
    {
        $max = (int) $this->pdo->query(
            'SELECT COALESCE(MAX(ordem), 0) FROM central_cancelamento_motivos'
        )->fetchColumn();

        return $max + 10;
    }
}
