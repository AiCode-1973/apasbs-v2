<?php
declare(strict_types=1);

/**
 * Auditoria Hospitalar — as contas de internação e de PA/Ambulatório.
 *
 * As tabelas são as do AuditorHosp, no banco dele. Este Model não cria nem
 * renomeia nada lá: trabalha nas colunas como elas estão, porque o sistema
 * antigo continua lendo as mesmas linhas enquanto a migração acontece.
 */
class AuditoriaHospModel
{
    /**
     * Os dois tipos de conta. Têm o mesmo miolo — sete valores e os mesmos
     * percentuais — e diferem no que a conta identifica: internação tem
     * paciente; PA/Ambulatório tem setor.
     *
     * `exige_inicial` diz se o tipo exige que a conta chegue com valor. Hoje
     * os dois exigem, e a chave continua existindo porque a regra é do tipo de
     * conta, não do formulário: já foi diferente entre eles, e voltar a ser é
     * trocar uma palavra aqui — não caçar `if` espalhado pelas telas.
     *
     * No PA ainda há o setor por cima disso: PA/NC e AMB/NC são isentos.
     * Quem junta as duas pontas é `exigeInicial()`, logo abaixo.
     */
    public const TIPOS = [
        'internacao' => [
            'rotulo'   => 'Internação',
            'plural'   => 'Internações',
            'tabela'   => 'internacoes',
            'motivos'  => 'internacao_motivo_glosa',
            'chave'    => 'internacao_id',
            'paciente' => true,
            'setor'    => false,
            'exige_inicial' => true,
        ],
        'pa' => [
            'rotulo'   => 'PA/Ambulatório',
            'plural'   => 'PA/Ambulatório',
            'tabela'   => 'pa_ambulatorio',
            'motivos'  => 'pa_ambulatorio_motivo_glosa',
            'chave'    => 'pa_ambulatorio_id',
            'paciente' => false,
            'setor'    => true,
            'exige_inicial' => true,
        ],
    ];

    /**
     * As situações que os formulários do AuditorHosp oferecem. O campo no banco
     * é texto livre, então uma conta antiga pode trazer outra coisa — a tela
     * mostra o que estiver gravado em vez de fingir que não existe.
     */
    public const SITUACOES = ['Em Aberto', 'Pré análise', 'Auditado'];

    /**
     * A situação que fecha a conta. Tem nome próprio porque uma regra depende
     * dela — só se chega aqui com o valor final preenchido —, e regra presa a
     * uma palavra solta no meio do código some na primeira vez que alguém
     * reescrever o rótulo.
     */
    public const SITUACAO_AUDITADO = 'Auditado';

    /** Os setores usados no PA/Ambulatório. NC = não conformidade. */
    public const SETORES = ['PA', 'AMB', 'PA/NC', 'AMB/NC'];

    /**
     * Os setores de não conformidade, onde a conta chega zerada e ganha valor
     * na auditoria — é o trabalho normal deles, não exceção. São os únicos
     * isentos da exigência do valor inicial.
     */
    public const SETORES_SEM_INICIAL = ['PA/NC', 'AMB/NC'];

    /**
     * Esta conta precisa chegar com valor inicial?
     *
     * A resposta depende de duas coisas — o tipo da conta e, no PA, o setor —
     * e por isso mora aqui, em um lugar só: a validação do servidor, o
     * asterisco do formulário e o aviso ao vivo da tela precisam dizer todos a
     * mesma coisa, ou o usuário vê um campo marcado como obrigatório que grava
     * vazio (ou o contrário, que é pior).
     */
    public static function exigeInicial(string $tipo, string $setor = ''): bool
    {
        $cfg = self::tipo($tipo);

        if (!$cfg['exige_inicial']) {
            return false;
        }

        return !($cfg['setor'] && in_array($setor, self::SETORES_SEM_INICIAL, true));
    }

    private ?PDO $pdo;

    public function __construct()
    {
        $this->pdo = getPDOAuditoria();
    }

