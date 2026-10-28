<?php
declare(strict_types=1);

class TussModel
{
    private PDO $pdo;
    private int $porPagina = 50;

    public function __construct()
    {
        $this->pdo = getPDO();
    }

    /**
     * Busca paginada por código, termo ou descrição.
     *
     * LIMIT/OFFSET vão por bindValue como inteiro: com prepares nativos
     * (EMULATE_PREPARES = false) passar esses valores como string quebra no MySQL.
     * Os curingas do LIKE são escapados para que a busca seja literal.
     */
    public function buscar(string $termo, int $pagina = 1, bool $somenteVigentes = false): array
    {
        $pagina = max(1, $pagina);
        $offset = ($pagina - 1) * $this->porPagina;

        $sql = 'SELECT * FROM tuss' . $this->where($termo, $somenteVigentes)
             . ' ORDER BY codigo LIMIT :limite OFFSET :offset';

        $stmt = $this->pdo->prepare($sql);
        $this->bindFiltros($stmt, $termo);
        $stmt->bindValue(':limite', $this->porPagina, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function total(string $termo, bool $somenteVigentes = false): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM tuss' . $this->where($termo, $somenteVigentes));
        $this->bindFiltros($stmt, $termo);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    /** Autocomplete usado pelo formulário de cirurgias. */
    public function sugerir(string $termo, int $limite = 20): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT codigo, termo FROM tuss
              WHERE codigo LIKE :b1 ESCAPE '!' OR termo LIKE :b2 ESCAPE '!'
              ORDER BY codigo
              LIMIT :limite"
        );

        $like = '%' . like_escape($termo) . '%';
        $stmt->bindValue(':b1', $like);
        $stmt->bindValue(':b2', $like);
        $stmt->bindValue(':limite', max(1, min(50, $limite)), PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function buscarPorCodigo(int $codigo): array|false
    {
        $stmt = $this->pdo->prepare('SELECT * FROM tuss WHERE codigo = ?');
        $stmt->execute([$codigo]);
        return $stmt->fetch();
    }

    public function totalGeral(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM tuss')->fetchColumn();
    }

    public function getPorPagina(): int
    {
        return $this->porPagina;
    }

    /** Quantos registros por página — validado por Paginacao::porPagina(). */
    public function setPorPagina(int $quantos): void
    {
        $this->porPagina = max(1, $quantos);
    }

    // ── Apoio ─────────────────────────────────────────────────────────────

    private function where(string $termo, bool $somenteVigentes): string
    {
        $partes = [];

        if ($termo !== '') {
            $partes[] = "(codigo LIKE :b1 ESCAPE '!' OR termo LIKE :b2 ESCAPE '!' OR descricao LIKE :b3 ESCAPE '!')";
        }

        if ($somenteVigentes) {
            $partes[] = '(vigencia_fim IS NULL OR vigencia_fim >= CURDATE())';
        }

        return $partes ? ' WHERE ' . implode(' AND ', $partes) : '';
    }

    private function bindFiltros(PDOStatement $stmt, string $termo): void
    {
        if ($termo === '') {
            return;
        }

        $like = '%' . like_escape($termo) . '%';
        $stmt->bindValue(':b1', $like);
        $stmt->bindValue(':b2', $like);
        $stmt->bindValue(':b3', $like);
    }
}
