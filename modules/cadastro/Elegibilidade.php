<?php
declare(strict_types=1);

/**
 * As regras de elegibilidade dos planos STANDARD, em um lugar só.
 *
 * Tradução do documento "REGRAS DE ELEGIBILIDADE – PLANOS STANDARD" da APAS
 * Baixada Santista. Fica separada do Model e do Controller de propósito:
 * quando o regulamento mudar, muda este arquivo e mais nada.
 *
 * ── O que o documento deixou em aberto, e como ficou ────────────────────
 * O item 9.1 do documento reconhece que a lista de parentesco do STANDARD 2
 * e do STANDARD 4 é quase igual, e que só o grau de parentesco não decide.
 * A resposta, confirmada pela APAS: o que separa é a ESCOLHA DA FAMÍLIA
 * entre contrato com ou sem coparticipação.
 *
 *   • Sem coparticipação  → todo o grupo vai para o STANDARD 4.
 *   • Com coparticipação  → o titular e o núcleo próximo ficam no
 *     STANDARD 1; os demais parentes, no STANDARD 2.
 *
 * Isso explica a matriz da página 6: toda linha marcada no STANDARD 1 ou no
 * STANDARD 2 também aparece marcada no STANDARD 4, porque o STANDARD 4 é o
 * mesmo produto sem coparticipação, e não uma categoria diferente de gente.
 *
 * A única exceção da matriz é o MENOR SOB GUARDA, marcado só no STANDARD 4
 * — e é assim que está implementado aqui.
 *
 * ── A ordem das regras (item 9.5 do documento) ──────────────────────────
 *   1. Pode ser titular?
 *   2. Qual o grau de parentesco?
 *   3. Qual a idade?
 *   4. Há guarda, tutela ou curatela?
 *   5. Há documentação e dependência econômica?
 *   6. Aplicar a regra do STANDARD correspondente.
 *
 * O motor não grava nada: devolve o plano sugerido, o motivo por escrito e
 * as pendências. Quem confirma é quem cadastra — as zonas cinzentas do
 * regulamento e o fato de o plano mudar o valor da mensalidade pedem um
 * olho humano no meio.
 */
final class Elegibilidade
{
    public const STD1 = 'STANDARD 1';
    public const STD2 = 'STANDARD 2';
    public const STD4 = 'STANDARD 4';

    /** Só estes três podem ser titulares. */
    public const CATEGORIAS = [
        'pm_ativo'    => 'Policial Militar ativo',
        'pm_inativo'  => 'Policial Militar inativo',
        'pensionista' => 'Pensionista de Policial Militar',
    ];

    public const TIPOS_CONTRATO = [
        'com_copart' => 'Com coparticipação',
        'sem_copart' => 'Sem coparticipação',
    ];

