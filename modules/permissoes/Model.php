<?php
declare(strict_types=1);

class PermissaoModel
{
    private const TIPOS = ['pode_ver', 'pode_criar', 'pode_editar', 'pode_excluir'];

    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = getPDO();
    }

    public function listarModulos(): array
    {
        return $this->pdo->query(
            'SELECT * FROM modulos WHERE ativo = 1 ORDER BY nome'
        )->fetchAll();
    }

    /** Ids de módulos válidos — usados para descartar qualquer id forjado no POST. */
    public function idsModulos(): array
    {
        return array_map('intval', $this->pdo->query('SELECT id FROM modulos WHERE ativo = 1')->fetchAll(PDO::FETCH_COLUMN));
    }

    /** Permissões indexadas por modulo_id */
    public function buscarPermissoesUsuario(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT p.*, m.nome AS modulo_nome, m.slug
               FROM permissoes p
               JOIN modulos m ON m.id = p.modulo_id
              WHERE p.usuario_id = ?'
        );
        $stmt->execute([$usuarioId]);

        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $result[(int) $row['modulo_id']] = $row;
        }
        return $result;
    }

    /**
     * Substitui todas as permissões do usuário pelo que veio do formulário.
     * Ids de módulo desconhecidos são ignorados.
     */
    public function salvar(int $usuarioId, array $permissoes): void
    {
        $validos = $this->idsModulos();

        $this->pdo->beginTransaction();

        try {
            $del = $this->pdo->prepare('DELETE FROM permissoes WHERE usuario_id = ?');
            $del->execute([$usuarioId]);

            $ins = $this->pdo->prepare(
                'INSERT INTO permissoes (usuario_id, modulo_id, pode_ver, pode_criar, pode_editar, pode_excluir)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );

            foreach ($permissoes as $moduloId => $perm) {
                $moduloId = (int) $moduloId;

                if (!in_array($moduloId, $validos, true) || !is_array($perm)) {
                    continue;
                }

                $flags = [];
                foreach (self::TIPOS as $tipo) {
                    $flags[] = isset($perm[$tipo]) ? 1 : 0;
                }

                // Não grava linha vazia
                if (array_sum($flags) === 0) {
                    continue;
                }

                // Quem pode criar, editar ou excluir precisa poder ver
                if ($flags[0] === 0) {
                    $flags[0] = 1;
                }

                $ins->execute([$usuarioId, $moduloId, ...$flags]);
            }

            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function listarUsuarios(): array
    {
        return $this->pdo->query(
            'SELECT u.id, u.nome, u.cpf, u.is_admin, s.nome AS setor_nome
               FROM usuarios u
               JOIN setores s ON s.id = u.setor_id
              WHERE u.ativo = 1
              ORDER BY u.nome'
        )->fetchAll();
    }

    public function buscarUsuario(int $id): array|false
    {
        $stmt = $this->pdo->prepare('SELECT id, nome, cpf, is_admin FROM usuarios WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
}
