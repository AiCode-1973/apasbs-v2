<?php
declare(strict_types=1);

/**
 * Mensagens de uma requisição só, guardadas na sessão.
 * Substitui o ?msg=criado espalhado pelas views da v1 — que continua
 * funcionando, mas agora o texto vem de um lugar só.
 */
class Flash
{
    private const CHAVE = '_flash';

    public static function sucesso(string $texto): void
    {
        self::add('sucesso', $texto);
    }

    public static function erro(string $texto): void
    {
        self::add('erro', $texto);
    }

    public static function aviso(string $texto): void
    {
        self::add('aviso', $texto);
    }

    public static function info(string $texto): void
    {
        self::add('info', $texto);
    }

    private static function add(string $tipo, string $texto): void
    {
        $_SESSION[self::CHAVE][] = ['tipo' => $tipo, 'texto' => $texto];
    }

    /** Devolve e limpa todas as mensagens pendentes. */
    public static function consumir(): array
    {
        $msgs = $_SESSION[self::CHAVE] ?? [];
        unset($_SESSION[self::CHAVE]);
        return is_array($msgs) ? $msgs : [];
    }
}