    /** O módulo consegue falar com o banco do AuditorHosp? */
    public function conectado(): bool
    {
        return $this->pdo instanceof PDO;
    }

    /**
     * Traduz o apelido do tipo para a configuração dele.
     *
     * As consultas interpolam nome de tabela, então o tipo nunca pode vir
     * direto da URL: só estes dois valores passam, e qualquer outro estoura.
     */
    public static function tipo(string $tipo): array
    {
        return self::TIPOS[$tipo]
            ?? throw new InvalidArgumentException('Tipo de conta desconhecido: ' . $tipo);
    }

    // ── Os números de uma conta ───────────────────────────────────────────

    /**
     * Os quatro percentuais, do jeito que o AuditorHosp calcula — e cada um
     * sobre a sua base, que é o que faz a conta significar alguma coisa:
     * retirado e acrescentado sobre o inicial, glosado sobre o final, aceito
     * sobre o glosado. Divisão por zero devolve null, não zero: "não dá para
     * dizer" é diferente de "é zero".
     *
     * @return array{retirado:?float,acrescentado:?float,glosado:?float,aceito:?float}
     */
    public static function percentuais(array $c): array
    {
        $div = static fn (float $parte, float $base): ?float
            => $base > 0 ? round($parte / $base * 100, 2) : null;

        $inicial = (float) ($c['valor_inicial'] ?? 0);
        $final   = (float) ($c['valor_total'] ?? 0);
        $glosado = (float) ($c['valor_glosado'] ?? 0);

        return [
            'retirado'     => $div((float) ($c['valor_retirado'] ?? 0), $inicial),
            'acrescentado' => $div((float) ($c['valor_acrescentado'] ?? 0), $inicial),
            'glosado'      => $div($glosado, $final),
            'aceito'       => $div((float) ($c['valor_aceito'] ?? 0), $glosado),
        ];
    }

    /**
     * O acrescentado sai dos outros três; ninguém digita.
     *
     * É a mesma identidade de sempre — `final = inicial − retirado +
     * acrescentado` —, resolvida para o outro lado. Quem digita agora é o
     * valor final, que é o número que chega da auditoria; o acrescentado
     * passou a ser a conta que fecha a diferença.
     *
     * Pode dar negativo, e isso não é defeito: quando a conta final ficou
     * abaixo de `inicial − retirado`, houve mais retirada do que o campo
     * retirado conta. O AuditorHosp já gravava assim, e a base importada está
     * cheia desses casos.
     */
    public static function valorAcrescentado(array $d): float
    {
        return round(
            (float) ($d['valor_total'] ?? 0)
            + (float) ($d['valor_retirado'] ?? 0)
            - (float) ($d['valor_inicial'] ?? 0),
            2
        );
    }

    // ── Lista ─────────────────────────────────────────────────────────────

    private function onde(string $tipo, array $f, array &$params): string
    {
        $cfg = self::tipo($tipo);
        $w   = [];

        if (($f['busca'] ?? '') !== '') {
            $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $f['busca']) . '%';

            if ($cfg['paciente']) {
                $w[] = "(c.paciente LIKE ? ESCAPE '\\\\' OR c.guia_paciente LIKE ? ESCAPE '\\\\')";
                array_push($params, $like, $like);
            } else {
                $w[] = "c.guia_paciente LIKE ? ESCAPE '\\\\'";
                $params[] = $like;
            }
        }

        if (($f['status'] ?? '') !== '') {
            $w[] = 'c.status = ?';
            $params[] = $f['status'];
        }

        if (!empty($f['convenio_id'])) {
            $w[] = 'c.convenio_id = ?';
            $params[] = (int) $f['convenio_id'];
        }

        if (($f['setor'] ?? '') !== '' && $cfg['setor']) {
            $w[] = 'c.setor = ?';
            $params[] = $f['setor'];
        }

