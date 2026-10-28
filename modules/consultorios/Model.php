<?php
declare(strict_types=1);

/**
 * Mapa de sala do ambulatório.
 *
 * A grade é semanal e fixa: sala × dia da semana × turno. Não há data,
 * porque o mapa vale enquanto ninguém mudar — é o quadro que fica na
 * parede, não a agenda de um mês.
 */
class ConsultorioModel
{
    /** Segunda a sexta. Sábado só entra na grade se tiver ocupação. */
    public const DIAS_BASE = [1, 2, 3, 4, 5];

    public const PERIODICIDADES = [
        'semanal'   => 'Toda semana',
        'quinzenal' => 'A cada 15 dias',
    ];

    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = getPDO();
    }

    // ── Salas ─────────────────────────────────────────────────────────────

    public function salas(bool $somenteAtivas = true): array
    {
        $sql = 'SELECT s.*,
                       (SELECT COUNT(*) FROM amb_ocupacoes o WHERE o.sala_id = s.id) AS qtd_ocupacoes
                  FROM amb_salas s';
        if ($somenteAtivas) {
            $sql .= ' WHERE s.ativo = 1';
        }
        $sql .= ' ORDER BY s.ativo DESC, s.ordem, s.nome';

        return $this->pdo->query($sql)->fetchAll();
    }

    public function sala(int $id): array|false
    {
        $stmt = $this->pdo->prepare('SELECT * FROM amb_salas WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function salvarSala(array $d, int $id = 0): int
    {
        if ($id > 0) {
            $stmt = $this->pdo->prepare(
                'UPDATE amb_salas SET nome = ?, observacao = ?, ordem = ?, ativo = ? WHERE id = ?'
            );
            $stmt->execute([$d['nome'], $d['observacao'] ?: null, $d['ordem'], $d['ativo'], $id]);
            return $id;
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO amb_salas (nome, observacao, ordem, ativo) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$d['nome'], $d['observacao'] ?: null, $d['ordem'], $d['ativo']]);
        return (int) $this->pdo->lastInsertId();
    }

    public function excluirSala(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM amb_salas WHERE id = ?');
        return $stmt->execute([$id]);
    }

    public function salaNomeExiste(string $nome, int $excluir = 0): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM amb_salas WHERE nome = ? AND id <> ?');
        $stmt->execute([$nome, $excluir]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function proximaOrdemSala(): int
    {
        return ((int) $this->pdo->query('SELECT COALESCE(MAX(ordem), 0) FROM amb_salas')->fetchColumn()) + 10;
    }

    // ── Ocupações ─────────────────────────────────────────────────────────

    /**
     * Todas as ocupações da grade, já ordenadas para a montagem do mapa.
     * $busca filtra por profissional ou especialidade.
     *
     * A especialidade sai da ocupação quando ela tem uma própria e, senão,
     * da que o profissional costuma atender: quem atende clínica num dia e
     * gastro no outro precisa das duas, e quem atende sempre a mesma não
     * deve ter que repetir o preenchimento.
     */
    public function ocupacoes(string $busca = '', bool $somenteAtivas = true): array
    {
        $sql = 'SELECT o.*,
                       s.nome  AS sala_nome,
                       s.ordem AS sala_ordem,
                       p.nome  AS profissional,
                       p.atividade,
                       COALESCE(e.nome, ep.nome) AS especialidade
                  FROM amb_ocupacoes o
                  JOIN amb_salas s          ON s.id  = o.sala_id
                  JOIN amb_profissionais p  ON p.id  = o.profissional_id
                  LEFT JOIN amb_especialidades e  ON e.id  = o.especialidade_id
                  LEFT JOIN amb_especialidades ep ON ep.id = p.especialidade_id';
        $where  = [];
        $params = [];

        if ($somenteAtivas) {
            $where[] = 'o.ativo = 1 AND s.ativo = 1 AND p.ativo = 1';
        }

        if ($busca !== '') {
            $where[] = "(p.nome LIKE :b0 ESCAPE '!'
                         OR e.nome LIKE :b1 ESCAPE '!'
                         OR ep.nome LIKE :b2 ESCAPE '!')";
            $like = '%' . like_escape($busca) . '%';
            $params[':b0'] = $like;
            $params[':b1'] = $like;
            $params[':b2'] = $like;
        }

        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY s.ordem, s.nome, o.dia_semana, o.hora_inicio, p.nome';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Ocupações indexadas para a grade: [turno][sala_id][dia] = [ocupação, …]
     */
    public function grade(array $ocupacoes): array
    {
        $mapa = [];
        foreach ($ocupacoes as $o) {
            $mapa[$o['turno']][(int) $o['sala_id']][(int) $o['dia_semana']][] = $o;
        }
        return $mapa;
    }

    /** Dias que a grade mostra: segunda a sexta + sábado, se houver algo nele. */
    public function diasVisiveis(array $ocupacoes): array
    {
        $dias = self::DIAS_BASE;
        foreach ($ocupacoes as $o) {
            $d = (int) $o['dia_semana'];
            if (!in_array($d, $dias, true)) {
                $dias[] = $d;
            }
        }
        sort($dias);
        return $dias;
    }

    public function ocupacao(int $id): array|false
    {
        $stmt = $this->pdo->prepare(
            'SELECT o.*, s.nome AS sala_nome, p.nome AS profissional, p.atividade
               FROM amb_ocupacoes o
               JOIN amb_salas s         ON s.id = o.sala_id
               JOIN amb_profissionais p ON p.id = o.profissional_id
              WHERE o.id = ?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function salvarOcupacao(array $d, int $id = 0): int
    {
        $campos = [
            $d['sala_id'], $d['profissional_id'], $d['especialidade_id'] ?: null,
            $d['dia_semana'], $d['turno'], $d['hora_inicio'], $d['hora_fim'],
            $d['periodicidade'], $d['observacao'] ?: null, $d['ativo'],
        ];

        if ($id > 0) {
            $stmt = $this->pdo->prepare(
                'UPDATE amb_ocupacoes
                    SET sala_id = ?, profissional_id = ?, especialidade_id = ?,
                        dia_semana = ?, turno = ?, hora_inicio = ?, hora_fim = ?,
                        periodicidade = ?, observacao = ?, ativo = ?
                  WHERE id = ?'
            );
            $stmt->execute([...$campos, $id]);
            return $id;
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO amb_ocupacoes
                (sala_id, profissional_id, especialidade_id, dia_semana, turno,
                 hora_inicio, hora_fim, periodicidade, observacao, ativo, usuario_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([...$campos, Auth::id()]);
        return (int) $this->pdo->lastInsertId();
    }

    public function excluirOcupacao(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM amb_ocupacoes WHERE id = ?');
        return $stmt->execute([$id]);
    }

    /** Mesma sala, dia e profissional começando no mesmo horário. */
    public function duplicada(array $d, int $excluir = 0): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM amb_ocupacoes
              WHERE sala_id = ? AND dia_semana = ? AND turno = ?
                AND hora_inicio = ? AND profissional_id = ? AND id <> ?'
        );
        $stmt->execute([
            $d['sala_id'], $d['dia_semana'], $d['turno'],
            $d['hora_inicio'], $d['profissional_id'], $excluir,
        ]);
        return (int) $stmt->fetchColumn() > 0;
    }

    // ── Conflitos ─────────────────────────────────────────────────────────

    /**
     * Dois tipos de choque, calculados sobre a grade inteira:
     *
     *   sala          → duas ocupações na mesma sala, no mesmo dia, com
     *                   horários que se cruzam. Uma vai encontrar a porta
     *                   fechada.
     *   profissional  → o mesmo profissional em duas salas ao mesmo tempo.
     *
     * Quinzenal com quinzenal não é apontado: podem ser as semanas alternadas.
     */
    public function conflitos(array $ocupacoes): array
    {
        $conflitos = [];

        $cruza = function (array $a, array $b): bool {
            return $a['hora_inicio'] < $b['hora_fim'] && $b['hora_inicio'] < $a['hora_fim'];
        };

        $total = count($ocupacoes);
        for ($i = 0; $i < $total; $i++) {
            for ($j = $i + 1; $j < $total; $j++) {
                $a = $ocupacoes[$i];
                $b = $ocupacoes[$j];

                if ((int) $a['dia_semana'] !== (int) $b['dia_semana'] || !$cruza($a, $b)) {
                    continue;
                }

                // Semanas alternadas não colidem necessariamente
                if ($a['periodicidade'] === 'quinzenal' && $b['periodicidade'] === 'quinzenal') {
                    continue;
                }

                $mesmaSala = (int) $a['sala_id'] === (int) $b['sala_id'];

                // Acupuntura na Sala 1 e na Sala 2 ao mesmo tempo é normal:
                // atividade não é uma pessoa só.
                $mesmoProf = !$a['atividade'] && !$b['atividade']
                    && (int) $a['profissional_id'] === (int) $b['profissional_id'];

                if (!$mesmaSala && !$mesmoProf) {
                    continue;
                }

                $conflitos[] = [
                    'tipo' => $mesmaSala ? 'sala' : 'profissional',
                    'a'    => $a,
                    'b'    => $b,
                ];
            }
        }

        return $conflitos;
    }

    /** Ids envolvidos em algum conflito — para destacar na grade. */
    public function idsEmConflito(array $conflitos): array
    {
        $ids = [];
        foreach ($conflitos as $c) {
            $ids[(int) $c['a']['id']] = true;
            $ids[(int) $c['b']['id']] = true;
        }
        return $ids;
    }

    // ── Especialidades ────────────────────────────────────────────────────

    public function especialidades(bool $somenteAtivas = true, string $busca = ''): array
    {
        $sql = 'SELECT e.*,
                       (SELECT COUNT(*) FROM amb_profissionais p WHERE p.especialidade_id = e.id) AS qtd_profissionais,
                       (SELECT COUNT(*) FROM amb_ocupacoes o WHERE o.especialidade_id = e.id) AS qtd_ocupacoes
                  FROM amb_especialidades e';
        $where  = [];
        $params = [];

        if ($somenteAtivas) {
            $where[] = 'e.ativo = 1';
        }
        if ($busca !== '') {
            $where[] = "e.nome LIKE :b0 ESCAPE '!'";
            $params[':b0'] = '%' . like_escape($busca) . '%';
        }
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY e.ativo DESC, e.nome';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function especialidade(int $id): array|false
    {
        $stmt = $this->pdo->prepare('SELECT * FROM amb_especialidades WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function salvarEspecialidade(array $d, int $id = 0): int
    {
        if ($id > 0) {
            $stmt = $this->pdo->prepare(
                'UPDATE amb_especialidades SET nome = ?, observacao = ?, ativo = ? WHERE id = ?'
            );
            $stmt->execute([$d['nome'], $d['observacao'] ?: null, $d['ativo'], $id]);
            return $id;
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO amb_especialidades (nome, observacao, ativo) VALUES (?, ?, ?)'
        );
        $stmt->execute([$d['nome'], $d['observacao'] ?: null, $d['ativo']]);
        return (int) $this->pdo->lastInsertId();
    }

    public function excluirEspecialidade(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM amb_especialidades WHERE id = ?');
        return $stmt->execute([$id]);
    }

    public function especialidadeNomeExiste(string $nome, int $excluir = 0): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM amb_especialidades WHERE nome = ? AND id <> ?');
        $stmt->execute([$nome, $excluir]);
        return (int) $stmt->fetchColumn() > 0;
    }

    // ── Profissionais ─────────────────────────────────────────────────────

    public function profissionais(bool $somenteAtivos = true, string $busca = ''): array
    {
        $sql = 'SELECT p.*, e.nome AS especialidade_nome,
                       (SELECT COUNT(*) FROM amb_ocupacoes o WHERE o.profissional_id = p.id) AS qtd_ocupacoes
                  FROM amb_profissionais p
                  LEFT JOIN amb_especialidades e ON e.id = p.especialidade_id';
        $where  = [];
        $params = [];

        if ($somenteAtivos) {
            $where[] = 'p.ativo = 1';
        }
        if ($busca !== '') {
            $where[] = "(p.nome LIKE :b0 ESCAPE '!' OR e.nome LIKE :b1 ESCAPE '!')";
            $like = '%' . like_escape($busca) . '%';
            $params[':b0'] = $like;
            $params[':b1'] = $like;
        }
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY p.ativo DESC, p.nome';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function profissional(int $id): array|false
    {
        $stmt = $this->pdo->prepare(
            'SELECT p.*, e.nome AS especialidade_nome
               FROM amb_profissionais p
               LEFT JOIN amb_especialidades e ON e.id = p.especialidade_id
              WHERE p.id = ?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Os profissionais oferecidos no formulário da ocupação: os ativos, mais
     * o inativo que já está naquela ocupação — senão editar o horário de quem
     * saiu trocaria a pessoa sem avisar.
     */
    public function profissionaisParaFormulario(int $incluirId = 0): array
    {
        $sql = 'SELECT p.id, p.nome, p.atividade, p.especialidade_id, e.nome AS especialidade_nome
                  FROM amb_profissionais p
                  LEFT JOIN amb_especialidades e ON e.id = p.especialidade_id
                 WHERE p.ativo = 1 OR p.id = ?
                 ORDER BY p.nome';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$incluirId]);
        return $stmt->fetchAll();
    }

    public function salvarProfissional(array $d, int $id = 0): int
    {
        $campos = [
            $d['nome'], $d['especialidade_id'] ?: null, $d['atividade'],
            $d['observacao'] ?: null, $d['ativo'],
        ];

        if ($id > 0) {
            $stmt = $this->pdo->prepare(
                'UPDATE amb_profissionais
                    SET nome = ?, especialidade_id = ?, atividade = ?, observacao = ?, ativo = ?
                  WHERE id = ?'
            );
            $stmt->execute([...$campos, $id]);
            return $id;
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO amb_profissionais (nome, especialidade_id, atividade, observacao, ativo)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute($campos);
        return (int) $this->pdo->lastInsertId();
    }

    public function excluirProfissional(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM amb_profissionais WHERE id = ?');
        return $stmt->execute([$id]);
    }

    public function profissionalNomeExiste(string $nome, int $excluir = 0): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM amb_profissionais WHERE nome = ? AND id <> ?');
        $stmt->execute([$nome, $excluir]);
        return (int) $stmt->fetchColumn() > 0;
    }

    // ── Apoio ─────────────────────────────────────────────────────────────

    /**
     * Números do rodapé do mapa: quantos espaços a grade tem (sala × dia ×
     * turno), quantos estão ocupados e quantas horas de consultório isso dá.
     */
    public function resumo(array $ocupacoes, int $qtdSalas, array $dias): array
    {
        $espacos   = $qtdSalas * count($dias) * 2;
        $ocupados  = [];
        $minutos   = 0;
        $porTurno  = ['manha' => 0, 'tarde' => 0];

        foreach ($ocupacoes as $o) {
            $chave = $o['turno'] . '-' . $o['sala_id'] . '-' . $o['dia_semana'];
            $ocupados[$chave] = true;
            $porTurno[$o['turno']] = ($porTurno[$o['turno']] ?? 0) + 1;

            $ini = strtotime('1970-01-01 ' . $o['hora_inicio']);
            $fim = strtotime('1970-01-01 ' . $o['hora_fim']);
            if ($fim > $ini) {
                $minutos += (int) (($fim - $ini) / 60);
            }
        }

        return [
            'espacos'       => $espacos,
            'ocupados'      => count($ocupados),
            'livres'        => max(0, $espacos - count($ocupados)),
            'ocupacoes'     => count($ocupacoes),
            'por_turno'     => $porTurno,
            'horas'         => (int) round($minutos / 60),
            'profissionais' => count(array_unique(array_column($ocupacoes, 'profissional_id'))),
        ];
    }
}
