<?php
declare(strict_types=1);

class ManualModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = getPDO();
    }

    /**
     * @param bool $sohAtivos true para a biblioteca (quem só lê),
     *                        false para a tela de quem administra.
     */
    public function listar(string $busca = '', bool $sohAtivos = false): array
    {
        $sql    = 'SELECT * FROM ajuda_manuais';
        $onde   = [];
        $params = [];

        if ($sohAtivos) {
            $onde[] = 'ativo = 1';
        }

        if ($busca !== '') {
            $onde[] = "(titulo LIKE ? ESCAPE '!' OR descricao LIKE ? ESCAPE '!' OR categoria LIKE ? ESCAPE '!')";
            $like = '%' . like_escape($busca) . '%';
            $params = [$like, $like, $like];
        }

        if ($onde) {
            $sql .= ' WHERE ' . implode(' AND ', $onde);
        }

        $sql .= ' ORDER BY ativo DESC, categoria, ordem, titulo';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Categorias já usadas, para sugerir no formulário. */
    public function categorias(): array
    {
        $linhas = $this->pdo->query(
            'SELECT DISTINCT categoria FROM ajuda_manuais ORDER BY categoria'
        )->fetchAll();

        return array_column($linhas, 'categoria');
    }

    public function buscarPorId(int $id): array|false
    {
        $stmt = $this->pdo->prepare('SELECT * FROM ajuda_manuais WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function criar(array $d): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO ajuda_manuais
                (titulo, descricao, categoria, nome_arq, nome_orig, mime, tamanho,
                 ordem, ativo, usuario_id, usuario_nome)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $d['titulo'],
            $d['descricao'] !== '' ? $d['descricao'] : null,
            $d['categoria'],
            $d['nome_arq'], $d['nome_orig'], $d['mime'], $d['tamanho'],
            $d['ordem'], $d['ativo'],
            Auth::id(), (string) Auth::nome(),
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /** Atualiza os dados da ficha. O arquivo só muda por trocarArquivo(). */
    public function atualizar(int $id, array $d): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE ajuda_manuais
                SET titulo = ?, descricao = ?, categoria = ?, ordem = ?, ativo = ?
              WHERE id = ?'
        );
        return $stmt->execute([
            $d['titulo'],
            $d['descricao'] !== '' ? $d['descricao'] : null,
            $d['categoria'], $d['ordem'], $d['ativo'], $id,
        ]);
    }

    public function trocarArquivo(int $id, array $d): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE ajuda_manuais
                SET nome_arq = ?, nome_orig = ?, mime = ?, tamanho = ?,
                    usuario_id = ?, usuario_nome = ?
              WHERE id = ?'
        );
        return $stmt->execute([
            $d['nome_arq'], $d['nome_orig'], $d['mime'], $d['tamanho'],
            Auth::id(), (string) Auth::nome(), $id,
        ]);
    }

    public function excluir(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM ajuda_manuais WHERE id = ?');
        return $stmt->execute([$id]);
    }

    /** Próxima ordem livre dentro da categoria, com folga de 10. */
    public function proximaOrdem(string $categoria): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COALESCE(MAX(ordem), 0) FROM ajuda_manuais WHERE categoria = ?'
        );
        $stmt->execute([$categoria]);
        return (int) $stmt->fetchColumn() + 10;
    }
}
