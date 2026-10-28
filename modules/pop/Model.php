<?php
declare(strict_types=1);

class PopModel
{
    /** Tipos de bloco que o corpo de um POP aceita. */
    public const TIPOS = [
        'texto'    => 'Parágrafo',
        'passos'   => 'Lista de passos',
        'alerta'   => 'Alerta',
        'mensagem' => 'Mensagem para copiar',
    ];

    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = getPDO();
    }

    /**
     * A busca entra também no conteúdo dos blocos: é assim que alguém acha
     * "Mounjaro" ou "CAPEP" sem saber em qual POP a regra está escrita.
     */
    public function listar(string $busca = '', bool $sohAtivos = false): array
    {
        $sql  = 'SELECT p.* FROM central_pops p';
        $onde = [];
        $params = [];

        if ($sohAtivos) {
            $onde[] = 'p.ativo = 1';
        }

        if ($busca !== '') {
            $like = '%' . like_escape($busca) . '%';
            $onde[] = "(p.titulo LIKE ? ESCAPE '!' OR p.codigo LIKE ? ESCAPE '!'
                        OR p.resumo LIKE ? ESCAPE '!' OR p.categoria LIKE ? ESCAPE '!'
                        OR EXISTS (SELECT 1 FROM central_pop_blocos b
                                    WHERE b.pop_id = p.id AND b.conteudo LIKE ? ESCAPE '!'))";
            $params = [$like, $like, $like, $like, $like];
        }

        if ($onde) {
            $sql .= ' WHERE ' . implode(' AND ', $onde);
        }

        $sql .= ' ORDER BY p.ativo DESC, p.categoria, p.ordem, p.codigo';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function categorias(): array
    {
        $linhas = $this->pdo->query(
            'SELECT DISTINCT categoria FROM central_pops ORDER BY categoria'
        )->fetchAll();
        return array_column($linhas, 'categoria');
    }

    public function buscarPorId(int $id): array|false
    {
        $stmt = $this->pdo->prepare('SELECT * FROM central_pops WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function blocos(int $popId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM central_pop_blocos WHERE pop_id = ? ORDER BY ordem, id'
        );
        $stmt->execute([$popId]);
        return $stmt->fetchAll();
    }

    public function codigoExiste(string $codigo, int $excludeId = 0): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM central_pops WHERE codigo = ? AND id <> ?');
        $stmt->execute([$codigo, $excludeId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /** Grava POP e blocos numa transação: nunca fica meio salvo. */
    public function salvar(array $d, array $blocos, int $id = 0): int
    {
        $this->pdo->beginTransaction();

        try {
            if ($id > 0) {
                $stmt = $this->pdo->prepare(
                    'UPDATE central_pops
                        SET codigo = ?, titulo = ?, categoria = ?, resumo = ?,
                            versao = ?, revisado_em = ?, ordem = ?, ativo = ?,
                            usuario_id = ?, usuario_nome = ?
                      WHERE id = ?'
                );
                $stmt->execute([
                    $d['codigo'], $d['titulo'], $d['categoria'],
                    $d['resumo'] !== '' ? $d['resumo'] : null,
                    $d['versao'], $d['revisado_em'] !== '' ? $d['revisado_em'] : null,
                    $d['ordem'], $d['ativo'],
                    Auth::id(), (string) Auth::nome(), $id,
                ]);
            } else {
                $stmt = $this->pdo->prepare(
                    'INSERT INTO central_pops
                        (codigo, titulo, categoria, resumo, versao, revisado_em,
                         ordem, ativo, usuario_id, usuario_nome)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute([
                    $d['codigo'], $d['titulo'], $d['categoria'],
                    $d['resumo'] !== '' ? $d['resumo'] : null,
                    $d['versao'], $d['revisado_em'] !== '' ? $d['revisado_em'] : null,
                    $d['ordem'], $d['ativo'],
                    Auth::id(), (string) Auth::nome(),
                ]);
                $id = (int) $this->pdo->lastInsertId();
            }

            $this->pdo->prepare('DELETE FROM central_pop_blocos WHERE pop_id = ?')->execute([$id]);

            $ins = $this->pdo->prepare(
                'INSERT INTO central_pop_blocos (pop_id, tipo, titulo, conteudo, ordem)
                 VALUES (?, ?, ?, ?, ?)'
            );

            $ordem = 0;
            foreach ($blocos as $b) {
                if (trim((string) $b['conteudo']) === '') {
                    continue;
                }
                $ordem += 10;
                $ins->execute([
                    $id,
                    isset(self::TIPOS[$b['tipo']]) ? $b['tipo'] : 'texto',
                    trim((string) $b['titulo']) !== '' ? mb_substr(trim((string) $b['titulo']), 0, 120) : null,
                    trim((string) $b['conteudo']),
                    $ordem,
                ]);
            }

            $this->pdo->commit();
            return $id;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function excluir(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM central_pops WHERE id = ?');
        return $stmt->execute([$id]);
    }

    public function proximaOrdem(string $categoria): int
    {
        $stmt = $this->pdo->prepare('SELECT COALESCE(MAX(ordem), 0) FROM central_pops WHERE categoria = ?');
        $stmt->execute([$categoria]);
        return (int) $stmt->fetchColumn() + 10;
    }
}
