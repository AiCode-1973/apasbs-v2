<?php
declare(strict_types=1);

class MotivoModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = getPDO();
    }

    public function listar(string $busca = ''): array
    {
        $sql = 'SELECT * FROM central_motivos';
        $params = [];

        if ($busca !== '') {
            $sql .= " WHERE (codigo LIKE ? ESCAPE '!' OR nome LIKE ? ESCAPE '!' OR descricao LIKE ? ESCAPE '!')";
            $like = '%' . like_escape($busca) . '%';
            $params = [$like, $like, $like];
        }

        $sql .= ' ORDER BY codigo';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function buscarPorId(int $id): array|false
    {
        $stmt = $this->pdo->prepare('SELECT * FROM central_motivos WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function criar(array $dados): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO central_motivos (codigo, nome, descricao, ativo) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([
            $dados['codigo'],
            $dados['nome'],
            $dados['descricao'] !== '' ? $dados['descricao'] : null,
            $dados['ativo'],
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function atualizar(int $id, array $dados): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE central_motivos SET codigo = ?, nome = ?, descricao = ?, ativo = ? WHERE id = ?'
        );
        return $stmt->execute([
            $dados['codigo'],
            $dados['nome'],
            $dados['descricao'] !== '' ? $dados['descricao'] : null,
            $dados['ativo'],
            $id,
        ]);
    }

    public function excluir(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM central_motivos WHERE id = ?');
        return $stmt->execute([$id]);
    }

    /** O motivo já foi usado em algum fechamento? */
    public function possuiLancamentos(int $id): bool
    {
        try {
            $stmt = $this->pdo->prepare(
                'SELECT COUNT(*) FROM central_fechamento_motivos WHERE motivo_id = ?'
            );
            $stmt->execute([$id]);
            return (int) $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            // Tabela ainda não criada: nada a bloquear
            error_log('Motivos::possuiLancamentos — ' . $e->getMessage());
            return false;
        }
    }

    public function codigoExiste(string $codigo, int $excludeId = 0): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM central_motivos WHERE codigo = ? AND id <> ?');
        $stmt->execute([$codigo, $excludeId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function nomeExiste(string $nome, int $excludeId = 0): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM central_motivos WHERE nome = ? AND id <> ?');
        $stmt->execute([$nome, $excludeId]);
        return (int) $stmt->fetchColumn() > 0;
    }
}
