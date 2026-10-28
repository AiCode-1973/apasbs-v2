<?php
declare(strict_types=1);

class AgendaModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = getPDO();
    }

    // ── Leitura do mês ────────────────────────────────────────────────────

    /** Primeiro e último dia da competência, em formato do banco. */
    public static function limites(int $ano, int $mes): array
    {
        $inicio = sprintf('%04d-%02d-01', $ano, $mes);
        $fim    = date('Y-m-t', (int) strtotime($inicio));
        return [$inicio, $fim];
    }

    /**
     * Blocos do mês, já com o médico e as vagas de cada tipo de exame.
     * Retorna um bloco por linha; a chave 'vagas' traz um array por exame.
     */
    public function blocosDoMes(int $ano, int $mes, int $medicoId = 0, int $exameId = 0): array
    {
        [$inicio, $fim] = self::limites($ano, $mes);

        $sql = 'SELECT b.id, b.data, b.turno, b.observacao, b.techsallus, b.updated_at,
                       m.id AS medico_id, m.nome AS medico_nome, m.cor AS medico_cor,
                       m.crm AS medico_crm, m.ativo AS medico_ativo
                  FROM central_agenda_blocos b
                  JOIN central_medicos m ON m.id = b.medico_id
                 WHERE b.data BETWEEN ? AND ?';
        $params = [$inicio, $fim];

        if ($medicoId > 0) {
            $sql .= ' AND b.medico_id = ?';
            $params[] = $medicoId;
        }

        if ($exameId > 0) {
            $sql .= ' AND EXISTS (SELECT 1 FROM central_agenda_vagas v
                                   WHERE v.bloco_id = b.id AND v.exame_id = ? AND v.capacidade > 0)';
            $params[] = $exameId;
        }

        $sql .= ' ORDER BY b.data, FIELD(b.turno, \'manha\', \'tarde\'), m.nome';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $blocos = $stmt->fetchAll();

        if (!$blocos) {
            return [];
        }

        $vagas = $this->vagasDeVariosBlocos(array_column($blocos, 'id'));

        foreach ($blocos as &$b) {
            $lista = $vagas[(int) $b['id']] ?? [];
            $b['vagas']      = $lista;
            $b['capacidade'] = array_sum(array_column($lista, 'capacidade'));
            $b['ocupadas']   = array_sum(array_column($lista, 'ocupadas'));
            $b['livres']     = max(0, $b['capacidade'] - $b['ocupadas']);
        }
        unset($b);

        return $blocos;
    }

    /** Vagas de vários blocos de uma vez, agrupadas por bloco_id. */
    private function vagasDeVariosBlocos(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if (!$ids) {
            return [];
        }

        // Placeholders nomeados e únicos — o PDO roda sem emulação
        $nomes = [];
        $params = [];
        foreach ($ids as $i => $id) {
            $nomes[] = ':b' . $i;
            $params[':b' . $i] = $id;
        }

        $sql = 'SELECT v.bloco_id, v.exame_id, v.capacidade,
                       e.codigo, e.nome, e.ordem
                  FROM central_agenda_vagas v
                  JOIN central_exames e ON e.id = v.exame_id
                 WHERE v.bloco_id IN (' . implode(',', $nomes) . ')
                 ORDER BY e.ordem, e.nome';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        // Quantas marcações ativas existem em cada (bloco, exame). Este é o
        // número de "marcadas" — não há mais contador digitado à mão.
        $cnt = $this->pdo->prepare(
            'SELECT bloco_id, exame_id, COUNT(*) AS marcadas
               FROM central_agenda_marcacoes
              WHERE status = \'marcada\'
                AND bloco_id IN (' . implode(',', $nomes) . ')
              GROUP BY bloco_id, exame_id'
        );
        $cnt->execute($params);

        $marcadas = [];
        foreach ($cnt->fetchAll() as $linha) {
            $marcadas[(int) $linha['bloco_id']][(int) $linha['exame_id']] = (int) $linha['marcadas'];
        }

        $mapa = [];
        foreach ($stmt->fetchAll() as $linha) {
            $blocoId    = (int) $linha['bloco_id'];
            $exameId    = (int) $linha['exame_id'];
            $capacidade = (int) $linha['capacidade'];
            $ocupadas   = $marcadas[$blocoId][$exameId] ?? 0;

            $mapa[$blocoId][] = [
                'exame_id'   => $exameId,
                'codigo'     => $linha['codigo'],
                'nome'       => $linha['nome'],
                'capacidade' => $capacidade,
                'ocupadas'   => $ocupadas,
                'livres'     => max(0, $capacidade - $ocupadas),
            ];
        }
        return $mapa;
    }

    /** Dias bloqueados no mês, indexados pela data. */
    public function bloqueiosDoMes(int $ano, int $mes): array
    {
        [$inicio, $fim] = self::limites($ano, $mes);

        $stmt = $this->pdo->prepare(
            'SELECT * FROM central_agenda_bloqueios WHERE data BETWEEN ? AND ? ORDER BY data'
        );
        $stmt->execute([$inicio, $fim]);

        $mapa = [];
        foreach ($stmt->fetchAll() as $b) {
            $mapa[$b['data']] = $b;
        }
        return $mapa;
    }

    /** Totais por médico no mês. */
    public function resumoPorMedico(int $ano, int $mes): array
    {
        [$inicio, $fim] = self::limites($ano, $mes);

        $stmt = $this->pdo->prepare(
            'SELECT m.id, m.nome, m.cor,
                    COUNT(DISTINCT b.id)   AS blocos,
                    COUNT(DISTINCT b.data) AS dias,
                    COALESCE(SUM(v.capacidade), 0) AS capacidade
               FROM central_agenda_blocos b
               JOIN central_medicos m ON m.id = b.medico_id
               LEFT JOIN central_agenda_vagas v ON v.bloco_id = b.id
              WHERE b.data BETWEEN ? AND ?
              GROUP BY m.id, m.nome, m.cor
              ORDER BY m.nome'
        );
        $stmt->execute([$inicio, $fim]);
        $linhas = $stmt->fetchAll();

        // Consulta separada: juntar marcações no SELECT acima multiplicaria as
        // linhas de vagas e inflaria a capacidade.
        $cnt = $this->pdo->prepare(
            'SELECT b.medico_id, COUNT(*) AS marcadas
               FROM central_agenda_marcacoes ma
               JOIN central_agenda_blocos b ON b.id = ma.bloco_id
              WHERE ma.status = \'marcada\' AND b.data BETWEEN ? AND ?
              GROUP BY b.medico_id'
        );
        $cnt->execute([$inicio, $fim]);

        $marcadas = [];
        foreach ($cnt->fetchAll() as $linha) {
            $marcadas[(int) $linha['medico_id']] = (int) $linha['marcadas'];
        }

        foreach ($linhas as &$l) {
            $l['ocupadas'] = $marcadas[(int) $l['id']] ?? 0;
        }
        unset($l);

        return $linhas;
    }

    /** Totais por tipo de exame no mês. */
    public function resumoPorExame(int $ano, int $mes): array
    {
        [$inicio, $fim] = self::limites($ano, $mes);

        $stmt = $this->pdo->prepare(
            'SELECT e.id, e.codigo, e.nome,
                    COALESCE(SUM(v.capacidade), 0) AS capacidade
               FROM central_agenda_vagas v
               JOIN central_exames e        ON e.id = v.exame_id
               JOIN central_agenda_blocos b ON b.id = v.bloco_id
              WHERE b.data BETWEEN ? AND ?
              GROUP BY e.id, e.codigo, e.nome, e.ordem
              ORDER BY e.ordem, e.nome'
        );
        $stmt->execute([$inicio, $fim]);
        $linhas = $stmt->fetchAll();

        $cnt = $this->pdo->prepare(
            'SELECT ma.exame_id, COUNT(*) AS marcadas
               FROM central_agenda_marcacoes ma
               JOIN central_agenda_blocos b ON b.id = ma.bloco_id
              WHERE ma.status = \'marcada\' AND b.data BETWEEN ? AND ?
              GROUP BY ma.exame_id'
        );
        $cnt->execute([$inicio, $fim]);

        $marcadas = [];
        foreach ($cnt->fetchAll() as $linha) {
            $marcadas[(int) $linha['exame_id']] = (int) $linha['marcadas'];
        }

        foreach ($linhas as &$l) {
            $l['ocupadas'] = $marcadas[(int) $l['id']] ?? 0;
        }
        unset($l);

        return $linhas;
    }

    /** Anos que já têm bloco lançado, para o filtro. */
    public function anosComAgenda(): array
    {
        $anos = $this->pdo->query(
            'SELECT DISTINCT YEAR(data) AS ano FROM central_agenda_blocos ORDER BY ano DESC'
        )->fetchAll();
        return array_map('intval', array_column($anos, 'ano'));
    }

    // ── Bloco ─────────────────────────────────────────────────────────────

    public function buscarBloco(int $id): array|false
    {
        $stmt = $this->pdo->prepare(
            'SELECT b.*, m.nome AS medico_nome, m.cor AS medico_cor
               FROM central_agenda_blocos b
               JOIN central_medicos m ON m.id = b.medico_id
              WHERE b.id = ?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /** Vagas de um bloco, indexadas por exame_id. */
    public function vagasDoBloco(int $blocoId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT v.exame_id, v.capacidade,
                    (SELECT COUNT(*) FROM central_agenda_marcacoes ma
                      WHERE ma.bloco_id = v.bloco_id
                        AND ma.exame_id = v.exame_id
                        AND ma.status   = \'marcada\') AS ocupadas
               FROM central_agenda_vagas v
              WHERE v.bloco_id = ?'
        );
        $stmt->execute([$blocoId]);

        $mapa = [];
        foreach ($stmt->fetchAll() as $v) {
            $mapa[(int) $v['exame_id']] = [
                'capacidade' => (int) $v['capacidade'],
                'ocupadas'   => (int) $v['ocupadas'],
            ];
        }
        return $mapa;
    }

    public function blocoExiste(int $medicoId, string $data, string $turno, int $excludeId = 0): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM central_agenda_blocos
              WHERE medico_id = ? AND data = ? AND turno = ? AND id <> ?'
        );
        $stmt->execute([$medicoId, $data, $turno, $excludeId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /** Cria (ou atualiza) o bloco junto com suas vagas, numa transação só. */
    public function salvarBloco(array $dados, array $vagas, int $id = 0): int
    {
        $this->pdo->beginTransaction();

        try {
            if ($id > 0) {
                $stmt = $this->pdo->prepare(
                    'UPDATE central_agenda_blocos
                        SET medico_id = ?, data = ?, turno = ?, observacao = ?,
                            techsallus = ?, usuario_id = ?
                      WHERE id = ?'
                );
                $stmt->execute([
                    $dados['medico_id'], $dados['data'], $dados['turno'],
                    $dados['observacao'] !== '' ? $dados['observacao'] : null,
                    $dados['techsallus'], $dados['usuario_id'], $id,
                ]);
            } else {
                $stmt = $this->pdo->prepare(
                    'INSERT INTO central_agenda_blocos
                        (medico_id, data, turno, observacao, techsallus, usuario_id)
                     VALUES (?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute([
                    $dados['medico_id'], $dados['data'], $dados['turno'],
                    $dados['observacao'] !== '' ? $dados['observacao'] : null,
                    $dados['techsallus'], $dados['usuario_id'],
                ]);
                $id = (int) $this->pdo->lastInsertId();
            }

            // Apagar e reinserir é seguro: as marcações vivem em tabela própria e
            // apontam para (bloco, exame), não para a linha de vaga.
            $this->pdo->prepare('DELETE FROM central_agenda_vagas WHERE bloco_id = ?')->execute([$id]);

            $ins = $this->pdo->prepare(
                'INSERT INTO central_agenda_vagas (bloco_id, exame_id, capacidade) VALUES (?, ?, ?)'
            );

            foreach ($vagas as $exameId => $v) {
                $capacidade = (int) ($v['capacidade'] ?? 0);

                // Só grava o que realmente existe no bloco
                if ($capacidade <= 0) {
                    continue;
                }
                $ins->execute([$id, (int) $exameId, $capacidade]);
            }

            $this->pdo->commit();
            return $id;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function excluirBloco(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM central_agenda_blocos WHERE id = ?');
        return $stmt->execute([$id]);
    }

    // ── Bloqueio de dia ───────────────────────────────────────────────────

    public function buscarBloqueio(string $data): array|false
    {
        $stmt = $this->pdo->prepare('SELECT * FROM central_agenda_bloqueios WHERE data = ?');
        $stmt->execute([$data]);
        return $stmt->fetch();
    }

    public function bloquear(string $data, string $motivo, ?int $usuarioId): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO central_agenda_bloqueios (data, motivo, usuario_id) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE motivo = VALUES(motivo), usuario_id = VALUES(usuario_id)'
        );
        $stmt->execute([$data, $motivo, $usuarioId]);
    }

    public function desbloquear(string $data): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM central_agenda_bloqueios WHERE data = ?');
        return $stmt->execute([$data]);
    }

    /** Quantos blocos existem num dia — para avisar antes de bloquear. */
    public function blocosNoDia(string $data): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM central_agenda_blocos WHERE data = ?');
        $stmt->execute([$data]);
        return (int) $stmt->fetchColumn();
    }

    // ── Marcações ─────────────────────────────────────────────────────────

    /** Todas as marcações de um bloco, ativas e canceladas. */
    public function marcacoesDoBloco(int $blocoId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT ma.*, e.codigo AS exame_codigo, e.nome AS exame_nome, e.ordem AS exame_ordem,
                    mo.nome AS motivo_nome
               FROM central_agenda_marcacoes ma
               JOIN central_exames e ON e.id = ma.exame_id
               LEFT JOIN central_cancelamento_motivos mo ON mo.id = ma.motivo_id
              WHERE ma.bloco_id = ?
              ORDER BY ma.status, e.ordem, e.nome, ma.created_at'
        );
        $stmt->execute([$blocoId]);
        return $stmt->fetchAll();
    }

    /**
     * A mesma paciente já está marcada para este exame neste bloco?
     * Dois exames diferentes no mesmo bloco continuam permitidos.
     */
    public function cpfJaMarcado(int $blocoId, int $exameId, string $cpf): bool
    {
        if ($cpf === '') {
            return false;
        }

        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM central_agenda_marcacoes
              WHERE bloco_id = ? AND exame_id = ? AND paciente_cpf = ? AND status = \'marcada\''
        );
        $stmt->execute([$blocoId, $exameId, $cpf]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /** Quantas marcações ativas o bloco tem — trava a exclusão do bloco. */
    public function marcacoesAtivas(int $blocoId): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM central_agenda_marcacoes WHERE bloco_id = ? AND status = \'marcada\''
        );
        $stmt->execute([$blocoId]);
        return (int) $stmt->fetchColumn();
    }

    public function buscarMarcacao(int $id): array|false
    {
        $stmt = $this->pdo->prepare(
            'SELECT ma.*, e.nome AS exame_nome, b.data, b.turno, b.medico_id
               FROM central_agenda_marcacoes ma
               JOIN central_exames e        ON e.id = ma.exame_id
               JOIN central_agenda_blocos b ON b.id = ma.bloco_id
              WHERE ma.id = ?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /** Capacidade contratada de um tipo de exame dentro do bloco. */
    public function capacidadeDaVaga(int $blocoId, int $exameId): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT capacidade FROM central_agenda_vagas WHERE bloco_id = ? AND exame_id = ?'
        );
        $stmt->execute([$blocoId, $exameId]);
        return (int) $stmt->fetchColumn();
    }

    /** Marcações ativas de um tipo de exame dentro do bloco. */
    public function marcadasDaVaga(int $blocoId, int $exameId): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM central_agenda_marcacoes
              WHERE bloco_id = ? AND exame_id = ? AND status = \'marcada\''
        );
        $stmt->execute([$blocoId, $exameId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Grava a marcação conferindo a vaga dentro da mesma transação.
     * Sem isso, duas atendentes clicando ao mesmo tempo na última vaga
     * passariam as duas.
     *
     * @return int|false id da marcação, ou false se a vaga acabou nesse meio-tempo
     */
    public function marcar(array $dados): int|false
    {
        $this->pdo->beginTransaction();

        try {
            $conf = $this->pdo->prepare(
                'SELECT capacidade FROM central_agenda_vagas
                  WHERE bloco_id = ? AND exame_id = ? FOR UPDATE'
            );
            $conf->execute([$dados['bloco_id'], $dados['exame_id']]);
            $capacidade = (int) $conf->fetchColumn();

            $usadas = $this->marcadasDaVaga($dados['bloco_id'], $dados['exame_id']);

            if ($capacidade <= 0 || $usadas >= $capacidade) {
                $this->pdo->rollBack();
                return false;
            }

            $stmt = $this->pdo->prepare(
                'INSERT INTO central_agenda_marcacoes
                    (bloco_id, exame_id, paciente_cpf, paciente_nome, observacao,
                     atendente_id, atendente_nome)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $dados['bloco_id'],
                $dados['exame_id'],
                $dados['paciente_cpf'],
                $dados['paciente_nome'],
                $dados['observacao'] !== '' ? $dados['observacao'] : null,
                $dados['atendente_id'],
                $dados['atendente_nome'],
            ]);

            $id = (int) $this->pdo->lastInsertId();
            $this->pdo->commit();
            return $id;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /** Cancela devolvendo a vaga. O registro permanece, com motivo e autor. */
    public function cancelar(int $id, int $motivoId, ?int $usuarioId, string $usuarioNome): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE central_agenda_marcacoes
                SET status = \'cancelada\', motivo_id = ?, cancelado_id = ?,
                    cancelado_nome = ?, cancelado_em = NOW()
              WHERE id = ? AND status = \'marcada\''
        );
        $stmt->execute([$motivoId, $usuarioId, $usuarioNome, $id]);
        return $stmt->rowCount() > 0;
    }

    public function excluirMarcacao(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM central_agenda_marcacoes WHERE id = ?');
        return $stmt->execute([$id]);
    }

    // ── Motivos de cancelamento ───────────────────────────────────────────
    // Lista própria desta seção; nada a ver com os motivos de fechamento.

    /** Mensagens que a atendente pode escolher, na ordem definida no cadastro. */
    public function motivosDeCancelamento(): array
    {
        return $this->pdo->query(
            'SELECT id, nome FROM central_cancelamento_motivos
              WHERE ativo = 1 ORDER BY ordem, nome'
        )->fetchAll();
    }

    /**
     * A mensagem escolhida existe? Não exige estar ativa: ela pode ter saído
     * da lista entre a tela abrir e a atendente clicar.
     */
    public function motivoExiste(int $id): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM central_cancelamento_motivos WHERE id = ?');
        $stmt->execute([$id]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /** Texto da mensagem, para escrever na auditoria. */
    public function nomeDoMotivo(int $id): string
    {
        $stmt = $this->pdo->prepare('SELECT nome FROM central_cancelamento_motivos WHERE id = ?');
        $stmt->execute([$id]);
        return (string) $stmt->fetchColumn();
    }

    /**
     * Devolve o id do motivo digitado, cadastrando-o se for a primeira vez.
     * A colação do campo é insensível a maiúsculas, então "Paciente desistiu"
     * e "paciente desistiu" caem no mesmo registro em vez de virar dois.
     */
    public function motivoDeCancelamento(string $nome, ?int $usuarioId): int
    {
        $nome = trim(preg_replace('/\s+/u', ' ', $nome) ?? '');

        $busca = $this->pdo->prepare('SELECT id FROM central_cancelamento_motivos WHERE nome = ?');
        $busca->execute([$nome]);
        $id = $busca->fetchColumn();

        if ($id !== false) {
            return (int) $id;
        }

        // Entra no fim da lista, com folga de 10 para encaixar no meio depois
        $ordem = (int) $this->pdo->query(
            'SELECT COALESCE(MAX(ordem), 0) FROM central_cancelamento_motivos'
        )->fetchColumn() + 10;

        $this->pdo->prepare(
            'INSERT INTO central_cancelamento_motivos (nome, ordem, usuario_id) VALUES (?, ?, ?)'
        )->execute([$nome, $ordem, $usuarioId]);

        return (int) $this->pdo->lastInsertId();
    }
}
