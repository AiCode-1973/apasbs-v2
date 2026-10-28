<?php
declare(strict_types=1);

class MedicoModel
{
    /** Quantas cores tem a paleta categórica (ver .medico-1..6 no CSS) */
    public const CORES = 6;

    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = getPDO();
    }

    public function listar(string $busca = ''): array
    {
        $sql = 'SELECT m.*,
                       (SELECT COUNT(*) FROM central_agenda_blocos b WHERE b.medico_id = m.id) AS qtd_blocos
                  FROM central_medicos m';
        $params = [];

        if ($busca !== '') {
            $sql .= " WHERE (m.nome LIKE ? ESCAPE '!' OR m.crm LIKE ? ESCAPE '!' OR m.especialidade LIKE ? ESCAPE '!')";
            $like = '%' . like_escape($busca) . '%';
            $params = [$like, $like, $like];
        }

        $sql .= ' ORDER BY m.ativo DESC, m.nome';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Só os ativos — usado nos selects da agenda */
    public function ativos(): array
    {
        return $this->pdo->query(
            'SELECT id, nome, crm, especialidade, cor FROM central_medicos WHERE ativo = 1 ORDER BY nome'
        )->fetchAll();
    }

    /**
     * Médicos oferecidos no formulário: os ativos, mais o inativo que já
     * está naquele bloco — senão editar um bloco antigo trocaria o médico.
     */
    public function paraFormulario(int $incluirId = 0): array
    {
        if ($incluirId <= 0) {
            return $this->ativos();
        }

        $stmt = $this->pdo->prepare(
            'SELECT id, nome, crm, especialidade, cor FROM central_medicos
              WHERE ativo = 1 OR id = ? ORDER BY nome'
        );
        $stmt->execute([$incluirId]);
        return $stmt->fetchAll();
    }

    public function buscarPorId(int $id): array|false
    {
        $stmt = $this->pdo->prepare('SELECT * FROM central_medicos WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function criar(array $dados): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO central_medicos (nome, crm, especialidade, cor, ativo) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $dados['nome'],
            $dados['crm'] !== '' ? $dados['crm'] : null,
            $dados['especialidade'] !== '' ? $dados['especialidade'] : null,
            $dados['cor'],
            $dados['ativo'],
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function atualizar(int $id, array $dados): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE central_medicos SET nome = ?, crm = ?, especialidade = ?, cor = ?, ativo = ? WHERE id = ?'
        );
        return $stmt->execute([
            $dados['nome'],
            $dados['crm'] !== '' ? $dados['crm'] : null,
            $dados['especialidade'] !== '' ? $dados['especialidade'] : null,
            $dados['cor'],
            $dados['ativo'],
            $id,
        ]);
    }

    public function excluir(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM central_medicos WHERE id = ?');
        return $stmt->execute([$id]);
    }

    /** O médico já tem bloco lançado na agenda? */
    public function possuiAgenda(int $id): bool
    {
        try {
            $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM central_agenda_blocos WHERE medico_id = ?');
            $stmt->execute([$id]);
            return (int) $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log('Medicos::possuiAgenda — ' . $e->getMessage());
            return false;
        }
    }

    public function nomeExiste(string $nome, int $excludeId = 0): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM central_medicos WHERE nome = ? AND id <> ?');
        $stmt->execute([$nome, $excludeId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /** Cores já usadas por outros médicos ativos — só para sugerir no formulário */
    public function coresEmUso(int $excludeId = 0): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT cor, nome FROM central_medicos WHERE ativo = 1 AND id <> ? ORDER BY nome'
        );
        $stmt->execute([$excludeId]);

        $mapa = [];
        foreach ($stmt->fetchAll() as $linha) {
            $mapa[(int) $linha['cor']][] = $linha['nome'];
        }
        return $mapa;
    }

    /** Primeira cor livre, para já vir escolhida num cadastro novo */
    public function proximaCorLivre(): int
    {
        $usadas = array_keys($this->coresEmUso());
        for ($c = 1; $c <= self::CORES; $c++) {
            if (!in_array($c, $usadas, true)) {
                return $c;
            }
        }
        return 1;
    }
}
