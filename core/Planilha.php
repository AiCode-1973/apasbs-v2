<?php
declare(strict_types=1);

/**
 * Planilha para baixar: um .xlsx de verdade, escrito à mão.
 *
 * O APASBS não tem Composer, e uma biblioteca de planilha é a maior
 * dependência que o projeto teria — para gerar uma tabela de células de texto,
 * número e data. Um .xlsx é um zip com alguns XMLs dentro, e é isso que esta
 * classe monta: nada para instalar, nada para atualizar.
 *
 * Por que não CSV, que seria três linhas: no CSV todo valor chega como texto.
 * Quem recebe a planilha quer somar a coluna de glosa e filtrar por
 * competência no mesmo minuto em que abre o arquivo — e com texto isso vira
 * uma conversa sobre separador decimal. Aqui número é número e data é data.
 *
 *     $p = new Planilha('Contas');
 *     $p->cabecalho(['Guia', 'Paciente', 'Competência', 'Valor inicial']);
 *     $p->linha(['123', 'Maria', new DateTimeImmutable('2026-08-01'), 12480.00],
 *               ['texto', 'texto', 'mes', 'moeda']);
 *     $p->baixar('contas-08-2026.xlsx');
 *
 * Os tipos de coluna são: `texto`, `numero`, `moeda`, `inteiro`, `pct`,
 * `data` e `mes`. Quem passa menos tipos do que colunas ganha `texto` no
 * resto — é o que acontece quando alguém acrescenta uma coluna e esquece.
 */
class Planilha
{
    /**
     * Os formatos numéricos, na ordem em que entram no styles.xml. O índice
     * aqui é o índice do estilo lá, e é por isso que a ordem não muda.
     */
    private const FORMATOS = [
        'texto'   => null,
        'numero'  => '#,##0.00',
        'moeda'   => 'R$\ #,##0.00',
        'inteiro' => '#,##0',
        'pct'     => '0.00%',
        'data'    => 'dd/mm/yyyy',
        'mes'     => 'mm/yyyy',
    ];

    private string $aba;
    private array  $linhas = [];
    private int    $colunas = 0;
    private bool   $temCabecalho = false;

    public function __construct(string $aba = 'Planilha')
    {
        // O Excel recusa aba com esses caracteres, e com mais de 31 letras.
        $this->aba = mb_substr(str_replace(['\\', '/', '*', '[', ']', ':', '?'], '-', $aba), 0, 31);
    }

    /** A primeira linha, que vira o cabeçalho congelado e com filtro. */
    public function cabecalho(array $titulos): void
    {
        $this->temCabecalho = true;
        $this->linha($titulos, array_fill(0, count($titulos), 'texto'));
    }

    public function linha(array $valores, array $tipos = []): void
    {
        $this->colunas = max($this->colunas, count($valores));
        $this->linhas[] = [array_values($valores), array_values($tipos)];
    }

    /**
     * Manda o arquivo para o navegador e encerra.
     *
     * Sem a extensão zip não há .xlsx possível — e ficar sem download nenhum
     * seria pior do que baixar um CSV. Então cai para CSV com BOM e ponto e
     * vírgula, que é o que o Excel em português abre com dois cliques.
     */
    public function baixar(string $nome): never
    {
        if (!class_exists('ZipArchive')) {
            $this->baixarCsv(preg_replace('/\.xlsx$/i', '.csv', $nome) ?? 'planilha.csv');
        }

        $conteudo = $this->montar();

        header_remove('Content-Security-Policy');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $this->nomeSeguro($nome) . '"');
        header('Content-Length: ' . strlen($conteudo));
        header('Cache-Control: no-store');
        header('X-Content-Type-Options: nosniff');

