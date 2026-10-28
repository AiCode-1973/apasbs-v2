<?php
declare(strict_types=1);

class ExameCodigoModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = getPDO();
    }

    /**
     * O WHERE dos filtros, montado uma vez e usado pela listagem e pela
     * contagem — assim o total do rodapé nunca discorda da tela.
     *
     * A busca por código entra por subconsulta em vez de JOIN: com JOIN, um
     * exame com três códigos apareceria três vezes na lista.
     */
    private function onde(array $f, array &$params): string
    {
        $w = [];

        if (($f['busca'] ?? '') !== '') {
            $like = '%' . like_escape($f['busca']) . '%';
            $w[] = "(e.descricao LIKE ? ESCAPE '!'
                     OR EXISTS (SELECT 1 FROM amb_exame_codigos_tuss t
                                 WHERE t.exame_id = e.id
                                   AND (t.codigo LIKE ? ESCAPE '!' OR t.termo LIKE ? ESCAPE '!')))";
            array_push($params, $like, $like, $like);
        }

        if (!empty($f['convenio_id'])) {
            $w[] = 'e.convenio_id = ?';
            $params[] = (int) $f['convenio_id'];
        }

        if (!empty($f['so_ativos'])) {
            $w[] = 'e.ativo = 1';
        }

        // Exame cadastrado e sem nenhum código é um registro pela metade:
        // alguém abriu, salvou e não voltou. Este filtro os junta.
        if (!empty($f['sem_codigo'])) {
            $w[] = 'NOT EXISTS (SELECT 1 FROM amb_exame_codigos_tuss t WHERE t.exame_id = e.id)';
        }

        return $w ? ' WHERE ' . implode(' AND ', $w) : '';
    }

    public function listar(array $f, int $limite, int $offset): array
    {
        $params = [];
        $sql = 'SELECT e.*, c.nome AS convenio
                  FROM amb_exame_codigos e
                  JOIN amb_convenios c ON c.id = e.convenio_id'
             . $this->onde($f, $params)
             . ' ORDER BY c.nome, e.descricao
                 LIMIT ? OFFSET ?';

        $stmt = $this->pdo->prepare($sql);

        // Tudo posicional: o PDO recusa misturar `?` com `:nome`.
        $i = 1;
        foreach ($params as $v) {
            $stmt->bindValue($i++, $v);
        }
        $stmt->bindValue($i++, $limite, PDO::PARAM_INT);
        $stmt->bindValue($i,   $offset, PDO::PARAM_INT);
        $stmt->execute();

        $exames = $stmt->fetchAll();

        // Os códigos de todos os exames da página em uma consulta só: um
        // SELECT por linha faria 50 idas ao banco para desenhar uma tela.
        $ids = array_column($exames, 'id');
        $porExame = $this->codigosDe($ids);

        foreach ($exames as &$e) {
            $e['codigos'] = $porExame[(int) $e['id']] ?? [];
        }

        return $exames;
    }

    public function contar(array $f): int
    {
        $params = [];
        $sql = 'SELECT COUNT(*) FROM amb_exame_codigos e' . $this->onde($f, $params);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /** Os códigos de vários exames de uma vez, indexados por exame. */
    public function codigosDe(array $exameIds): array
    {
        if (!$exameIds) {
            return [];
        }

        $marcas = implode(',', array_fill(0, count($exameIds), '?'));
        $stmt = $this->pdo->prepare(
            "SELECT * FROM amb_exame_codigos_tuss
              WHERE exame_id IN ({$marcas})
              ORDER BY exame_id, ordem, id"
        );
        $stmt->execute(array_map('intval', $exameIds));

        $mapa = [];
        foreach ($stmt->fetchAll() as $c) {
            $mapa[(int) $c['exame_id']][] = $c;
        }
        return $mapa;
    }

    public function buscarPorId(int $id): array|false
    {
        $stmt = $this->pdo->prepare(
            'SELECT e.*, c.nome AS convenio
               FROM amb_exame_codigos e
               JOIN amb_convenios c ON c.id = e.convenio_id
              WHERE e.id = ?'
        );
        $stmt->execute([$id]);
        $exame = $stmt->fetch();

        if ($exame) {
            $exame['codigos'] = $this->codigosDe([(int) $exame['id']])[(int) $exame['id']] ?? [];
        }
        return $exame;
    }

    /** Já existe este exame para este convênio? */
    public function existe(int $convenioId, string $descricao, int $excetoId = 0): array|false
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, descricao FROM amb_exame_codigos
              WHERE convenio_id = ? AND descricao = ? AND id <> ?'
        );
        $stmt->execute([$convenioId, $descricao, $excetoId]);
        return $stmt->fetch();
    }

    /**
     * Grava o exame e os códigos dele numa transação só.
     *
     * Os códigos são apagados e regravados em vez de comparados um a um:
     * a lista é curta e a ordem importa, e um diff aqui seria código
     * complicado para resolver um problema que não existe nesta escala.
     */
    public function salvar(int $id, array $d, array $codigos): int
    {
        $this->pdo->beginTransaction();

        try {
            if ($id > 0) {
                $stmt = $this->pdo->prepare(
                    'UPDATE amb_exame_codigos
                        SET convenio_id = ?, descricao = ?, observacao = ?, ativo = ?,
                            usuario_id = ?, usuario_nome = ?
                      WHERE id = ?'
                );
                $stmt->execute([
                    $d['convenio_id'], $d['descricao'], $d['observacao'] ?: null,
                    $d['ativo'], Auth::id(), Auth::nome(), $id,
                ]);

                $this->pdo->prepare('DELETE FROM amb_exame_codigos_tuss WHERE exame_id = ?')
                          ->execute([$id]);
            } else {
                $stmt = $this->pdo->prepare(
                    'INSERT INTO amb_exame_codigos
                        (convenio_id, descricao, observacao, ativo, usuario_id, usuario_nome)
                     VALUES (?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute([
                    $d['convenio_id'], $d['descricao'], $d['observacao'] ?: null,
                    $d['ativo'], Auth::id(), Auth::nome(),
                ]);
                $id = (int) $this->pdo->lastInsertId();
            }

            $ins = $this->pdo->prepare(
                'INSERT INTO amb_exame_codigos_tuss (exame_id, codigo, termo, ordem)
                 VALUES (?, ?, ?, ?)'
            );

            $ordem = 0;
            $vistos = [];
            foreach ($codigos as $c) {
                $codigo = trim((string) ($c['codigo'] ?? ''));
                if ($codigo === '' || isset($vistos[$codigo])) {
                    continue;   // linha em branco ou repetida na mesma tela
                }
                $vistos[$codigo] = true;

                $termo = trim((string) ($c['termo'] ?? ''));

                // Rede de segurança do lado de cá: código da Tabela 22 que
                // chegou sem descrição ganha a oficial. Cobre quem digitou e
                // salvou antes de a busca da tela responder, e quem usa o
                // sistema com o JavaScript pela metade.
                if ($termo === '') {
                    $termo = (string) ($this->termoOficial($codigo) ?? '');
                }

                $ins->execute([$id, $codigo, $termo !== '' ? $termo : null, $ordem++]);
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
        // Os códigos vão junto pelo ON DELETE CASCADE.
        return $this->pdo->prepare('DELETE FROM amb_exame_codigos WHERE id = ?')->execute([$id]);
    }

    // ── Números da tela ──────────────────────────────────────────────────

    public function panorama(): array
    {
        $q = fn(string $sql): int => (int) $this->pdo->query($sql)->fetchColumn();

        return [
            'exames'     => $q('SELECT COUNT(*) FROM amb_exame_codigos WHERE ativo = 1'),
            'codigos'    => $q('SELECT COUNT(*) FROM amb_exame_codigos_tuss'),
            'convenios'  => $q('SELECT COUNT(DISTINCT convenio_id) FROM amb_exame_codigos'),
            'sem_codigo' => $q('SELECT COUNT(*) FROM amb_exame_codigos e
                                 WHERE NOT EXISTS (SELECT 1 FROM amb_exame_codigos_tuss t
                                                    WHERE t.exame_id = e.id)'),
        ];
    }

    public function convenios(bool $soAtivos = true): array
    {
        return $this->pdo->query(
            'SELECT * FROM amb_convenios' . ($soAtivos ? ' WHERE ativo = 1' : '') . ' ORDER BY nome'
        )->fetchAll();
    }

    /**
     * O mesmo código em outro convênio.
     *
     * Serve à pergunta que o papel não respondia: "com que código a Unimed
     * paga este exame que a Bluemed paga com 40901165?"
     */
    public function ondeMaisAparece(string $codigo, int $exceto = 0): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT c.nome AS convenio, e.descricao, e.id
               FROM amb_exame_codigos_tuss t
               JOIN amb_exame_codigos e ON e.id = t.exame_id
               JOIN amb_convenios c     ON c.id = e.convenio_id
              WHERE t.codigo = ? AND e.id <> ?
              ORDER BY c.nome'
        );
        $stmt->execute([$codigo, $exceto]);
        return $stmt->fetchAll();
    }

    /** O código existe na Tabela 22 da ANS? Devolve o termo oficial. */
    public function termoOficial(string $codigo): ?string
    {
        if (!ctype_digit($codigo)) {
            return null;   // código próprio do convênio não está na tabela
        }

        $stmt = $this->pdo->prepare('SELECT termo FROM tuss WHERE codigo = ?');
        $stmt->execute([$codigo]);
        $termo = $stmt->fetchColumn();

        return $termo !== false ? (string) $termo : null;
    }
}