    /**
     * Os graus de parentesco do documento, agrupados como ele os agrupa.
     *
     * 'nucleo' marca quem entra no STANDARD 1 num contrato com
     * coparticipação — sozinho ou mediante condição, dita em 'condicao'.
     */
    public const PARENTESCOS = [
        'Cônjuge e companheiro(a)' => [
            'conjuge'      => ['rotulo' => 'Cônjuge',         'nucleo' => true],
            'companheiro'  => ['rotulo' => 'Companheiro(a)',  'nucleo' => true, 'pede' => 'uniao_estavel'],
        ],
        'Filhos e descendentes' => [
            'filho'          => ['rotulo' => 'Filho(a) natural',        'nucleo' => 'menor'],
            'filho_adotivo'  => ['rotulo' => 'Filho(a) adotivo(a)',     'nucleo' => 'menor'],
            'filho_reconhecido' => ['rotulo' => 'Filho(a) legitimado(a) ou reconhecido(a)', 'nucleo' => 'menor'],
            'enteado'        => ['rotulo' => 'Enteado(a)',              'nucleo' => 'menor_com_guarda'],
            'menor_guarda'   => ['rotulo' => 'Menor sob guarda',        'so_std4' => true, 'pede' => 'guarda_judicial'],
            'menor_tutela'   => ['rotulo' => 'Menor sob tutela',        'so_std4' => true, 'pede' => 'tutela_curatela'],
            'neto'           => ['rotulo' => 'Neto(a)',                 'pede' => 'dependencia_economica'],
            'bisneto'        => ['rotulo' => 'Bisneto(a)'],
            'trineto'        => ['rotulo' => 'Trineto(a)'],
        ],
        'Ascendentes' => [
            'pai'      => ['rotulo' => 'Pai'],
            'mae'      => ['rotulo' => 'Mãe'],
            'avo_m'    => ['rotulo' => 'Avô'],
            'avo_f'    => ['rotulo' => 'Avó'],
            'bisavo_m' => ['rotulo' => 'Bisavô'],
            'bisavo_f' => ['rotulo' => 'Bisavó'],
            'trisavo'  => ['rotulo' => 'Trisavô(ó)'],
        ],
        'Irmãos e colaterais' => [
            'irmao'          => ['rotulo' => 'Irmão(ã)'],
            'sobrinho'       => ['rotulo' => 'Sobrinho(a)'],
            'tio'            => ['rotulo' => 'Tio(a)'],
            'tio_avo'        => ['rotulo' => 'Tio-avô(ó)'],
            'sobrinho_neto'  => ['rotulo' => 'Sobrinho(a)-neto(a)'],
            'primo'          => ['rotulo' => 'Primo(a)'],
        ],
        'Afinidade' => [
            'padrasto' => ['rotulo' => 'Padrasto'],
            'madrasta' => ['rotulo' => 'Madrasta'],
            'sogro'    => ['rotulo' => 'Sogro(a)'],
            'genro'    => ['rotulo' => 'Genro'],
            'nora'     => ['rotulo' => 'Nora'],
            'cunhado'  => ['rotulo' => 'Cunhado(a)'],
        ],
        'Outras situações' => [
            'incapaz' => ['rotulo' => 'Absolutamente incapaz sob tutela ou curatela do titular',
                          'pede' => 'tutela_curatela'],
        ],
    ];

    /** O que cada condição quer dizer na tela e no motivo. */
    public const CONDICOES = [
        'guarda_judicial'       => 'guarda legal/judicial comprovada',
        'tutela_curatela'       => 'termo de tutela ou curatela',
        'dependencia_economica' => 'dependência econômica comprovada',
        'uniao_estavel'         => 'união estável comprovada',
        'emancipado'            => 'emancipação',
        'vinculo_comprovado'    => 'comprovação do vínculo familiar',
    ];

    // ── Consultas à lista ────────────────────────────────────────────────

    /** Achata os grupos num mapa chave => rótulo. */
    public static function parentescos(): array
    {
        $mapa = [];
        foreach (self::PARENTESCOS as $itens) {
            foreach ($itens as $chave => $dados) {
                $mapa[$chave] = $dados['rotulo'];
            }
        }
        return $mapa;
    }

    public static function rotuloParentesco(?string $chave): string
    {
        return self::parentescos()[$chave] ?? (string) $chave;
    }

    private static function dados(string $chave): ?array
    {
        foreach (self::PARENTESCOS as $itens) {
            if (isset($itens[$chave])) {
                return $itens[$chave];
            }
        }
        return null;
    }

    // ── O motor ──────────────────────────────────────────────────────────

