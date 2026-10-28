<?php
declare(strict_types=1);

/**
 * A lista como o usuário a deixou.
 *
 * Filtrar por "Aguardando convênio", abrir um pedido, gravar e cair numa
 * lista sem filtro nenhum é perder o lugar na página. Quem trabalha uma fila
 * inteira refaz o filtro a cada registro — dezenas de vezes por dia.
 *
 * Aqui a lista de cada módulo é lembrada como ela estava: filtros, busca,
 * ordenação, página e quantos por página. Voltar devolve a mesma tela.
 *
 * A memória vive na sessão, e não num cookie ou na URL: é preferência de
 * navegação de uma pessoa numa máquina, não dado a ser compartilhado por
 * link. Some com o logout, que é o comportamento certo num computador de
 * balcão usado por várias atendentes.
 */
final class Navegacao
{
    private const CHAVE = 'ultima_lista';

    /** Módulos lembrados ao mesmo tempo. */
    private const MODULOS = 20;

    /** Parâmetros guardados por módulo. */
    private const PARAMS = 20;

    /** Tamanho máximo do valor de um parâmetro. */
    private const VALOR = 200;

    /**
     * Guarda a lista atual. Chamado pelo front controller a cada GET de
     * `lista` — inclusive o GET sem filtro nenhum, que é como "limpar
     * filtros" apaga a memória sem precisar de código próprio.
     */
    public static function lembrar(string $modulo): void
    {
        $guardar = [];

        foreach ($_GET as $chave => $valor) {
            if (count($guardar) >= self::PARAMS) {
                break;
            }

            // mod e action são remontados por url(); guardá-los deixaria a
            // memória apontar para si mesma se um dia a rota mudar de nome.
            if ($chave === 'mod' || $chave === 'action') {
                continue;
            }

            // Só nome de campo de verdade, e só valor simples: o que volta
            // daqui vira URL, e array ou chave estranha viraria parâmetro
            // inventado na próxima requisição.
            if (!is_string($chave) || !preg_match('/^[a-zA-Z_][a-zA-Z0-9_]{0,39}$/', $chave)) {
                continue;
            }

            if (!is_scalar($valor)) {
                continue;
            }

            $valor = (string) $valor;

            if (mb_strlen($valor) > self::VALOR) {
                continue;
            }

            $guardar[$chave] = $valor;
        }

        $memoria = self::memoria();

        // Regrava a chave no fim do array: assim o módulo usado agora é o
        // último a ser descartado quando o limite estourar.
        unset($memoria[$modulo]);
        $memoria[$modulo] = $guardar;

        while (count($memoria) > self::MODULOS) {
            array_shift($memoria);
        }

        $_SESSION[self::CHAVE] = $memoria;
    }

    /** A URL da lista do módulo como ela foi deixada. */
    public static function lista(string $modulo, array $extras = []): string
    {
        return url($modulo, 'lista', array_merge(self::memoria()[$modulo] ?? [], $extras));
    }

    /** Há filtro, busca ou página guardados para este módulo? */
    public static function temMemoria(string $modulo): bool
    {
        return !empty(self::memoria()[$modulo]);
    }

    /**
     * Esquece a lista de um módulo — ou todas, sem argumento.
     *
     * Usado no login e no logout: a tela que a atendente anterior deixou
     * filtrada não é a tela com que a próxima deve começar o dia.
     */
    public static function esquecer(?string $modulo = null): void
    {
        if ($modulo === null) {
            unset($_SESSION[self::CHAVE]);
            return;
        }

        $memoria = self::memoria();
        unset($memoria[$modulo]);
        $_SESSION[self::CHAVE] = $memoria;
    }

    private static function memoria(): array
    {
        $memoria = $_SESSION[self::CHAVE] ?? [];
        return is_array($memoria) ? $memoria : [];
    }
}
