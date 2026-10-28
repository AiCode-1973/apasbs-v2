<?php
declare(strict_types=1);

/**
 * Proteção CSRF por token de sessão.
 *
 * Uso na view:      <?= Csrf::campo() ?>
 * Uso no controller: Csrf::exigir();
 */
class Csrf
{
    private const CHAVE = '_csrf_token';
    public  const CAMPO = '_token';

    public static function token(): string
    {
        if (empty($_SESSION[self::CHAVE]) || !is_string($_SESSION[self::CHAVE])) {
            $_SESSION[self::CHAVE] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::CHAVE];
    }

    /** Campo hidden pronto para colar dentro de qualquer <form method="post"> */
    public static function campo(): string
    {
        return '<input type="hidden" name="' . self::CAMPO . '" value="' . htmlspecialchars(self::token(), ENT_QUOTES) . '">';
    }

    public static function valido(?string $token): bool
    {
        if (!is_string($token) || $token === '' || empty($_SESSION[self::CHAVE])) {
            return false;
        }
        return hash_equals((string) $_SESSION[self::CHAVE], $token);
    }

    /**
     * Barra a requisição se o token não conferir.
     * Aceita o token pelo corpo do POST ou pelo cabeçalho X-CSRF-Token (AJAX).
     */
    public static function exigir(): void
    {
        $token = $_POST[self::CAMPO] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

        if (!self::valido(is_string($token) ? $token : null)) {
            http_response_code(419);
            exit('Sessão expirada ou requisição inválida. Recarregue a página e tente novamente.');
        }
    }

    /** Gera um token novo (chamado no login e no logout) */
    public static function rotacionar(): void
    {
        $_SESSION[self::CHAVE] = bin2hex(random_bytes(32));
    }
}
