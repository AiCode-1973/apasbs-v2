<?php
declare(strict_types=1);

/**
 * Inicialização segura da sessão e cabeçalhos de proteção.
 * Substitui o session_start() cru do sistema original.
 */
class Security
{
    public static function iniciarSessao(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $https = APP_HTTPS === 'auto' ? self::ehHttps() : (bool) APP_HTTPS;

        session_name('APASBSSESS');
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $https,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        // Impede que um id de sessão vindo da URL seja aceito
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');

        session_start();

        // Expira sessões ociosas há mais de 2 horas
        $limite = 2 * 60 * 60;
        if (isset($_SESSION['ultimo_acesso']) && (time() - (int) $_SESSION['ultimo_acesso']) > $limite) {
            session_unset();
            session_destroy();
            session_start();
        }
        $_SESSION['ultimo_acesso'] = time();
    }

    public static function cabecalhos(): void
    {
        if (headers_sent()) {
            return;
        }

        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: same-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
        header_remove('X-Powered-By');

        // Todo o CSS/JS é servido localmente — nada vem de CDN.
        // 'unsafe-eval' é exigido pelo Alpine.js, que avalia as expressões
        // dos atributos x-*. Scripts inline continuam bloqueados, então um
        // XSS refletido não consegue executar nada.
        header(
            "Content-Security-Policy: default-src 'self'; "
            . "img-src 'self' data:; "
            . "style-src 'self' 'unsafe-inline'; "
            . "script-src 'self' 'unsafe-eval'; "
            . "font-src 'self'; "
            . "connect-src 'self'; "
            . "form-action 'self'; "
            . "frame-ancestors 'self'; "
            . "base-uri 'self'; "
            . "object-src 'none'"
        );
    }

    public static function ehHttps(): bool
    {
        if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
            return true;
        }
        if (($_SERVER['SERVER_PORT'] ?? '') === '443') {
            return true;
        }
        return strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
    }
}
