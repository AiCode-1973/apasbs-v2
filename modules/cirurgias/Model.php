<?php
declare(strict_types=1);

class CirurgiaModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = getPDO();
    }

    public function listar(string $busca = '', int $especialidadeId = 0): array
    {
        $sql = 'SELECT c.*, e.nome AS especialidade_nome, COUNT(a.id) AS total_anexos
                  FROM cirurgias c
                  JOIN especialidades e ON e.id = c.especialidade_id
                  LEFT JOIN cirurgias_anexos a ON a.cirurgia_id = c.id';

        $where  = [];
        $params = [];

        if ($busca !== '') {
            $where[] = "(c.protocolo LIKE :b1 ESCAPE '!' OR c.medico LIKE :b2 ESCAPE '!'"
                     . " OR c.tuss_termo LIKE :b3 ESCAPE '!' OR c.tuss_codigo LIKE :b4 ESCAPE '!')";
            $like = '%' . like_escape($busca) . '%';
            $params[':b1'] = $like;
            $params[':b2'] = $like;
            $params[':b3'] = $like;
            $params[':b4'] = $like;
        }

        if ($especialidadeId > 0) {
            $where[] = 'c.especialidade_id = :esp';
            $params[':esp'] = $especialidadeId;
        }

        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' GROUP BY c.id ORDER BY c.created_at DESC';

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $chave => $valor) {
            $stmt->bindValue($chave, $valor, $chave === ':esp' ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function buscarPorId(int $id): array|false
    {
        $stmt = $this->pdo->prepare(
            'SELECT c.*, e.nome AS especialidade_nome
               FROM cirurgias c
               JOIN especialidades e ON e.id = c.especialidade_id
              WHERE c.id = ?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function criar(array $dados): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO cirurgias (protocolo, tuss_codigo, tuss_termo, medico, especialidade_id)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $dados['protocolo'],
            $dados['tuss_codigo'],
            $dados['tuss_termo'],
            $dados['medico'],
            $dados['especialidade_id'],
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function atualizar(int $id, array $dados): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE cirurgias
                SET protocolo = ?, tuss_codigo = ?, tuss_termo = ?,
                    medico = ?, especialidade_id = ?
              WHERE id = ?'
        );
        return $stmt->execute([
            $dados['protocolo'],
            $dados['tuss_codigo'],
            $dados['tuss_termo'],
            $dados['medico'],
            $dados['especialidade_id'],
            $id,
        ]);
    }

    public function excluir(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM cirurgias WHERE id = ?');
        return $stmt->execute([$id]);
    }

    public function protocoloExiste(string $protocolo, int $excludeId = 0): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM cirurgias WHERE protocolo = ? AND id <> ?');
        $stmt->execute([$protocolo, $excludeId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function especialidadeExiste(int $id): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM especialidades WHERE id = ? AND ativo = 1');
        $stmt->execute([$id]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function listarEspecialidades(): array
    {
        return $this->pdo->query(
            'SELECT id, nome FROM especialidades WHERE ativo = 1 ORDER BY nome'
        )->fetchAll();
    }

    // ── Anexos ────────────────────────────────────────────────────────────

    public function listarAnexos(int $cirurgiaId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM cirurgias_anexos WHERE cirurgia_id = ? ORDER BY created_at, id'
        );
        $stmt->execute([$cirurgiaId]);
        return $stmt->fetchAll();
    }

    public function inserirAnexo(int $cirurgiaId, array $anexo): bool
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO cirurgias_anexos (cirurgia_id, nome_orig, nome_arq, mime, tamanho)
             VALUES (?, ?, ?, ?, ?)'
        );
        return $stmt->execute([
            $cirurgiaId,
            $anexo['nome_orig'],
            $anexo['nome_arq'],
            $anexo['mime'],
            $anexo['tamanho'],
        ]);
    }

    public function buscarAnexo(int $id): array|false
    {
        $stmt = $this->pdo->prepare('SELECT * FROM cirurgias_anexos WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function excluirAnexo(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM cirurgias_anexos WHERE id = ?');
        return $stmt->execute([$id]);
    }

    public function contarAnexos(int $cirurgiaId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM cirurgias_anexos WHERE cirurgia_id = ?');
        $stmt->execute([$cirurgiaId]);
        return (int) $stmt->fetchColumn();
    }
}
