<?php
declare(strict_types=1);

class SetorModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = getPDO();
    }

    public function listar(): array
    {
        return $this->pdo->query(
            'SELECT s.*, COUNT(u.id) AS total_usuarios
               FROM setores s
               LEFT JOIN usuarios u ON u.setor_id = s.id
              GROUP BY s.id
              ORDER BY s.nome'
        )->fetchAll();
    }

    public function buscarPorId(int $id): array|false
    {
        $stmt = $this->pdo->prepare('SELECT * FROM setores WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function criar(array $dados): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO setores (nome, ativo) VALUES (?, ?)');
        $stmt->execute([$dados['nome'], $dados['ativo']]);
        return (int) $this->pdo->lastInsertId();
    }

    public function atualizar(int $id, array $dados): bool
    {
        $stmt = $this->pdo->prepare('UPDATE setores SET nome = ?, ativo = ? WHERE id = ?');
        return $stmt->execute([$dados['nome'], $dados['ativo'], $id]);
    }

    public function excluir(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM setores WHERE id = ?');
        return $stmt->execute([$id]);
    }

    public function possuiUsuarios(int $id): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM usuarios WHERE setor_id = ?');
        $stmt->execute([$id]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function nomeExiste(string $nome, int $excludeId = 0): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM setores WHERE nome = ? AND id <> ?');
        $stmt->execute([$nome, $excludeId]);
        return (int) $stmt->fetchColumn() > 0;
    }
}
