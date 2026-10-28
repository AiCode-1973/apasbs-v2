<?php
declare(strict_types=1);

class ExameModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = getPDO();
    }

    public function listar(string $busca = ''): array
    {
        $sql = 'SELECT e.*,
                       (SELECT COUNT(*) FROM central_agenda_vagas v WHERE v.exame_id = e.id) AS qtd_vagas
                  FROM central_exames e';
        $params = [];

        if ($busca !== '') {
            $sql .= " WHERE (e.codigo LIKE ? ESCAPE '!' OR e.nome LIKE ? ESCAPE '!' OR e.descricao LIKE ? ESCAPE '!')";
            $like = '%' . like_escape($busca) . '%';
            $params = [$like, $like, $like];
        }

        $sql .= ' ORDER BY e.ativo DESC, e.ordem, e.nome';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Só os ativos, na ordem de exibição — usado pela agenda */
    public function ativos(): array
    {
        return $this->pdo->query(
            'SELECT id, codigo, nome, ordem FROM central_exames WHERE ativo = 1 ORDER BY ordem, nome'
        )->fetchAll();
    }

    /**
     * Tipos que devem aparecer no formulário de um bloco: todos os ativos,
     * mais os inativos que já têm vaga lançada naquele bloco — senão o
     * lançamento antigo sumiria da tela ao salvar.
     */
    public function paraFormulario(array $idsExtras = []): array
    {
        $idsExtras = array_values(array_unique(array_filter(array_map('intval', $idsExtras))));

        if (!$idsExtras) {
            return $this->ativos();
        }

        $nomes  = [];
        $params = [];
        foreach ($idsExtras as $i => $id) {
            $nomes[] = ':x' . $i;
            $params[':x' . $i] = $id;
        }

        $stmt = $this->pdo->prepare(
            'SELECT id, codigo, nome, ordem, ativo FROM central_exames
              WHERE ativo = 1 OR id IN (' . implode(',', $nomes) . ')
              ORDER BY ordem, nome'
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function buscarPorId(int $id): array|false
    {
        $stmt = $this->pdo->prepare('SELECT * FROM central_exames WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function criar(array $dados): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO central_exames (codigo, nome, descricao, ordem, ativo) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $dados['codigo'],
            $dados['nome'],
            $dados['descricao'] !== '' ? $dados['descricao'] : null,
            $dados['ordem'],
            $dados['ativo'],
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function atualizar(int $id, array $dados): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE central_exames SET codigo = ?, nome = ?, descricao = ?, ordem = ?, ativo = ? WHERE id = ?'
        );
        return $stmt->execute([
            $dados['codigo'],
            $dados['nome'],
            $dados['descricao'] !== '' ? $dados['descricao'] : null,
            $dados['ordem'],
            $dados['ativo'],
            $id,
        ]);
    }

    public function excluir(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM central_exames WHERE id = ?');
        return $stmt->execute([$id]);
    }

    /** O tipo de exame já foi usado em algum bloco da agenda? */
    public function possuiVagas(int $id): bool
    {
        try {
            $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM central_agenda_vagas WHERE exame_id = ?');
            $stmt->execute([$id]);
            return (int) $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log('Exames::possuiVagas — ' . $e->getMessage());
            return false;
        }
    }

    public function codigoExiste(string $codigo, int $excludeId = 0): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM central_exames WHERE codigo = ? AND id <> ?');
        $stmt->execute([$codigo, $excludeId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function nomeExiste(string $nome, int $excludeId = 0): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM central_exames WHERE nome = ? AND id <> ?');
        $stmt->execute([$nome, $excludeId]);
        return (int) $stmt->fetchColumn() > 0;
    }
}
