<?php
declare(strict_types=1);

/**
 * APASBS v2 — front controller.
 * Todas as requisições entram por aqui: /?mod=<modulo>&action=<acao>
 */

define('BASE_PATH', __DIR__);
// Detecta automaticamente se está na raiz ou em subpasta
define('BASE_URL', rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php')), '/'));

require_once BASE_PATH . '/config/database.php';

// Exibição de erros controlada por config/env.php → 'debug' => true|false
// Em produção mantenha debug = false: os erros vão só para o log do servidor.
ini_set('display_errors', APP_DEBUG ? '1' : '0');
ini_set('log_errors', '1');
error_reporting(APP_DEBUG ? E_ALL : E_ALL & ~E_DEPRECATED & ~E_NOTICE);

require_once BASE_PATH . '/core/helpers.php';
require_once BASE_PATH . '/core/Security.php';

Security::iniciarSessao();
Security::cabecalhos();

require_once BASE_PATH . '/core/Csrf.php';
require_once BASE_PATH . '/core/Flash.php';
require_once BASE_PATH . '/core/Auth.php';
require_once BASE_PATH . '/core/Audit.php';
require_once BASE_PATH . '/core/Icon.php';
require_once BASE_PATH . '/core/View.php';
require_once BASE_PATH . '/core/Modules.php';
require_once BASE_PATH . '/core/Paginacao.php';
require_once BASE_PATH . '/core/Navegacao.php';
require_once BASE_PATH . '/core/Planilha.php';
require_once BASE_PATH . '/core/Controller.php';
require_once BASE_PATH . '/core/Instalacao.php';

Modules::sync();

$mod    = (string) ($_GET['mod']    ?? 'usuarios');
$action = (string) ($_GET['action'] ?? 'login');

// Whitelist derivada do registry central
if (!in_array($mod, Modules::slugs(), true)) {
    http_response_code(404);
    exit('Módulo não encontrado.');
}

// Permite apenas letras minúsculas e underscore na action
$action = preg_replace('/[^a-z_]/', '', strtolower($action)) ?? '';

if ($action === '') {
    http_response_code(404);
    exit('Ação não encontrada.');
}

$controllerFile = BASE_PATH . "/modules/{$mod}/Controller.php";

if (!is_file($controllerFile)) {
    http_response_code(404);
    exit('Módulo não encontrado.');
}

require_once $controllerFile;

$className = ucfirst($mod) . 'Controller';

if (!class_exists($className)) {
    http_response_code(500);
    exit('Controller inválido.');
}

// Módulo novo no código e script do banco ainda não rodado: em vez de um
// "Fatal error" com o caminho dos arquivos na tela, uma página dizendo qual
// script falta rodar. Vale para a construção e para a action, porque a
// tabela que falta tanto pode ser consultada numa quanto na outra.
try {
    $controller = new $className();
} catch (PDOException $e) {
    $tabela = Instalacao::tabelaFaltando($e);

    if ($tabela === null) {
        throw $e;
    }

    error_log('Tabela ausente ao abrir o módulo ' . $mod . ': ' . $e->getMessage());
    Instalacao::pagina($mod, $tabela, Instalacao::scriptDaTabela($tabela));
}

// Só métodos públicos declarados no próprio controller podem virar rota.
// Isso impede chamar helpers herdados (redirect, json, auditar…) pela URL.
try {
    $metodo = new ReflectionMethod($controller, $action);
} catch (ReflectionException) {
    http_response_code(404);
    exit('Ação não encontrada.');
}

if (!$metodo->isPublic() || $metodo->isStatic() || $metodo->getDeclaringClass()->getName() !== $className) {
    http_response_code(404);
    exit('Ação não encontrada.');
}

// Guarda a lista como o usuário a está vendo, para que gravar um registro e
// voltar devolva a mesma tela — com o mesmo filtro e na mesma página. Só em
// GET: um POST não é a tela que ele está olhando, é o que ele acabou de fazer.
if ($action === 'lista' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
    Navegacao::lembrar($mod);
}

try {
    $controller->{$action}();
} catch (PDOException $e) {
    $tabela = Instalacao::tabelaFaltando($e);

    if ($tabela === null) {
        throw $e;
    }

    error_log('Tabela ausente em ' . $mod . '/' . $action . ': ' . $e->getMessage());
    Instalacao::pagina($mod, $tabela, Instalacao::scriptDaTabela($tabela));
}
