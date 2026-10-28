<?php
declare(strict_types=1);

/**
 * Módulo instalado no código, mas sem as tabelas no banco.
 *
 * Acontece toda vez que um módulo novo sobe para o servidor e o script do
 * banco ainda não foi rodado: o PHP morre com um "Fatal error" cheio de
 * caminho de arquivo e rastro de pilha, que não diz à pessoa o que fazer e
 * ainda conta ao mundo onde o sistema mora.
 *
 * Aqui a mesma situação vira uma página que diz qual tabela falta e qual
 * script a cria — e o resto do sistema continua funcionando.
 */
final class Instalacao
{
    /** Arquivos grandes de carga de dados não declaram tabela; não vale lê-los. */
    private const LIMITE_LEITURA = 524288; // 512 KB

    /**
     * Extrai o nome da tabela da mensagem do MySQL:
     * "Base table or view not found: 1146 Table 'banco.vd_vendedores' doesn't exist"
     */
    public static function tabelaFaltando(PDOException $e): ?string
    {
        if (($e->errorInfo[1] ?? null) !== 1146 && !str_contains($e->getMessage(), '1146')) {
            return null;
        }

        if (!preg_match("/Table '([^']+)' doesn't exist/i", $e->getMessage(), $m)) {
            return null;
        }

        $partes = explode('.', $m[1]);

        return trim((string) end($partes), '`');
    }

    /**
     * Procura, entre os scripts de sql/, qual deles cria essa tabela.
     * É uma busca no texto porque é o que não envelhece: script novo entra na
     * pasta e passa a ser encontrado sem ninguém alimentar uma lista aqui.
     */
    public static function scriptDaTabela(string $tabela): ?string
    {
        $tabela = preg_replace('/[^a-z0-9_]/i', '', $tabela) ?? '';

        if ($tabela === '') {
            return null;
        }

        foreach (glob(BASE_PATH . '/sql/*.sql') ?: [] as $arquivo) {
            if ((int) @filesize($arquivo) > self::LIMITE_LEITURA) {
                continue;
            }

            $sql = (string) @file_get_contents($arquivo);

            if (preg_match('/CREATE\s+TABLE\s+(IF\s+NOT\s+EXISTS\s+)?`?' . preg_quote($tabela, '/') . '`?\s*\(/i', $sql)) {
                return 'sql/' . basename($arquivo);
            }
        }

        return null;
    }

    /** Página de aviso. Encerra a requisição. */
    public static function pagina(string $modulo, string $tabela, ?string $script): never
    {
        http_response_code(503);
        header('Content-Type: text/html; charset=utf-8');

        $nome   = (string) (Modules::get($modulo)['nome'] ?? $modulo);
        $comando = $script
            ? 'mysql -u usuario -p apasbs < ' . $script
            : 'Rode os scripts de sql/ que ainda não foram executados.';

        $e = static fn (string $t): string => htmlspecialchars($t, ENT_QUOTES, 'UTF-8');

        echo '<!doctype html><html lang="pt-BR"><head><meta charset="utf-8">'
           . '<meta name="viewport" content="width=device-width, initial-scale=1">'
           . '<title>Módulo ainda não instalado — APASBS</title><style>'
           . 'body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;'
           . 'background:#f1f5f9;font:15px/1.6 system-ui,-apple-system,"Segoe UI",sans-serif;color:#1e293b;padding:24px}'
           . '.caixa{background:#fff;border:1px solid #e2e8f0;border-radius:12px;max-width:560px;padding:28px 30px;'
           . 'box-shadow:0 10px 30px rgba(15,23,42,.06)}'
           . 'h1{margin:0 0 10px;font-size:19px;color:#0d1c3c}'
           . 'p{margin:0 0 12px}code{background:#f1f5f9;border-radius:4px;padding:1px 5px;font-size:13px}'
           . 'pre{background:#0d1c3c;color:#e2e8f0;border-radius:8px;padding:12px 14px;overflow:auto;font-size:13px;margin:0 0 14px}'
           . 'a{color:#2a4b96}'
           . '</style></head><body><div class="caixa">'
           . '<h1>O módulo ' . $e($nome) . ' ainda não foi instalado</h1>'
           . '<p>O código já está no servidor, mas a tabela <code>' . $e($tabela)
           . '</code> não existe neste banco. Rode o script que a cria:</p>'
           . '<pre>' . $e($comando) . '</pre>'
           . '<p>Depois recarregue esta página. O resto do sistema continua funcionando normalmente.</p>'
           . '<p><a href="' . $e(BASE_URL) . '/">Voltar ao início</a></p>'
           . '</div></body></html>';

        exit;
    }
}