    /**
     * Classifica uma pessoa.
     *
     * @param array $p  tipo, categoria, parentesco, nascimento e as
     *                  condições (guarda_judicial, tutela_curatela, …)
     * @param string $tipoContrato  'com_copart' | 'sem_copart'
     *
     * @return array{elegivel:bool, plano:?string, motivo:string,
     *               pendencias:array<string>, impedimento:?string}
     */
    public static function avaliar(array $p, string $tipoContrato): array
    {
        $semCopart = $tipoContrato === 'sem_copart';
        $idade     = self::idade($p['nascimento'] ?? null);

        // ── 1. Pode ser titular? ─────────────────────────────────────────
        if (($p['tipo'] ?? 'dependente') === 'titular') {
            $categoria = (string) ($p['categoria'] ?? '');

            if (!isset(self::CATEGORIAS[$categoria])) {
                return self::nao(
                    'Só pode ser titular quem é Policial Militar ativo, Policial Militar'
                    . ' inativo ou pensionista de Policial Militar. Informe a categoria'
                    . ' do titular.'
                );
            }

            $plano = $semCopart ? self::STD4 : self::STD1;

            return self::sim($plano, sprintf(
                '%s em contrato %s.',
                self::CATEGORIAS[$categoria],
                mb_strtolower(self::TIPOS_CONTRATO[$tipoContrato] ?? $tipoContrato)
            ), self::pendencias($p, []));
        }

        // ── 2. Grau de parentesco ────────────────────────────────────────
        $chave = (string) ($p['parentesco'] ?? '');
        $d     = self::dados($chave);

        if ($d === null) {
            return self::nao(
                'Informe o grau de parentesco com o titular — é ele que define'
                . ' em qual plano o dependente entra.'
            );
        }

        $rotulo = $d['rotulo'];

        // ── 3 e 4. Quem só existe no STANDARD 4 ──────────────────────────
        // Menor sob guarda e menor sob tutela aparecem só na coluna do
        // STANDARD 4 na matriz do documento (página 6).
        if (!empty($d['so_std4'])) {
            if (!$semCopart) {
                return self::nao(sprintf(
                    '%s só é aceito no STANDARD 4, que é o contrato sem coparticipação.'
                    . ' Neste contrato, com coparticipação, não há plano que o receba —'
                    . ' seria preciso mudar o tipo do contrato.',
                    $rotulo
                ));
            }

            return self::sim(self::STD4, sprintf(
                '%s em contrato sem coparticipação. É a única situação que o STANDARD 4'
                . ' atende com exclusividade.',
                $rotulo
            ), self::pendencias($p, [$d['pede'] ?? null]));
        }

        // ── Sem coparticipação: o grupo inteiro vai para o STANDARD 4 ────
        if ($semCopart) {
            return self::sim(self::STD4, sprintf(
                '%s em contrato sem coparticipação — no STANDARD 4 entra toda a'
                . ' família elegível, sem separar por grau de parentesco.',
                $rotulo
            ), self::pendencias($p, [$d['pede'] ?? null]));
        }

        // ── Com coparticipação: núcleo no STANDARD 1, resto no STANDARD 2 ─
        $nucleo = $d['nucleo'] ?? false;

        // Cônjuge e companheiro(a): núcleo sempre.
        if ($nucleo === true) {
            return self::sim(self::STD1, sprintf(
                '%s é do núcleo familiar do titular.', $rotulo
            ), self::pendencias($p, [$d['pede'] ?? null]));
        }

        // Filhos: menores no STANDARD 1, maiores no STANDARD 2.
        if ($nucleo === 'menor') {
            if ($idade === null) {
                return self::sim(self::STD2, sprintf(
                    '%s sem data de nascimento informada. Menor de 18 anos entra no'
                    . ' STANDARD 1; sem a data, o sistema não tem como afirmar isso e'
                    . ' fica no STANDARD 2. Informe o nascimento para a classificação'
                    . ' ficar correta.',
                    $rotulo
                ), self::pendencias($p, ['nascimento']));
            }

            if ($idade < 18) {
                return self::sim(self::STD1, sprintf(
                    '%s com %d anos — menor de 18 entra no núcleo, no STANDARD 1.',
                    $rotulo, $idade
                ), self::pendencias($p, [$d['pede'] ?? null]));
            }

            // Confirmado com a APAS: não há idade-limite para filho maior.
            $emancipado = !empty($p['emancipado']) ? ' e emancipado(a)' : '';

            return self::sim(self::STD2, sprintf(
                '%s com %d anos%s — maior de 18 entra no STANDARD 2. Não há'
                . ' idade-limite: continua elegível pagando a faixa etária dele.',
                $rotulo, $idade, $emancipado
            ), self::pendencias($p, [$d['pede'] ?? null]));
        }

        // Enteado(a): a regra específica do item 4.3 do documento.
        if ($nucleo === 'menor_com_guarda') {
            if ($idade === null) {
                return self::sim(self::STD2, sprintf(
                    '%s sem data de nascimento informada. Enteado menor COM guarda'
                    . ' comprovada vai para o STANDARD 1; sem a data não dá para'
                    . ' afirmar, e fica no STANDARD 2.',
                    $rotulo
                ), self::pendencias($p, ['nascimento']));
            }

            if ($idade >= 18) {
                return self::sim(self::STD2, sprintf(
                    '%s com %d anos — enteado maior de 18 entra no STANDARD 2.',
                    $rotulo, $idade
                ), self::pendencias($p, []));
            }

            if (!empty($p['guarda_judicial'])) {
                return self::sim(self::STD1, sprintf(
                    '%s com %d anos e guarda legal/judicial comprovada — pela regra'
                    . ' específica do enteado, entra no STANDARD 1.',
                    $rotulo, $idade
                ), self::pendencias($p, []));
            }

            return self::sim(self::STD2, sprintf(
                '%s com %d anos e SEM guarda legal/judicial comprovada — pela regra'
                . ' específica do enteado, vai para o STANDARD 2. Comprovando a guarda,'
                . ' passa para o STANDARD 1.',
                $rotulo, $idade
            ), self::pendencias($p, ['guarda_judicial']));
        }

        // Todo o resto do parentesco: STANDARD 2.
        return self::sim(self::STD2, sprintf(
            '%s — vínculo elegível ao STANDARD 2 em contrato com coparticipação.',
            $rotulo
        ), self::pendencias($p, [$d['pede'] ?? null]));
    }

