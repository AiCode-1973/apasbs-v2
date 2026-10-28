<?php
declare(strict_types=1);

/**
 * O rodapé de paginação das listagens.
 *
 * Uma implementação só para o sistema inteiro: paginação diferente em cada
 * tela obriga quem usa a reaprender o mesmo gesto várias vezes.
 *
 * O formato é: à esquerda o intervalo ("41–60 de 265 notas") e quantas linhas
 * por página; à direita os números, com a primeira e a última sempre à vista.
 *
 *     echo Paginacao::barra([
 *         'modulo'    => 'opme',
 *         'action'    => 'lista',
 *         'pagina'    => $pagina,
 *         'total'     => $total,
 *         'porPagina' => $porPagina,
 *         'base'      => $filtrosDaUrl,   // filtros que precisam sobreviver
 *         'rotulo'    => ['nota', 'notas'],
 *     ]);
 */
class Paginacao
{
    /** As opções do seletor de quantidade. */
    public const TAMANHOS = [20, 50, 100];

    /** Quantos vizinhos aparecem de cada lado da página atual. */
    private const VIZINHOS = 2;

    /**
     * Lê e valida o `por` da URL. Fora da lista de tamanhos, vale o padrão —
     * senão um `?por=999999` vira um SELECT sem limite prático.
     */
    public static function porPagina(int $padrao = 20): int
    {
        $pedido = (int) ($_GET['por'] ?? 0);
        return in_array($pedido, self::TAMANHOS, true) ? $pedido : $padrao;
    }

    public static function barra(array $cfg): string
    {
        $modulo    = (string) ($cfg['modulo'] ?? '');
        $action    = (string) ($cfg['action'] ?? 'lista');
        $total     = max(0, (int) ($cfg['total'] ?? 0));
        $porPagina = max(1, (int) ($cfg['porPagina'] ?? 20));
        $paginas   = max(1, (int) ceil($total / $porPagina));
        $pagina    = min(max(1, (int) ($cfg['pagina'] ?? 1)), $paginas);
        $base      = (array) ($cfg['base'] ?? []);
        $seletor   = $cfg['seletor'] ?? true;
        // Nem toda tela chama o parâmetro de "pagina" — o TUSS usa "p".
        $param     = (string) ($cfg['param'] ?? 'pagina');
        $padrao    = (int) ($cfg['padrao'] ?? self::TAMANHOS[0]);
        [$sing, $plur] = (array) ($cfg['rotulo'] ?? ['registro', 'registros']);

        // O seletor só faz sentido quando existe mais de um tamanho possível
        // para o que está na tela.
        $mostrarSeletor = $seletor && $total > min(self::TAMANHOS);

        // Uma página só e nada a escolher: o rodapé seria uma linha vazia.
        if ($paginas <= 1 && !$mostrarSeletor) {
            return '';
        }

        $primeiro = $total === 0 ? 0 : ($pagina - 1) * $porPagina + 1;
        $ultimo   = min($total, $pagina * $porPagina);

        // Página 1 e tamanho padrão não entram na URL: assim o link da lista
        // é sempre o mesmo endereço, e não uma variação com ?pagina=1&por=20.
        $link = static function (int $p, ?int $por = null)
                use ($modulo, $action, $base, $porPagina, $param, $padrao): string {
            $q = $base;
            if ($p > 1) { $q[$param] = $p; }
            $usar = $por ?? $porPagina;
            if ($usar !== $padrao) { $q['por'] = $usar; }
            return url($modulo, $action, $q);
        };

        $html  = '<nav class="toolbar-rodape" aria-label="Paginação">';

        // ── Esquerda: o intervalo e quantas por página ────────────────────
        $html .= '<span class="flex flex-wrap items-center gap-2">';
        if ($total === 0) {
            $html .= '<span>nenhum ' . e($sing) . '</span>';
        } else {
            $html .= '<span><strong class="font-semibold text-slate-700 dark:text-slate-200">'
                   . numero($primeiro) . '–' . numero($ultimo) . '</strong> de '
                   . numero($total) . ' ' . e($total === 1 ? $sing : $plur) . '</span>';
        }

        if ($mostrarSeletor) {
            // Sem onchange inline: a CSP do sistema não permite script embutido
            // no HTML. Quem escuta é o app.js, pelo data-navegar.
            $html .= '<select class="select input-sm w-auto py-0.5 text-xs" data-navegar'
                   . ' aria-label="Linhas por página">';
            foreach (self::TAMANHOS as $t) {
                // Ao trocar o tamanho volta-se para a primeira página: manter o
                // número faria "página 12 de 5" e uma tela vazia.
                $html .= '<option value="' . e($link(1, $t)) . '"'
                       . ($t === $porPagina ? ' selected' : '') . '>'
                       . $t . ' por página</option>';
            }
            $html .= '</select>';
        }
        $html .= '</span>';

        // ── Direita: os números ───────────────────────────────────────────
        if ($paginas > 1) {
            $html .= '<span class="flex flex-wrap items-center gap-1">';
            $html .= self::seta($link, $pagina - 1, $pagina > 1, 'anterior', 'Página anterior');

            foreach (self::janela($pagina, $paginas) as $item) {
                if ($item === null) {
                    $html .= '<span class="pag-reticencia" aria-hidden="true">…</span>';
                } elseif ($item === $pagina) {
                    $html .= '<span class="btn-pag btn-pag-atual" aria-current="page">' . $item . '</span>';
                } else {
                    $html .= '<a href="' . e($link($item)) . '" class="btn-pag"'
                           . ' aria-label="Página ' . $item . '">' . $item . '</a>';
                }
            }

            $html .= self::seta($link, $pagina + 1, $pagina < $paginas, 'proximo', 'Próxima página');
            $html .= '</span>';
        }

        return $html . '</nav>';
    }

    /**
     * Os números que aparecem: primeira, última, os vizinhos da atual e
     * `null` onde entram as reticências.
     *
     * A janela tem largura fixa — sem isso a barra encolhe e cresce a cada
     * clique, e os números fogem de baixo do cursor.
     */
    private static function janela(int $pagina, int $paginas): array
    {
        $largura = self::VIZINHOS * 2 + 1;

        if ($paginas <= $largura + 2) {
            return range(1, $paginas);
        }

        $de  = max(2, min($pagina - self::VIZINHOS, $paginas - $largura));
        $ate = min($paginas - 1, max($pagina + self::VIZINHOS, $largura + 1));

        $itens = [1];
        if ($de > 2) {
            $itens[] = null;
        }
        for ($p = $de; $p <= $ate; $p++) {
            $itens[] = $p;
        }
        if ($ate < $paginas - 1) {
            $itens[] = null;
        }
        $itens[] = $paginas;

        return $itens;
    }

    /** Seta de anterior/próxima. Desligada vira cinza, sem link. */
    private static function seta(callable $link, int $destino, bool $ativa, string $icone, string $rotulo): string
    {
        $svg = Icon::svg($icone, 'w-3.5 h-3.5');

        if (!$ativa) {
            return '<span class="btn-pag opacity-30" aria-hidden="true">' . $svg . '</span>';
        }

        return '<a href="' . e($link($destino)) . '" class="btn-pag" aria-label="' . e($rotulo) . '">'
             . $svg . '</a>';
    }
}