        // A competência é um mês: no banco é o dia 1 daquele mês.
        if (($f['competencia'] ?? '') !== '') {
            $w[] = "DATE_FORMAT(c.competencia, '%Y-%m') = ?";
            $params[] = $f['competencia'];
        }

        if (!empty($f['so_glosa'])) {
            $w[] = 'c.valor_glosado > 0';
        }

        return $w ? ' WHERE ' . implode(' AND ', $w) : '';
    }

    public function listar(string $tipo, array $f, int $limite, int $offset): array
    {
        if (!$this->conectado()) {
            return [];
        }

        $cfg    = self::tipo($tipo);
        $params = [];

        $sql = 'SELECT c.*, v.nome_convenio AS convenio,
                       (SELECT COUNT(*) FROM `' . $cfg['motivos'] . '` m
                         WHERE m.`' . $cfg['chave'] . '` = c.id) AS motivos
                  FROM `' . $cfg['tabela'] . '` c
                  LEFT JOIN convenios v ON v.id = c.convenio_id'
             . $this->onde($tipo, $f, $params)
             . ' ORDER BY c.competencia DESC, c.id DESC LIMIT ? OFFSET ?';

        $stmt = $this->pdo->prepare($sql);

        $i = 1;
        foreach ($params as $v) {
            $stmt->bindValue($i++, $v);
        }
        // LIMIT/OFFSET como inteiro: com prepare nativo, string quebra no MySQL.
        $stmt->bindValue($i++, $limite, PDO::PARAM_INT);
        $stmt->bindValue($i,   $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * O mesmo recorte da lista, sem paginação — é o que vai para a planilha.
     *
     * Não tem LIMIT de propósito: quem pede a planilha do mês quer o mês
     * inteiro, e uma planilha que para na página 1 é pior do que não existir.
     * O recorte, esse sim, continua valendo: é o mesmo `onde()` da tela, então
     * o arquivo traz exatamente as linhas que estavam ali.
     */
    public function todas(string $tipo, array $f): array
    {
        if (!$this->conectado()) {
            return [];
        }

        $cfg    = self::tipo($tipo);
        $params = [];

        $sql = 'SELECT c.*, v.nome_convenio AS convenio,
                       (SELECT COUNT(*) FROM `' . $cfg['motivos'] . '` m
                         WHERE m.`' . $cfg['chave'] . '` = c.id) AS motivos
                  FROM `' . $cfg['tabela'] . '` c
                  LEFT JOIN convenios v ON v.id = c.convenio_id'
             . $this->onde($tipo, $f, $params)
             . ' ORDER BY c.competencia DESC, c.id DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Os motivos de glosa de várias contas de uma vez, já agrupados por conta.
     *
     * Uma consulta para o lote inteiro, e não uma por linha: a planilha de uma
     * competência de PA tem milhares de contas, e uma consulta por conta
     * transformaria o download em um minuto de espera.
     *
     * O texto é montado aqui, em PHP, e não com GROUP_CONCAT: o `FORMAT()` do
     * MySQL escreve o número no padrão dele, não no nosso, e dinheiro com
     * ponto decimal numa planilha brasileira é exatamente o tipo de detalhe
     * que ninguém percebe até alguém somar errado.
     *
     * @param  int[] $ids
     * @return array<int,string>
     */
    public function motivosDeVarias(string $tipo, array $ids): array
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));

        if (!$this->conectado() || !$ids) {
            return [];
        }

        $cfg  = self::tipo($tipo);
        $stmt = $this->pdo->prepare(
            'SELECT g.`' . $cfg['chave'] . '` AS conta_id, m.codigo, m.descricao, g.valor_glosa
               FROM `' . $cfg['motivos'] . '` g
               JOIN motivos_glosa m ON m.id = g.motivo_glosa_id
              WHERE g.`' . $cfg['chave'] . '` IN (' . implode(',', array_fill(0, count($ids), '?')) . ')
              ORDER BY g.`' . $cfg['chave'] . '`, m.codigo'
        );
        $stmt->execute($ids);

        $por = [];
        foreach ($stmt->fetchAll() as $l) {
            $por[(int) $l['conta_id']][] = sprintf(
                '%s %s (%s)', $l['codigo'], $l['descricao'], dinheiro_br((float) $l['valor_glosa'])
            );
        }

        return array_map(static fn (array $itens): string => implode(' | ', $itens), $por);
    }

    public function contar(string $tipo, array $f): int
    {
        if (!$this->conectado()) {
            return 0;
        }

        $cfg    = self::tipo($tipo);
        $params = [];
        $sql    = 'SELECT COUNT(*) FROM `' . $cfg['tabela'] . '` c' . $this->onde($tipo, $f, $params);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    /**
     * As somas do recorte que está na tela — não do banco inteiro. Um total que
     * ignora o filtro responde a uma pergunta que ninguém fez.
     */
    public function totais(string $tipo, array $f): array
    {
        $zero = ['contas' => 0, 'inicial' => 0.0, 'final' => 0.0, 'glosado' => 0.0, 'aceito' => 0.0];

        if (!$this->conectado()) {
            return $zero;
        }

        $cfg    = self::tipo($tipo);
        $params = [];

        $sql = 'SELECT COUNT(*) AS contas,
                       COALESCE(SUM(c.valor_inicial), 0) AS inicial,
                       COALESCE(SUM(c.valor_total), 0)   AS final,
                       COALESCE(SUM(c.valor_glosado), 0) AS glosado,
                       COALESCE(SUM(c.valor_aceito), 0)  AS aceito
                  FROM `' . $cfg['tabela'] . '` c' . $this->onde($tipo, $f, $params);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetch() ?: $zero;
    }

    /** Quantas contas em cada situação, para os cartões do topo. */
    public function porStatus(string $tipo): array
    {
        if (!$this->conectado()) {
            return [];
        }

        $cfg = self::tipo($tipo);
        $linhas = $this->pdo->query(
            'SELECT status, COUNT(*) AS total FROM `' . $cfg['tabela'] . '` GROUP BY status'
        )->fetchAll();

        $mapa = [];
        foreach ($linhas as $l) {
            $mapa[(string) $l['status']] = (int) $l['total'];
        }

        return $mapa;
    }

    /** As competências que existem, da mais nova para a mais velha. */
    public function competencias(string $tipo): array
    {
        if (!$this->conectado()) {
            return [];
        }

        $cfg = self::tipo($tipo);

        return $this->pdo->query(
            "SELECT DISTINCT DATE_FORMAT(competencia, '%Y-%m') AS mes
               FROM `" . $cfg['tabela'] . "`
              WHERE competencia IS NOT NULL
              ORDER BY mes DESC"
        )->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    // ── Consolidado ───────────────────────────────────────────────────────

    /**
     * As sete somas de um recorte, com os nomes das colunas de uma conta — é o
     * que deixa `percentuais()` e `valorFinal()` servirem tanto para uma conta
     * quanto para um grupo delas, sem uma segunda conta de percentual por aí.
     *
     * `sem_final` conta as contas que chegaram com valor e saíram com final
     * zerado, ou seja, que ninguém auditou ainda. Elas entram no inicial e não
     * entram no final, então puxam o percentual de glosa para cima: a tela
     * precisa poder avisar em vez de deixar o número mentir sozinho.
     */
    private const SOMAS = "COUNT(*) AS contas,
        SUM(CASE WHEN c.valor_total <= 0 AND c.valor_inicial > 0 THEN 1 ELSE 0 END) AS sem_final,
        COALESCE(SUM(c.valor_inicial), 0)      AS valor_inicial,
        COALESCE(SUM(c.valor_retirado), 0)     AS valor_retirado,
        COALESCE(SUM(c.valor_acrescentado), 0) AS valor_acrescentado,
        COALESCE(SUM(c.valor_total), 0)        AS valor_total,
        COALESCE(SUM(c.valor_glosado), 0)      AS valor_glosado,
        COALESCE(SUM(c.valor_aceito), 0)       AS valor_aceito,
        COALESCE(SUM(c.valor_faturado), 0)     AS valor_faturado";

    /**
     * O consolidado por convênio — somado na hora, a partir das contas.
     *
     * O AuditorHosp guarda tabelas consolidadas à parte, atualizadas por um
     * botão, e elas já divergem das contas em mais de uma competência. Aqui a
     * soma acontece quando a tela abre: nunca desatualiza, e não existe o
     * estado "esqueci de consolidar".
     *
     * @param bool $porSetor abre cada convênio por setor (só faz sentido no PA)
     */
    public function consolidado(string $tipo, array $f, bool $porSetor = false): array
    {
        if (!$this->conectado()) {
            return [];
        }

        $cfg      = self::tipo($tipo);
        $porSetor = $porSetor && $cfg['setor'];
        $params   = [];

        $sql = 'SELECT c.convenio_id, v.nome_convenio AS convenio, '
             . ($porSetor ? 'c.setor, ' : "'' AS setor, ")
             . self::SOMAS
             . ' FROM `' . $cfg['tabela'] . '` c
                 LEFT JOIN convenios v ON v.id = c.convenio_id'
             . $this->onde($tipo, $f, $params)
             . ' GROUP BY c.convenio_id, v.nome_convenio' . ($porSetor ? ', c.setor' : '')
             . ' ORDER BY valor_glosado DESC, v.nome_convenio' . ($porSetor ? ', c.setor' : '');

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /** As mesmas somas, mas por setor do PA — sem separar convênio. */
    public function consolidadoPorSetor(string $tipo, array $f): array
    {
        if (!$this->conectado() || !self::tipo($tipo)['setor']) {
            return [];
        }

        $cfg    = self::tipo($tipo);
        $params = [];

        $sql = 'SELECT c.setor, ' . self::SOMAS
             . ' FROM `' . $cfg['tabela'] . '` c'
             . $this->onde($tipo, $f, $params)
             . ' GROUP BY c.setor ORDER BY valor_glosado DESC, c.setor';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * A série dos últimos meses, da mais velha para a mais nova — é a ordem em
     * que o gráfico lê.
     *
     * A competência do filtro sai fora de propósito: uma evolução de um mês só
     * não é uma evolução. Os outros recortes continuam valendo, senão a série
     * fala de um universo diferente do da tabela ao lado.
     */
    public function evolucao(string $tipo, array $f, int $meses = 12): array
    {
        if (!$this->conectado()) {
            return [];
        }

        $cfg    = self::tipo($tipo);
        $f      = ['competencia' => ''] + $f;
        $params = [];
        $onde   = $this->onde($tipo, $f, $params);

        $sql = "SELECT DATE_FORMAT(c.competencia, '%Y-%m') AS mes, " . self::SOMAS
             . ' FROM `' . $cfg['tabela'] . '` c'
             . ($onde !== '' ? $onde . ' AND' : ' WHERE') . ' c.competencia IS NOT NULL'
             . ' GROUP BY mes ORDER BY mes DESC LIMIT ?';

        $stmt = $this->pdo->prepare($sql);

        $i = 1;
        foreach ($params as $v) {
            $stmt->bindValue($i++, $v);
        }
        $stmt->bindValue($i, $meses, PDO::PARAM_INT);
        $stmt->execute();

        return array_reverse($stmt->fetchAll());
    }

    /**
     * A soma de uma lista de grupos, para a linha de total da tabela.
     *
     * Somar as linhas que estão na tela é diferente de pedir o total ao banco
     * de novo: o rodapé fecha com o que está acima dele, sempre.
     */
    public static function somar(array $linhas): array
    {
        $total = [
            'contas' => 0, 'sem_final' => 0, 'valor_inicial' => 0.0,
            'valor_retirado' => 0.0, 'valor_acrescentado' => 0.0, 'valor_total' => 0.0,
            'valor_glosado' => 0.0, 'valor_aceito' => 0.0, 'valor_faturado' => 0.0,
        ];

        foreach ($linhas as $l) {
            foreach ($total as $campo => $_) {
                $total[$campo] += $l[$campo] ?? 0;
            }
        }

        $total['contas']    = (int) $total['contas'];
        $total['sem_final'] = (int) $total['sem_final'];

        return $total;
    }

    // ── Uma conta ─────────────────────────────────────────────────────────

    public function porId(string $tipo, int $id): array|false
    {
        if (!$this->conectado()) {
            return false;
        }

        $cfg  = self::tipo($tipo);
        $stmt = $this->pdo->prepare(
            'SELECT c.*, v.nome_convenio AS convenio
               FROM `' . $cfg['tabela'] . '` c
               LEFT JOIN convenios v ON v.id = c.convenio_id
              WHERE c.id = ?'
        );
        $stmt->execute([$id]);

        return $stmt->fetch();
    }

    /** As colunas que o formulário grava, na ordem, para cada tipo. */
    private function colunas(string $tipo): array
    {
        $cfg = self::tipo($tipo);

        $comuns = [
            'data_recebimento', 'competencia', 'convenio_id', 'guia_paciente',
            'data_entrada', 'data_saida', 'valor_inicial', 'valor_retirado',
            'valor_acrescentado', 'valor_total', 'valor_glosado', 'valor_aceito',
            'valor_faturado', 'falta_nf', 'status', 'observacoes',
        ];

        if ($cfg['paciente']) {
            array_unshift($comuns, 'paciente');
        }
        if ($cfg['setor']) {
            array_unshift($comuns, 'setor');
        }

        return $comuns;
    }

    public function criar(string $tipo, array $d): int
    {
        $cfg     = self::tipo($tipo);
        $colunas = $this->colunas($tipo);

        $sql = 'INSERT INTO `' . $cfg['tabela'] . '` (`' . implode('`, `', $colunas) . '`)'
             . ' VALUES (' . implode(', ', array_fill(0, count($colunas), '?')) . ')';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->valores($tipo, $d));

        return (int) $this->pdo->lastInsertId();
    }

    public function atualizar(string $tipo, int $id, array $d): bool
    {
        $cfg     = self::tipo($tipo);
        $colunas = $this->colunas($tipo);

        $sql = 'UPDATE `' . $cfg['tabela'] . '` SET '
             . implode(', ', array_map(static fn ($c) => "`$c` = ?", $colunas))
             . ' WHERE id = ?';

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([...$this->valores($tipo, $d), $id]);
    }

    /** Os valores na ordem das colunas, em um lugar só. */
    private function valores(string $tipo, array $d): array
    {
        $cfg = self::tipo($tipo);

        $v = [
            $d['data_recebimento'] ?: null,
            $d['competencia'] ?: null,
            (int) $d['convenio_id'],
            $d['guia_paciente'] !== '' ? $d['guia_paciente'] : null,
            $d['data_entrada'] ?: null,
            $d['data_saida'] ?: null,
            $d['valor_inicial'],
            $d['valor_retirado'],
            // O acrescentado não vem da tela: é calculado dos outros três.
            self::valorAcrescentado($d),
            $d['valor_total'],
            $d['valor_glosado'],
            $d['valor_aceito'],
            $d['valor_faturado'],
            $d['falta_nf'] === 'Sim' ? 'Sim' : 'Não',
            $d['status'],
            $d['observacoes'] !== '' ? $d['observacoes'] : null,
        ];

        if ($cfg['paciente']) {
            array_unshift($v, $d['paciente']);
        }
        if ($cfg['setor']) {
            array_unshift($v, $d['setor']);
        }

        return $v;
    }

    public function excluir(string $tipo, int $id): bool
    {
        $cfg = self::tipo($tipo);

        // As tabelas de motivo não têm ON DELETE CASCADE no banco do
        // AuditorHosp — então a limpeza é aqui, senão sobra motivo apontando
        // para uma conta que não existe mais.
        $this->pdo->prepare(
            'DELETE FROM `' . $cfg['motivos'] . '` WHERE `' . $cfg['chave'] . '` = ?'
        )->execute([$id]);

        return $this->pdo->prepare('DELETE FROM `' . $cfg['tabela'] . '` WHERE id = ?')->execute([$id]);
    }

    // ── Motivos de glosa da conta ─────────────────────────────────────────

    /** O cadastro padronizado, para escolher na tela. */
    public function motivosDisponiveis(): array
    {
        if (!$this->conectado()) {
            return [];
        }

        return $this->pdo->query(
            'SELECT id, codigo, grupo, descricao FROM motivos_glosa WHERE ativo = 1 ORDER BY codigo'
        )->fetchAll();
    }

    public function motivosDaConta(string $tipo, int $id): array
    {
        if (!$this->conectado()) {
            return [];
        }

        $cfg  = self::tipo($tipo);
        $stmt = $this->pdo->prepare(
            'SELECT g.*, m.codigo, m.grupo, m.descricao
               FROM `' . $cfg['motivos'] . '` g
               JOIN motivos_glosa m ON m.id = g.motivo_glosa_id
              WHERE g.`' . $cfg['chave'] . '` = ?
              ORDER BY g.data_registro, g.id'
        );
        $stmt->execute([$id]);

        return $stmt->fetchAll();
    }

    public function somaMotivos(string $tipo, int $id): float
    {
        if (!$this->conectado()) {
            return 0.0;
        }

        $cfg  = self::tipo($tipo);
        $stmt = $this->pdo->prepare(
            'SELECT COALESCE(SUM(valor_glosa), 0) FROM `' . $cfg['motivos'] . '`
              WHERE `' . $cfg['chave'] . '` = ?'
        );
        $stmt->execute([$id]);

        return (float) $stmt->fetchColumn();
    }

    public function adicionarMotivo(string $tipo, int $id, int $motivoId, float $valor, string $obs): bool
    {
        $cfg = self::tipo($tipo);

        $stmt = $this->pdo->prepare(
            'INSERT INTO `' . $cfg['motivos'] . '`
                (`' . $cfg['chave'] . '`, motivo_glosa_id, valor_glosa, observacao, usuario_id)
             VALUES (?, ?, ?, ?, ?)'
        );

        // usuario_id é o do APASBS: quem está logado aqui é quem registrou.
        return $stmt->execute([$id, $motivoId, $valor, $obs !== '' ? $obs : null, Auth::id()]);
    }

    public function motivoPorId(string $tipo, int $motivoContaId): array|false
    {
        if (!$this->conectado()) {
            return false;
        }

        $cfg  = self::tipo($tipo);
        $stmt = $this->pdo->prepare(
            'SELECT g.*, m.codigo, m.descricao
               FROM `' . $cfg['motivos'] . '` g
               JOIN motivos_glosa m ON m.id = g.motivo_glosa_id
              WHERE g.id = ?'
        );
        $stmt->execute([$motivoContaId]);

        return $stmt->fetch();
    }

    public function excluirMotivo(string $tipo, int $motivoContaId): bool
    {
        $cfg = self::tipo($tipo);

        return $this->pdo->prepare(
            'DELETE FROM `' . $cfg['motivos'] . '` WHERE id = ?'
        )->execute([$motivoContaId]);
    }

    // ── Apoio ─────────────────────────────────────────────────────────────

    public function convenios(): array
    {
        if (!$this->conectado()) {
            return [];
        }

        return $this->pdo->query('SELECT id, nome_convenio FROM convenios ORDER BY nome_convenio')->fetchAll();
    }

    public function convenioExiste(int $id): bool
    {
        if (!$this->conectado()) {
            return false;
        }

        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM convenios WHERE id = ?');
        $stmt->execute([$id]);

        return (int) $stmt->fetchColumn() > 0;
    }
}
