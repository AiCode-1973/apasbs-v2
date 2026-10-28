<?php
declare(strict_types=1);

/**
 * Coletas — o que o robô do ReciclaLead trouxe, esperando conferência.
 *
 * O coletor (tools/coletar_reciclalead.php) só grava aqui. Virar fechamento
 * é decisão de gente, nesta tela: sistema de terceiro muda rótulo sem avisar,
 * e número errado no fechamento é pior que número nenhum.
 */
class ColetaModel
{
    public const STATUS = [
        'pendente'   => 'Aguardando conferência',
        'confirmada' => 'Confirmada',
        'descartada' => 'Descartada',
        'erro'       => 'Falhou',
    ];

    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = getPDO();
    }

    // ── Leitura ───────────────────────────────────────────────────────────

    public function listar(string $status = '', int $limite = 60): array
    {
        $sql = 'SELECT c.*, u.nome AS usuario_nome
                  FROM central_coletas c
                  LEFT JOIN usuarios u ON u.id = c.usuario_id';
        $params = [];

        if ($status !== '' && isset(self::STATUS[$status])) {
            $sql .= ' WHERE c.status = ?';
            $params[] = $status;
        }

        $sql .= ' ORDER BY c.ano DESC, c.mes DESC, c.id DESC LIMIT ' . max(1, $limite);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function contarPorStatus(): array
    {
        $linhas = $this->pdo->query(
            'SELECT status, COUNT(*) AS qtd FROM central_coletas GROUP BY status'
        )->fetchAll();

        $mapa = array_fill_keys(array_keys(self::STATUS), 0);
        foreach ($linhas as $l) {
            $mapa[$l['status']] = (int) $l['qtd'];
        }
        return $mapa;
    }

    public function buscarPorId(int $id): array|false
    {
        $stmt = $this->pdo->prepare(
            'SELECT c.*, u.nome AS usuario_nome
               FROM central_coletas c
               LEFT JOIN usuarios u ON u.id = c.usuario_id
              WHERE c.id = ?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /** Os itens de um tipo, do maior para o menor. */
    public function itens(int $coletaId, string $tipo): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT i.*, m.nome AS motivo_nome
               FROM central_coleta_itens i
               LEFT JOIN central_motivos m ON m.id = i.motivo_id
              WHERE i.coleta_id = ? AND i.tipo = ?
              ORDER BY i.quantidade DESC, i.rotulo'
        );
        $stmt->execute([$coletaId, $tipo]);
        return $stmt->fetchAll();
    }

    /** O que já está lançado no fechamento daquela competência, se houver. */
    public function fechamentoAtual(int $ano, int $mes): array|false
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM central_fechamentos WHERE ano = ? AND mes = ?'
        );
        $stmt->execute([$ano, $mes]);
        $fechamento = $stmt->fetch();

        if (!$fechamento) {
            return false;
        }

        $stmt = $this->pdo->prepare(
            'SELECT m.id, m.nome, fm.quantidade
               FROM central_fechamento_motivos fm
               JOIN central_motivos m ON m.id = fm.motivo_id
              WHERE fm.fechamento_id = ?
              ORDER BY fm.quantidade DESC'
        );
        $stmt->execute([(int) $fechamento['id']]);
        $fechamento['motivos'] = $stmt->fetchAll();

        return $fechamento;
    }

    public function motivos(): array
    {
        return $this->pdo->query(
            'SELECT id, codigo, nome FROM central_motivos WHERE ativo = 1 ORDER BY nome'
        )->fetchAll();
    }

    // ── Confirmação ───────────────────────────────────────────────────────

    /**
     * Transforma a coleta em fechamento.
     *
     * $decisoes é [id do item => 'novo' | id do motivo | 'ignorar'], vindo
     * da tela. Tudo numa transação: ou a competência inteira entra, ou nada
     * entra e a coleta continua pendente.
     *
     * Devolve o id do fechamento.
     */
    public function confirmar(int $coletaId, array $decisoes): int
    {
        $coleta = $this->buscarPorId($coletaId);
        if (!$coleta) {
            throw new RuntimeException('Coleta não encontrada.');
        }

        $itens = $this->itens($coletaId, 'motivo');

        $this->pdo->beginTransaction();
        try {
            // 1. Cada rótulo vira (ou casa com) um motivo cadastrado
            $quantidades = [];

            foreach ($itens as $item) {
                $decisao = (string) ($decisoes[(int) $item['id']] ?? 'ignorar');

                if ($decisao === 'ignorar') {
                    continue;
                }

                $motivoId = $decisao === 'novo'
                    ? $this->criarMotivo((string) $item['rotulo'])
                    : (int) $decisao;

                if ($motivoId <= 0) {
                    continue;
                }

                // Dois rótulos podem apontar para o mesmo motivo: somam.
                $quantidades[$motivoId] = ($quantidades[$motivoId] ?? 0) + (int) $item['quantidade'];

                $this->pdo->prepare(
                    'UPDATE central_coleta_itens SET motivo_id = ? WHERE id = ?'
                )->execute([$motivoId, (int) $item['id']]);

                $this->lembrarDePara((string) $item['rotulo'], $motivoId, (string) $coleta['origem']);
            }

            // 2. O fechamento da competência
            $fechamentoId = $this->gravarFechamento(
                (int) $coleta['ano'],
                (int) $coleta['mes'],
                (int) $coleta['total_atendimentos'],
                $coleta
            );

            // 3. As quantidades por motivo
            $this->pdo->prepare('DELETE FROM central_fechamento_motivos WHERE fechamento_id = ?')
                      ->execute([$fechamentoId]);

            $ins = $this->pdo->prepare(
                'INSERT INTO central_fechamento_motivos (fechamento_id, motivo_id, quantidade)
                 VALUES (?, ?, ?)'
            );
            foreach ($quantidades as $motivoId => $qtd) {
                if ($qtd > 0) {
                    $ins->execute([$fechamentoId, $motivoId, $qtd]);
                }
            }

            // 4. A coleta sai da fila
            $this->pdo->prepare(
                "UPDATE central_coletas
                    SET status = 'confirmada', confirmado_em = NOW(), usuario_id = ?, fechamento_id = ?
                  WHERE id = ?"
            )->execute([Auth::id(), $fechamentoId, $coletaId]);

            $this->pdo->commit();
            return $fechamentoId;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function descartar(int $id): bool
    {
        return $this->pdo->prepare(
            "UPDATE central_coletas
                SET status = 'descartada', confirmado_em = NOW(), usuario_id = ?
              WHERE id = ? AND status IN ('pendente','erro')"
        )->execute([Auth::id(), $id]);
    }

    public function excluir(int $id): bool
    {
        return $this->pdo->prepare('DELETE FROM central_coletas WHERE id = ?')->execute([$id]);
    }

    // ── Apoio ─────────────────────────────────────────────────────────────

    /** Cria o fechamento da competência, ou atualiza o que já existe. */
    private function gravarFechamento(int $ano, int $mes, int $total, array $coleta): int
    {
        $stmt = $this->pdo->prepare('SELECT id FROM central_fechamentos WHERE ano = ? AND mes = ?');
        $stmt->execute([$ano, $mes]);
        $id = (int) ($stmt->fetchColumn() ?: 0);

        $observacao = 'Importado do ReciclaLead em ' . date('d/m/Y H:i')
            . ' (coleta #' . (int) $coleta['id'] . ').';

        if ($id > 0) {
            $this->pdo->prepare(
                'UPDATE central_fechamentos SET total_atendimentos = ?, observacao = ?, usuario_id = ?
                  WHERE id = ?'
            )->execute([$total, $observacao, Auth::id(), $id]);
            return $id;
        }

        $this->pdo->prepare(
            'INSERT INTO central_fechamentos (ano, mes, total_atendimentos, observacao, usuario_id)
             VALUES (?, ?, ?, ?, ?)'
        )->execute([$ano, $mes, $total, $observacao, Auth::id()]);

        return (int) $this->pdo->lastInsertId();
    }

    /** Cria um motivo com o nome que veio de fora, gerando um código curto. */
    private function criarMotivo(string $nome): int
    {
        $nome = mb_substr(trim($nome), 0, 150);

        $stmt = $this->pdo->prepare('SELECT id FROM central_motivos WHERE nome = ?');
        $stmt->execute([$nome]);
        if ($id = (int) ($stmt->fetchColumn() ?: 0)) {
            return $id;
        }

        $codigo = $this->codigoLivre($nome);

        $this->pdo->prepare(
            'INSERT INTO central_motivos (codigo, nome, descricao, ativo) VALUES (?, ?, ?, 1)'
        )->execute([$codigo, $nome, 'Criado a partir da coleta do ReciclaLead.']);

        return (int) $this->pdo->lastInsertId();
    }

    /** "Agendamento Concluido" → AGEND-CONC, sem repetir código existente. */
    private function codigoLivre(string $nome): string
    {
        $limpo = strtoupper(strtr($nome, [
            'Á'=>'A','À'=>'A','Ã'=>'A','Â'=>'A','É'=>'E','Ê'=>'E','Í'=>'I',
            'Ó'=>'O','Ô'=>'O','Õ'=>'O','Ú'=>'U','Ç'=>'C',
            'á'=>'A','à'=>'A','ã'=>'A','â'=>'A','é'=>'E','ê'=>'E','í'=>'I',
            'ó'=>'O','ô'=>'O','õ'=>'O','ú'=>'U','ç'=>'C',
        ]));

        $partes = preg_split('/[^A-Z0-9]+/', $limpo, -1, PREG_SPLIT_NO_EMPTY) ?: ['MOTIVO'];
        $base   = implode('-', array_map(fn($p) => substr($p, 0, 5), array_slice($partes, 0, 2)));
        $base   = substr($base, 0, 24) ?: 'MOTIVO';

        $codigo = $base;
        $n = 2;
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM central_motivos WHERE codigo = ?');

        $stmt->execute([$codigo]);
        while ((int) $stmt->fetchColumn() > 0) {
            $codigo = substr($base, 0, 26) . '-' . $n++;
            $stmt->execute([$codigo]);
        }

        return $codigo;
    }

    /** Guarda o casamento para a próxima coleta já vir resolvida. */
    private function lembrarDePara(string $rotulo, int $motivoId, string $origem): void
    {
        $this->pdo->prepare(
            'INSERT INTO central_coleta_mapa (origem, rotulo, motivo_id) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE motivo_id = VALUES(motivo_id)'
        )->execute([$origem, mb_substr($rotulo, 0, 150), $motivoId]);
    }

    /**
     * Sugere, para cada rótulo ainda sem casamento, o motivo cadastrado mais
     * parecido. Só sugere quando a semelhança é alta o bastante para não
     * fazer alguém confirmar bobagem no automático.
     */
    public function sugerir(array $itens, array $motivos): array
    {
        $sugestoes = [];

        foreach ($itens as $item) {
            if (!empty($item['motivo_id'])) {
                continue;
            }

            $melhor = null;
            $melhorPct = 0.0;

            foreach ($motivos as $m) {
                // "Agendamento não Realizado" e "Atendimento realizado" são 84%
                // parecidos e querem dizer o contrário. Quando um nega e o outro
                // não, não há semelhança que valha.
                if ($this->nega((string) $item['rotulo']) !== $this->nega((string) $m['nome'])) {
                    continue;
                }

                similar_text(
                    $this->chave((string) $item['rotulo']),
                    $this->chave((string) $m['nome']),
                    $pct
                );
                if ($pct > $melhorPct) {
                    $melhorPct = $pct;
                    $melhor    = $m;
                }
            }

            if ($melhor && $melhorPct >= 82.0) {
                $sugestoes[(int) $item['id']] = [
                    'motivo_id' => (int) $melhor['id'],
                    'nome'      => $melhor['nome'],
                    'confianca' => round($melhorPct),
                ];
            }
        }

        return $sugestoes;
    }

    /** O rótulo carrega uma negação? ("não realizado", "sem interação") */
    private function nega(string $texto): bool
    {
        $t = ' ' . mb_strtolower(trim($texto)) . ' ';
        $t = strtr($t, ['ã' => 'a', 'á' => 'a', 'â' => 'a']);
        foreach ([' nao ', ' sem ', ' fora ', ' nenhum', ' exceto '] as $marca) {
            if (str_contains($t, $marca)) {
                return true;
            }
        }
        return false;
    }

    private function chave(string $texto): string
    {
        $t = mb_strtolower(trim($texto));
        $t = strtr($t, [
            'á'=>'a','à'=>'a','ã'=>'a','â'=>'a','é'=>'e','ê'=>'e','í'=>'i',
            'ó'=>'o','ô'=>'o','õ'=>'o','ú'=>'u','ç'=>'c',
        ]);
        return preg_replace('/[^a-z0-9]/', '', $t) ?? '';
    }
}
