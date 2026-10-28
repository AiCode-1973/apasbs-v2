<?php
declare(strict_types=1);

class AuditoriaModel
{
    private PDO $pdo;
    private int $porPagina = 40;

    public function __construct()
    {
        $this->pdo = getPDO();
    }

    public function listar(array $filtros, int $pagina = 1): array
    {
        $pagina = max(1, $pagina);
        $offset = ($pagina - 1) * $this->porPagina;

        $sql = 'SELECT * FROM auditoria' . $this->where($filtros)
             . ' ORDER BY created_at DESC, id DESC LIMIT :limite OFFSET :offset';

        $stmt = $this->pdo->prepare($sql);
        $this->bind($stmt, $filtros);
        $stmt->bindValue(':limite', $this->porPagina, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function total(array $filtros): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM auditoria' . $this->where($filtros));
        $this->bind($stmt, $filtros);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    public function acoes(): array
    {
        return $this->pdo->query(
            'SELECT DISTINCT acao FROM auditoria ORDER BY acao'
        )->fetchAll(PDO::FETCH_COLUMN);
    }

    /** Quantos registros por página — validado por Paginacao::porPagina(). */
    public function setPorPagina(int $quantos): void
    {
        $this->porPagina = max(1, $quantos);
    }

    public function getPorPagina(): int
    {
        return $this->porPagina;
    }

    /** Remove registros mais antigos que N dias. */
    public function limpar(int $dias): int
    {
        $stmt = $this->pdo->prepare('DELETE FROM auditoria WHERE created_at < (NOW() - INTERVAL :dias DAY)');
        $stmt->bindValue(':dias', max(1, $dias), PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount();
    }

    // ── Apoio ─────────────────────────────────────────────────────────────

    private function where(array $f): string
    {
        $partes = [];

        if (($f['q'] ?? '') !== '') {
            $partes[] = "(descricao LIKE :b1 ESCAPE '!' OR usuario_nome LIKE :b2 ESCAPE '!')";
        }
        if (($f['modulo'] ?? '') !== '') {
            $partes[] = 'modulo = :modulo';
        }
        if (($f['acao'] ?? '') !== '') {
            $partes[] = 'acao = :acao';
        }
        if (($f['de'] ?? '') !== '') {
            $partes[] = 'created_at >= :de';
        }
        if (($f['ate'] ?? '') !== '') {
            $partes[] = 'created_at < (:ate + INTERVAL 1 DAY)';
        }

        return $partes ? ' WHERE ' . implode(' AND ', $partes) : '';
    }

    private function bind(PDOStatement $stmt, array $f): void
    {
        if (($f['q'] ?? '') !== '') {
            $like = '%' . like_escape((string) $f['q']) . '%';
            $stmt->bindValue(':b1', $like);
            $stmt->bindValue(':b2', $like);
        }
        if (($f['modulo'] ?? '') !== '') {
            $stmt->bindValue(':modulo', (string) $f['modulo']);
        }
        if (($f['acao'] ?? '') !== '') {
            $stmt->bindValue(':acao', (string) $f['acao']);
        }
        if (($f['de'] ?? '') !== '') {
            $stmt->bindValue(':de', (string) $f['de']);
        }
        if (($f['ate'] ?? '') !== '') {
            $stmt->bindValue(':ate', (string) $f['ate']);
        }
    }
}
