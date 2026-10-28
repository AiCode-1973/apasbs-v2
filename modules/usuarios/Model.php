<?php
declare(strict_types=1);

class UsuarioModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = getPDO();
    }

    public function buscarPorCpf(string $cpf): array|false
    {
        $stmt = $this->pdo->prepare(
            'SELECT u.*, s.nome AS setor_nome
               FROM usuarios u
               JOIN setores s ON s.id = u.setor_id
              WHERE u.cpf = ?
                AND u.ativo = 1'
        );
        $stmt->execute([$cpf]);
        return $stmt->fetch();
    }

    public function listar(string $busca = ''): array
    {
        $sql = 'SELECT u.id, u.nome, u.cpf, u.ativo, u.is_admin, u.senha_provisoria,
                       u.senha_alterada_em, u.created_at, s.nome AS setor_nome
                  FROM usuarios u
                  JOIN setores s ON s.id = u.setor_id';

        $params = [];
        if ($busca !== '') {
            $sql .= " WHERE (u.nome LIKE ? ESCAPE '!' OR u.cpf LIKE ? ESCAPE '!' OR s.nome LIKE ? ESCAPE '!')";
            $like = '%' . like_escape($busca) . '%';
            $params = [$like, $like, $like];
        }

        $sql .= ' ORDER BY u.nome';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function buscarPorId(int $id): array|false
    {
        $stmt = $this->pdo->prepare('SELECT * FROM usuarios WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function criar(array $dados): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO usuarios (nome, cpf, senha, senha_provisoria, setor_id, ativo, is_admin)
             VALUES (?, ?, ?, 1, ?, ?, ?)'
        );
        $stmt->execute([
            $dados['nome'],
            $dados['cpf'],
            password_hash($dados['senha'], PASSWORD_BCRYPT),
            $dados['setor_id'],
            $dados['ativo'],
            $dados['is_admin'],
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function atualizar(int $id, array $dados): bool
    {
        if (!empty($dados['senha'])) {
            $stmt = $this->pdo->prepare(
                'UPDATE usuarios
                    SET nome = ?, cpf = ?, senha = ?, senha_provisoria = 1,
                        setor_id = ?, ativo = ?, is_admin = ?
                  WHERE id = ?'
            );
            return $stmt->execute([
                $dados['nome'],
                $dados['cpf'],
                password_hash($dados['senha'], PASSWORD_BCRYPT),
                $dados['setor_id'],
                $dados['ativo'],
                $dados['is_admin'],
                $id,
            ]);
        }

        $stmt = $this->pdo->prepare(
            'UPDATE usuarios
                SET nome = ?, cpf = ?, setor_id = ?, ativo = ?, is_admin = ?
              WHERE id = ?'
        );
        return $stmt->execute([
            $dados['nome'],
            $dados['cpf'],
            $dados['setor_id'],
            $dados['ativo'],
            $dados['is_admin'],
            $id,
        ]);
    }

    public function excluir(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM usuarios WHERE id = ?');
        return $stmt->execute([$id]);
    }

    public function listarSetores(): array
    {
        return $this->pdo->query(
            'SELECT * FROM setores WHERE ativo = 1 ORDER BY nome'
        )->fetchAll();
    }

    public function cpfExiste(string $cpf, int $excludeId = 0): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM usuarios WHERE cpf = ? AND id <> ?'
        );
        $stmt->execute([$cpf, $excludeId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /** Quantos administradores ativos existem além do informado. */
    public function outrosAdmins(int $excetoId = 0): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM usuarios WHERE is_admin = 1 AND ativo = 1 AND id <> ?'
        );
        $stmt->execute([$excetoId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Troca a senha do próprio usuário.
     *
     * Confere a senha atual dentro do próprio método, com o hash lido na
     * hora: deixar essa conferência no controller abriria a porta para um
     * caminho novo esquecer de fazê-la.
     */
    public function trocarSenha(int $id, string $atual, string $nova): bool
    {
        $stmt = $this->pdo->prepare('SELECT senha FROM usuarios WHERE id = ? AND ativo = 1');
        $stmt->execute([$id]);
        $hash = $stmt->fetchColumn();

        if (!$hash || !password_verify($atual, (string) $hash)) {
            return false;
        }

        $stmt = $this->pdo->prepare(
            'UPDATE usuarios
                SET senha = ?, senha_provisoria = 0, senha_alterada_em = NOW()
              WHERE id = ?'
        );
        $stmt->execute([password_hash($nova, PASSWORD_BCRYPT), $id]);

        return true;
    }

    /** A senha nova é a mesma que já está gravada? */
    public function senhaEhAtual(int $id, string $senha): bool
    {
        $stmt = $this->pdo->prepare('SELECT senha FROM usuarios WHERE id = ?');
        $stmt->execute([$id]);
        $hash = $stmt->fetchColumn();

        return $hash && password_verify($senha, (string) $hash);
    }

    /** Totais para os cartões do painel. */
    public function contadores(): array
    {
        $q = fn(string $sql): int => (int) $this->pdo->query($sql)->fetchColumn();

        return [
            'usuarios'       => $q('SELECT COUNT(*) FROM usuarios WHERE ativo = 1'),
            'setores'        => $q('SELECT COUNT(*) FROM setores WHERE ativo = 1'),
            'especialidades' => $q('SELECT COUNT(*) FROM especialidades WHERE ativo = 1'),
            'cirurgias'      => $q('SELECT COUNT(*) FROM cirurgias'),
        ];
    }
}