        echo $conteudo;
        exit;
    }

    // ── O arquivo ────────────────────────────────────────────────────────

    private function montar(): string
    {
        // O ZipArchive quer um caminho em disco; o arquivo morre logo em
        // seguida. sys_get_temp_dir() porque é o único lugar em que o
        // processo do PHP tem escrita garantida em qualquer hospedagem.
        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive();
        $zip->open($tmp, ZipArchive::OVERWRITE);

        $zip->addFromString('[Content_Types].xml', $this->contentTypes());
        $zip->addFromString('_rels/.rels', $this->rels());
        $zip->addFromString('xl/workbook.xml', $this->workbook());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRels());
        $zip->addFromString('xl/styles.xml', $this->styles());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->sheet());
        $zip->close();

        $conteudo = (string) file_get_contents($tmp);
        @unlink($tmp);

        return $conteudo;
    }

    private function contentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>';
    }

    private function rels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
    }

    private function workbook(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
            . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="' . htmlspecialchars($this->aba, ENT_XML1) . '" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>';
    }

    private function workbookRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';
    }

    /**
     * Os estilos: um por tipo de coluna, na ordem de FORMATOS, e o último é o
     * do cabeçalho — negrito sobre fundo claro.
     */
    private function styles(): string
    {
        $numFmts = '';
        $xfs     = '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'; // texto
        $id      = 164; // os abaixo de 164 são reservados pelo próprio Excel

        foreach (self::FORMATOS as $fmt) {
            if ($fmt === null) {
                continue;
            }
            $numFmts .= '<numFmt numFmtId="' . $id . '" formatCode="' . htmlspecialchars($fmt, ENT_XML1) . '"/>';
            $xfs     .= '<xf numFmtId="' . $id . '" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>';
            $id++;
        }

        $xfs .= '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<numFmts count="' . ($id - 164) . '">' . $numFmts . '</numFmts>'
            . '<fonts count="2">'
            . '<font><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FF0D1C3C"/><name val="Calibri"/></font>'
            . '</fonts>'
            . '<fills count="3">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFEEF2F8"/><bgColor indexed="64"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="1"><border/></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="' . (substr_count($xfs, '<xf ')) . '">' . $xfs . '</cellXfs>'
            // O estilo "Normal" tem de existir: sem ele há leitor de planilha
            // que reclama de arquivo sem estilo padrão antes de abrir.
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }

    private function sheet(): string
    {
        $xml = '';

        foreach ($this->linhas as $n => [$valores, $tipos]) {
            $cabecalho = $this->temCabecalho && $n === 0;
            $xml .= '<row r="' . ($n + 1) . '">';

            foreach ($valores as $i => $valor) {
                $tipo = $tipos[$i] ?? 'texto';
                $xml .= $this->celula($this->coluna($i) . ($n + 1), $valor, $tipo, $cabecalho);
            }

            $xml .= '</row>';
        }

        $ref = 'A1:' . $this->coluna(max(0, $this->colunas - 1)) . max(1, count($this->linhas));

        // Cabeçalho congelado e autofiltro: quem abre a planilha rola mil
        // linhas e continua sabendo em que coluna está.
        $painel = $this->temCabecalho
            ? '<sheetViews><sheetView workbookViewId="0" tabSelected="1">'
              . '<pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/>'
              . '</sheetView></sheetViews>'
            : '';

        $filtro = $this->temCabecalho ? '<autoFilter ref="' . $ref . '"/>' : '';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . $painel
            . '<cols><col min="1" max="' . max(1, $this->colunas) . '" width="18" customWidth="1"/></cols>'
            . '<sheetData>' . $xml . '</sheetData>'
            . $filtro
            . '</worksheet>';
    }

    private function celula(string $ref, mixed $valor, string $tipo, bool $cabecalho): string
    {
        $estilo = $cabecalho ? count(self::FORMATOS) : $this->estilo($tipo);
        $s      = $estilo > 0 ? ' s="' . $estilo . '"' : '';

        if ($valor === null || $valor === '') {
            return '<c r="' . $ref . '"' . $s . '/>';
        }

        if (in_array($tipo, ['data', 'mes'], true)) {
            $serie = $this->serieExcel($valor);
            if ($serie !== null) {
                return '<c r="' . $ref . '"' . $s . '><v>' . $serie . '</v></c>';
            }
        }

        if (in_array($tipo, ['numero', 'moeda', 'inteiro', 'pct'], true) && is_numeric($valor)) {
            // O XML quer ponto decimal, sempre — o que a tela mostra é outra
            // conversa, e quem faz essa é o Excel, pelo formato da célula.
            return '<c r="' . $ref . '"' . $s . '><v>' . rtrim(rtrim(number_format((float) $valor, 6, '.', ''), '0'), '.') . '</v></c>';
        }

        // Texto vai inline: sem tabela de strings compartilhadas, que é
        // otimização de tamanho e aqui só traria mais um XML para manter.
        return '<c r="' . $ref . '"' . $s . ' t="inlineStr"><is><t xml:space="preserve">'
             . htmlspecialchars((string) $valor, ENT_XML1, 'UTF-8')
             . '</t></is></c>';
    }

    /** O índice do estilo de cada tipo, que é a posição dele em FORMATOS. */
    private function estilo(string $tipo): int
    {
        $i = array_search($tipo, array_keys(self::FORMATOS), true);

        return $i === false ? 0 : (int) $i;
    }

    /**
     * Data → o número de dias desde 31/12/1899, que é como o Excel guarda
     * data. O 1900 do Excel tem um 29 de fevereiro que nunca existiu, e por
     * isso a contagem parte de 1899-12-30: o erro dele já vem embutido aí.
     */
    private function serieExcel(mixed $valor): ?int
    {
        $d = $valor instanceof DateTimeInterface
            ? $valor
            : (is_string($valor) ? DateTimeImmutable::createFromFormat('Y-m-d', substr($valor, 0, 10)) : false);

        if (!$d) {
            return null;
        }

        $base = new DateTimeImmutable('1899-12-30 00:00:00');

        return (int) $base->diff(
            new DateTimeImmutable($d->format('Y-m-d') . ' 00:00:00')
        )->format('%r%a');
    }

    /** 0 → A, 25 → Z, 26 → AA. */
    private function coluna(int $i): string
    {
        $nome = '';
        $i++;

        while ($i > 0) {
            $resto = ($i - 1) % 26;
            $nome  = chr(65 + $resto) . $nome;
            $i     = intdiv($i - 1 - $resto, 26);
        }

        return $nome;
    }

    private function nomeSeguro(string $nome): string
    {
        $nome = preg_replace('/[^A-Za-z0-9._-]+/', '-', $nome) ?? 'planilha.xlsx';

        return trim($nome, '-') !== '' ? $nome : 'planilha.xlsx';
    }

    // ── A saída de emergência ────────────────────────────────────────────

    private function baixarCsv(string $nome): never
    {
        header_remove('Content-Security-Policy');
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $this->nomeSeguro($nome) . '"');
        header('Cache-Control: no-store');
        header('X-Content-Type-Options: nosniff');

        // BOM: sem ele o Excel em Windows lê UTF-8 como se fosse Latin-1 e
        // todo acento vira caractere estranho.
        echo "\xEF\xBB\xBF";

        $saida = fopen('php://output', 'w');

        foreach ($this->linhas as [$valores, $tipos]) {
            $linha = [];

            foreach ($valores as $i => $valor) {
                $tipo = $tipos[$i] ?? 'texto';

                if ($tipo === 'pct' && is_numeric($valor)) {
                    // No .xlsx o percentual é fração, porque quem multiplica
                    // por cem é o formato da célula. No CSV não há formato:
                    // o número tem de sair já em por cento.
                    $linha[] = number_format((float) $valor * 100, 2, ',', '');
                } elseif (in_array($tipo, ['numero', 'moeda'], true) && is_numeric($valor)) {
                    $linha[] = number_format((float) $valor, 2, ',', '');
                } elseif (in_array($tipo, ['data', 'mes'], true) && $valor) {
                    $d = $valor instanceof DateTimeInterface
                        ? $valor
                        : new DateTimeImmutable((string) $valor);
                    $linha[] = $d->format($tipo === 'mes' ? 'm/Y' : 'd/m/Y');
                } else {
                    $linha[] = (string) $valor;
                }
            }

            fputcsv($saida, $linha, ';', '"', '\\');
        }

        fclose($saida);
        exit;
    }
}
