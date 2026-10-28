<?php
declare(strict_types=1);

/**
 * Carrega as credenciais de config/env.php (não versionado)
 * e expõe a conexão PDO do sistema (singleton por requisição).
 */

$envFile = __DIR__ . '/env.php';

if (!is_file($envFile)) {
    http_response_code(500);
    exit('Configuração ausente: copie config/env.example.php para config/env.php e preencha os dados de acesso.');
}

/** @var array<string,mixed> $env */
$env = require $envFile;

define('DB_HOST',    (string) $env['db_host']);
define('DB_USER',    (string) $env['db_user']);
define('DB_PASS',    (string) $env['db_pass']);
define('DB_NAME',    (string) $env['db_name']);
define('DB_CHARSET', (string) ($env['db_charset'] ?? 'utf8mb4'));
define('APP_DEBUG',  (bool)   ($env['debug'] ?? false));
define('APP_HTTPS',  $env['https'] ?? 'auto');
define('LOGIN_MAX_TENTATIVAS', (int) ($env['login_max_tentativas'] ?? 5));
define('LOGIN_BLOQUEIO_SEG',   (int) ($env['login_bloqueio_seg'] ?? 900));

// ── Banco da Auditoria Hospitalar ───────────────────────────────────────
// O módulo Auditoria Hospitalar lê e grava no banco do AuditorHosp, que
// continua existindo com as tabelas dele. Em branco, o módulo avisa que não
// foi configurado em vez de quebrar — o resto do sistema não depende disto.
define('AUD_DB_HOST', (string) ($env['aud_db_host'] ?? ''));
define('AUD_DB_NAME', (string) ($env['aud_db_name'] ?? ''));
define('AUD_DB_USER', (string) ($env['aud_db_user'] ?? ''));
define('AUD_DB_PASS', (string) ($env['aud_db_pass'] ?? ''));

unset($env, $envFile);

/**
 * Conexão PDO reaproveitada em toda a requisição.
 * Antes cada Model abria a sua — agora é uma só.
 */
function getPDO(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        error_log('Erro de conexão com o banco: ' . $e->getMessage());
        http_response_code(503);
        exit(APP_DEBUG
            ? 'Falha na conexão com o banco de dados: ' . $e->getMessage()
            : 'Falha na conexão com o banco de dados.');
    }

    return $pdo;
}

/**
 * Conexão com o banco do AuditorHosp.
 *
 * É um banco à parte, com as tabelas dele, que continua servindo o sistema
 * antigo enquanto o módulo novo trabalha em cima das mesmas tabelas. Por isso
 * é uma segunda conexão, e não uma cópia: dois bancos guardando a mesma conta
 * divergiriam na primeira correção.
 *
 * A conexão é aberta só quando alguém abre o módulo — quem nunca entra nele
 * não paga por ela. E, se o banco não estiver configurado ou liberado, devolve
 * null: o módulo explica o que falta e o resto do sistema segue funcionando.
 */
function getPDOAuditoria(): ?PDO
{
    static $pdo  = null;
    static $erro = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    // Já tentou e falhou nesta requisição: não insiste a cada consulta.
    if ($erro !== null) {
        return null;
    }

    if (AUD_DB_HOST === '' || AUD_DB_NAME === '' || AUD_DB_USER === '') {
        $erro = 'Banco da Auditoria Hospitalar não configurado em config/env.php.';
        return null;
    }

    $dsn = 'mysql:host=' . AUD_DB_HOST . ';dbname=' . AUD_DB_NAME . ';charset=utf8mb4';

    try {
        $pdo = new PDO($dsn, AUD_DB_USER, AUD_DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        error_log('Erro de conexão com o banco da Auditoria Hospitalar: ' . $e->getMessage());
        $erro = $e->getMessage();
        return null;
    }

    return $pdo;
}

/**
 * O que impediu a conexão com o banco da Auditoria Hospitalar, se foi o caso:
 * 'nao_configurado' (falta preencher o env) ou 'sem_acesso' (preenchido, mas o
 * banco recusou). São duas conversas diferentes com quem está na tela.
 */
function erroAuditoria(): ?string
{
    if (AUD_DB_HOST === '' || AUD_DB_NAME === '' || AUD_DB_USER === '') {
        return 'nao_configurado';
    }

    return getPDOAuditoria() === null ? 'sem_acesso' : null;
}