    /**
     * O que falta comprovar.
     *
     * Pendência não impede gravar: é lembrete de documento que o
     * regulamento pede. Travar por papel faria a atendente anotar o
     * cadastro num caderno para digitar quando a pasta chegasse.
     */
    private static function pendencias(array $p, array $exigidas): array
    {
        $faltando = [];

        foreach (array_filter($exigidas) as $condicao) {
            if ($condicao === 'nascimento') {
                $faltando[] = 'informar a data de nascimento';
                continue;
            }

            if (empty($p[$condicao])) {
                $faltando[] = self::CONDICOES[$condicao] ?? $condicao;
            }
        }

        // O vínculo comprovado é pedido em toda a matriz do documento
        // ("com vínculo comprovado"), menos para o próprio titular.
        if (($p['tipo'] ?? 'dependente') !== 'titular' && empty($p['vinculo_comprovado'])) {
            $faltando[] = self::CONDICOES['vinculo_comprovado'];
        }

        return $faltando;
    }

    private static function sim(string $plano, string $motivo, array $pendencias): array
    {
        return [
            'elegivel'    => true,
            'plano'       => $plano,
            'motivo'      => $motivo,
            'pendencias'  => $pendencias,
            'impedimento' => null,
        ];
    }

    private static function nao(string $impedimento): array
    {
        return [
            'elegivel'    => false,
            'plano'       => null,
            'motivo'      => '',
            'pendencias'  => [],
            'impedimento' => $impedimento,
        ];
    }

    // ── Idade e faixa etária ─────────────────────────────────────────────

    /** Idade em anos completos na data de referência (hoje, por padrão). */
    public static function idade(?string $nascimento, ?string $em = null): ?int
    {
        if (!$nascimento) {
            return null;
        }

        try {
            $nasc = new DateTimeImmutable($nascimento);
            $ref  = new DateTimeImmutable($em ?: 'today');
        } catch (Exception) {
            return null;
        }

        if ($nasc > $ref) {
            return null;   // nascimento no futuro é erro de digitação
        }

        return (int) $nasc->diff($ref)->y;
    }

    /** As dez faixas da ANS, na ordem. O índice é o que vai para o banco. */
    public const FAIXAS = [
        '00 a 18', '19 a 23', '24 a 28', '29 a 33', '34 a 38',
        '39 a 43', '44 a 48', '49 a 53', '54 a 58', '59 ou mais',
    ];

    /** Em que faixa etária a idade cai. */
    public static function faixa(?int $idade): ?int
    {
        if ($idade === null || $idade < 0) {
            return null;
        }

        return match (true) {
            $idade <= 18 => 0,
            $idade <= 23 => 1,
            $idade <= 28 => 2,
            $idade <= 33 => 3,
            $idade <= 38 => 4,
            $idade <= 43 => 5,
            $idade <= 48 => 6,
            $idade <= 53 => 7,
            $idade <= 58 => 8,
            default      => 9,
        };
    }

    /** A faixa de um nascimento, direto. */
    public static function faixaDe(?string $nascimento, ?string $em = null): ?int
    {
        return self::faixa(self::idade($nascimento, $em));
    }

    public static function rotuloFaixa(?int $faixa): string
    {
        return $faixa === null ? '—' : (self::FAIXAS[$faixa] ?? (string) $faixa);
    }
}
