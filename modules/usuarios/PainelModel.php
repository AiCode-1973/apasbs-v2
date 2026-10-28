<?php
declare(strict_types=1);

/**
 * Consultas do painel inicial.
 *
 * Cada método é independente e tolerante a falha: se uma tabela não existir
 * (banco vindo da v1 sem `auditoria`, por exemplo), devolve vazio em vez de
 * derrubar a tela inteira.
 */
class PainelModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = getPDO();
    }

    /** Números do topo do painel. */
    public function contadores(): array
    {
        return [
            'usuarios'       => $this->contar('SELECT COUNT(*) FROM usuarios WHERE ativo = 1'),
            'setores'        => $this->contar('SELECT COUNT(*) FROM setores WHERE ativo = 1'),
            'especialidades' => $this->contar('SELECT COUNT(*) FROM especialidades WHERE ativo = 1'),
            'cirurgias'      => $this->contar('SELECT COUNT(*) FROM cirurgias'),
        ];
    }

    /** Cirurgias registradas no mês corrente — legenda do cartão de cirurgias. */
    public function cirurgiasNoMes(): int
    {
        return $this->contar(
            "SELECT COUNT(*) FROM cirurgias WHERE created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')"
        );
    }

    /** Últimos procedimentos registrados. */
    public function ultimasCirurgias(int $limite = 6): array
    {
        return $this->consultar(
            'SELECT c.id, c.protocolo, c.medico, c.tuss_codigo, c.tuss_termo, c.created_at,
                    e.nome AS especialidade_nome,
                    (SELECT COUNT(*) FROM cirurgias_anexos a WHERE a.cirurgia_id = c.id) AS total_anexos
               FROM cirurgias c
               JOIN especialidades e ON e.id = c.especialidade_id
              ORDER BY c.created_at DESC, c.id DESC
              LIMIT :limite',
            $limite
        );
    }

    /** Distribuição de cirurgias por especialidade (série única, ordenada). */
    public function porEspecialidade(int $limite = 6): array
    {
        return $this->consultar(
            'SELECT e.nome, COUNT(c.id) AS total
               FROM especialidades e
               JOIN cirurgias c ON c.especialidade_id = e.id
              GROUP BY e.id, e.nome
              ORDER BY total DESC, e.nome
              LIMIT :limite',
            $limite
        );
    }

    /** Últimas ações registradas no log de auditoria. */
    public function atividadeRecente(int $limite = 7): array
    {
        return $this->consultar(
            'SELECT usuario_nome, acao, modulo, descricao, created_at
               FROM auditoria
              ORDER BY id DESC
              LIMIT :limite',
            $limite
        );
    }

    // ── Apoio ─────────────────────────────────────────────────────────────

    private function contar(string $sql): int
    {
        try {
            return (int) $this->pdo->query($sql)->fetchColumn();
        } catch (PDOException $e) {
            error_log('Painel: ' . $e->getMessage());
            return 0;
        }
    }

    private function consultar(string $sql, int $limite): array
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':limite', max(1, min(50, $limite)), PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('Painel: ' . $e->getMessage());
            return [];
        }
    }
}
