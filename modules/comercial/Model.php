<?php
declare(strict_types=1);

class ComercialModel
{
    /** O que se cobra, pelo nome que o faturamento usa. */
    public const TIPOS = [
        'procedimento' => 'Procedimento / SADT',
        'honorario'    => 'Honorário médico',
        'pacote'       => 'Pacote',
        'opme'         => 'OPME',
        'material'     => 'Material',
        'dieta'        => 'Dieta / suplemento',
        'diaria'       => 'Diária',
        'taxa'         => 'Taxa',
        'porte'        => 'Porte / UCO',
    ];

    /** Os rótulos dos campos que vivem dentro de `extras`. */
    public const EXTRAS = [
        'porte'         => 'Porte',
        'valor_hm'      => 'Valor HM',
        'valor_filme'   => 'Valor do filme',
        'porte_sala'    => 'Porte da sala cirúrgica',
        'unidade'       => 'Unidade',
        'fabricante'    => 'Fabricante',
        'simpro'        => 'Código SIMPRO',
        'anvisa'        => 'Registro Anvisa',
        'validade'      => 'Validade do registro',
        'classificacao' => 'Classificação',
        'apresentacao'  => 'Apresentação',
        'colunas'       => 'Demais colunas do edital',
        'observacao'    => 'Observação do edital',
        'pagina'        => 'Página do edital',
    ];

    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = getPDO();
    }

    // ── Tabelas de valores ───────────────────────────────────────────────

    public function tabelas(bool $soAtivas = false): array
    {
        return $this->pdo->query(
            'SELECT t.*, (SELECT COUNT(*) FROM com_itens i WHERE i.tabela_id = t.id) AS itens
               FROM com_tabelas t'
            . ($soAtivas ? ' WHERE t.ativo = 1' : '')
            . ' ORDER BY t.convenio, t.vigencia DESC'
        )->fetchAll();
    }

    public function tabelaPorId(int $id): array|false
    {
        $stmt = $this->pdo->prepare('SELECT * FROM com_tabelas WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * A tabela que vale hoje: a vigência mais recente que já começou.
     *
     * É ela que a lista abre por padrão — reajuste lançado com antecedência
     * fica guardado e só aparece sozinho quando a data chega.
     */
    public function tabelaVigente(): array|false
    {
        $stmt = $this->pdo->query(
            'SELECT * FROM com_tabelas
              WHERE ativo = 1 AND vigencia <= CURDATE()
              ORDER BY vigencia DESC, id DESC LIMIT 1'
        );
        return $stmt->fetch() ?: $this->pdo->query(
            'SELECT * FROM com_tabelas ORDER BY vigencia DESC, id DESC LIMIT 1'
        )->fetch();
    }

    public function salvarTabela(int $id, array $d): int
    {
        $valores = [
            'convenio'   => $d['convenio'],
            'referencia' => $d['referencia'],
            'vigencia'   => $d['vigencia'],
            'observacao' => $d['observacao'] !== '' ? $d['observacao'] : null,
            'ativo'      => $d['ativo'],
            'usuario_id'   => Auth::id(),
            'usuario_nome' => Auth::nome(),
        ];

        if ($id > 0) {
            $campos = implode(' = ?, ', array_keys($valores)) . ' = ?';
            $this->pdo->prepare("UPDATE com_tabelas SET {$campos} WHERE id = ?")
                      ->execute([...array_values($valores), $id]);
            return $id;
        }

        $colunas = implode(', ', array_keys($valores));
        $marcas  = implode(', ', array_fill(0, count($valores), '?'));
        $this->pdo->prepare("INSERT INTO com_tabelas ({$colunas}) VALUES ({$marcas})")
                  ->execute(array_values($valores));

        return (int) $this->pdo->lastInsertId();
    }

    public function excluirTabela(int $id): bool
    {
        return $this->pdo->prepare('DELETE FROM com_tabelas WHERE id = ?')->execute([$id]);
    }

    /** Já existe esta trinca convênio + edital + vigência? */
    public function tabelaRepetida(array $d, int $excetoId = 0): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM com_tabelas
              WHERE convenio = ? AND referencia = ? AND vigencia = ? AND id <> ?'
        );
        $stmt->execute([$d['convenio'], $d['referencia'], $d['vigencia'], $excetoId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    // ── Itens ────────────────────────────────────────────────────────────

    private function onde(array $f, array &$params): string
    {
        $w = ['i.tabela_id = ?'];
        $params[] = (int) $f['tabela_id'];

        if (($f['busca'] ?? '') !== '') {
            $like = '%' . like_escape($f['busca']) . '%';
            // Código e descrição na mesma busca: no balcão a pessoa tem na
            // mão ora o número do pedido, ora o nome do exame.
            $w[] = "(i.descricao LIKE ? ESCAPE '!' OR i.codigo LIKE ? ESCAPE '!'
                     OR i.grupo LIKE ? ESCAPE '!')";
            array_push($params, $like, $like, $like);
        }

        if (($f['tipo'] ?? '') !== '' && isset(self::TIPOS[$f['tipo']])) {
            $w[] = 'i.tipo = ?';
            $params[] = $f['tipo'];
        }

        if (($f['grupo'] ?? '') !== '') {
            $w[] = 'i.grupo = ?';
            $params[] = $f['grupo'];
        }

        if (!empty($f['de'])) {
            $w[] = 'i.valor >= ?';
            $params[] = $f['de'];
        }

        if (!empty($f['ate'])) {
            $w[] = 'i.valor <= ?';
            $params[] = $f['ate'];
        }

        if (!empty($f['so_manuais'])) {
            $w[] = "i.origem = 'manual'";
        }

        return ' WHERE ' . implode(' AND ', $w);
    }

    public function listar(array $f, int $limite, int $offset): array
    {
        $params = [];
        $sql = 'SELECT i.* FROM com_itens i'
             . $this->onde($f, $params)
             . ' ORDER BY i.grupo, i.descricao, i.variante
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

        return $stmt->fetchAll();
    }

    public function contar(array $f): int
    {
        $params = [];
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM com_itens i' . $this->onde($f, $params));
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /** Quantos itens de cada tipo a tabela tem — os cartões do topo. */
    public function panorama(int $tabelaId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT tipo, COUNT(*) AS itens FROM com_itens
              WHERE tabela_id = ? GROUP BY tipo'
        );
        $stmt->execute([$tabelaId]);

        $mapa = [];
        foreach ($stmt->fetchAll() as $l) {
            $mapa[$l['tipo']] = (int) $l['itens'];
        }
        return $mapa;
    }

    /** Os grupos existentes na tabela, para o filtro. */
    public function grupos(int $tabelaId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT grupo, COUNT(*) AS itens FROM com_itens
              WHERE tabela_id = ? AND grupo IS NOT NULL
              GROUP BY grupo ORDER BY grupo'
        );
        $stmt->execute([$tabelaId]);
        return $stmt->fetchAll();
    }

    public function itemPorId(int $id): array|false
    {
        $stmt = $this->pdo->prepare(
            'SELECT i.*, t.convenio, t.referencia, t.vigencia
               FROM com_itens i
               JOIN com_tabelas t ON t.id = i.tabela_id
              WHERE i.id = ?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * O mesmo código em outras vigências — é assim que se vê o que o
     * reajuste fez com aquele item.
     */
    public function historico(array $item): array
    {
        if (($item['codigo'] ?? '') === '') {
            return [];
        }

        $stmt = $this->pdo->prepare(
            'SELECT i.valor, i.variante, t.convenio, t.referencia, t.vigencia
               FROM com_itens i
               JOIN com_tabelas t ON t.id = i.tabela_id
              WHERE i.codigo = ? AND t.convenio = ? AND i.tipo = ?
              ORDER BY t.vigencia DESC'
        );
        $stmt->execute([$item['codigo'], $item['convenio'], $item['tipo']]);
        return $stmt->fetchAll();
    }

    public function salvarItem(int $id, array $d): int
    {
        $valores = [
            'tabela_id' => $d['tabela_id'],
            'tipo'      => $d['tipo'],
            'grupo'     => $d['grupo']    !== '' ? $d['grupo']    : null,
            'codigo'    => $d['codigo']   !== '' ? $d['codigo']   : null,
            'descricao' => $d['descricao'],
            'variante'  => $d['variante'] !== '' ? $d['variante'] : null,
            'valor'     => $d['valor'],
            'extras'    => $d['extras'] !== '' ? $d['extras'] : null,
            'usuario_id'   => Auth::id(),
            'usuario_nome' => Auth::nome(),
        ];

        if ($id > 0) {
            $campos = implode(' = ?, ', array_keys($valores)) . ' = ?';
            $this->pdo->prepare("UPDATE com_itens SET {$campos} WHERE id = ?")
                      ->execute([...array_values($valores), $id]);
            return $id;
        }

        // Item criado pela tela nasce 'manual' e sobrevive à recarga do
        // edital — a carga automática só mexe no que ela mesma gravou.
        $valores['origem'] = 'manual';

        $colunas = implode(', ', array_keys($valores));
        $marcas  = implode(', ', array_fill(0, count($valores), '?'));
        $this->pdo->prepare("INSERT INTO com_itens ({$colunas}) VALUES ({$marcas})")
                  ->execute(array_values($valores));

        return (int) $this->pdo->lastInsertId();
    }

    public function excluirItem(int $id): bool
    {
        return $this->pdo->prepare('DELETE FROM com_itens WHERE id = ?')->execute([$id]);
    }

    /** O JSON de `extras` virado em pares rótulo => valor, para a tela. */
    public static function extras(?string $json): array
    {
        if (!$json) {
            return [];
        }

        $dados = json_decode($json, true);
        if (!is_array($dados)) {
            return [];
        }

        $saida = [];
        foreach ($dados as $chave => $valor) {
            if ($valor === null || $valor === '') {
                continue;
            }
            $rotulo = self::EXTRAS[$chave] ?? ucfirst(str_replace('_', ' ', (string) $chave));
            $saida[$rotulo] = is_float($valor) || is_int($valor)
                ? (in_array($chave, ['valor_hm', 'valor_filme'], true)
                    ? dinheiro_br((float) $valor)
                    : (string) $valor)
                : (string) $valor;
        }
        return $saida;
    }
}
