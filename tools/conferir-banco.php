<?php
/**
 * Diz por que o banco recusou a conexão.
 *
 * A tela do sistema não mostra o motivo de propósito: ela é pública para quem
 * já entrou, e o motivo do MySQL entrega nome de usuário, nome de banco e, às
 * vezes, caminho de arquivo. Aqui, no terminal do servidor, quem lê é quem
 * administra — então o motivo aparece inteiro, e traduzido.
 *
 * Uso, na pasta do sistema:
 *     php tools/conferir-banco.php
 *
 * Só lê: não grava, não altera permissão, não cria banco. A senha nunca é
 * impressa — nem a certa, nem a errada.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit("Este script só roda no terminal.\n");
}

$raiz    = dirname(__DIR__);
$envFile = $raiz . '/config/env.php';

if (!is_file($envFile)) {
    exit("Não achei config/env.php.\n"
       . "  Copie config/env.example.php para config/env.php e preencha os acessos.\n");
}

/** @var array<string,mixed> $env */
$env = require $envFile;

/**
 * Traduz o erro do MySQL para o que precisa ser feito.
 *
 * Os códigos são os do próprio servidor: 1045 senha ou usuário, 1044/1142 falta
 * de permissão no banco, 1049 banco inexistente, 2002/2005 host inalcançável.
 */
function explicar(PDOException $e, string $banco, string $usuario): string
{
    $msg = $e->getMessage();

    return match (true) {
        str_contains($msg, '1045') => "Usuário ou senha recusados.\n"
            . "      O MySQL não reclamou do banco, reclamou de quem bateu na porta:\n"
            . "      confira a senha de '$usuario' no painel do provedor (MySQL ›\n"
            . "      Usuários). Se não souber, redefina por lá e escreva a nova no\n"
            . "      config/env.php. Senha com acento ou aspas precisa estar entre\n"
            . "      aspas simples, e barra invertida dobrada.",

        str_contains($msg, '1044'),
        str_contains($msg, '1142') => "O usuário '$usuario' não entra no banco '$banco'.\n"
            . "      Olhe primeiro a lista de bancos no fim deste relatório: se '$banco'\n"
            . "      não estiver escrito lá do mesmo jeito, é nome errado e não falta de\n"
            . "      permissão — o MySQL responde a mesma coisa nos dois casos, para não\n"
            . "      contar a quem pergunta quais bancos existem.\n"
            . "      Sendo o nome certo, falta permissão: no painel do provedor, MySQL ›\n"
            . "      Adicionar usuário ao banco de dados, escolha '$usuario' e '$banco' e\n"
            . "      marque todos os privilégios. Pelo terminal, o comando está em\n"
            . "      sql/aud_acesso.sql.",

        str_contains($msg, '1049') => "O banco '$banco' não existe neste servidor.\n"
            . "      Confira o nome no painel — ele costuma vir com o prefixo da conta,\n"
            . "      como dema5738_apasbs. A lista abaixo mostra o que o usuário enxerga.",

        str_contains($msg, '2002'),
        str_contains($msg, '2005') => "Não consegui chegar ao servidor de banco neste endereço.\n"
            . "      Com os dois bancos na mesma hospedagem, o host é 'localhost'.\n"
            . "      Endereço externo só funciona se o provedor liberar o seu IP em\n"
            . "      MySQL › Acesso remoto.",

        default => "O MySQL respondeu assim:\n      " . $msg,
    };
}

/**
 * Tenta uma conexão e conta como foi.
 *
 * @return array{0: ?PDO, 1: string}
 */
function tentar(string $host, string $nome, string $user, string $pass, string $charset = 'utf8mb4'): array
{
    if ($host === '' || $nome === '' || $user === '') {
        return [null, "em branco no config/env.php — nada a testar."];
    }

    try {
        $pdo = new PDO(
            "mysql:host=$host;dbname=$nome;charset=$charset",
            $user,
            $pass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
    } catch (PDOException $e) {
        return [null, explicar($e, $nome, $user)];
    }

    return [$pdo, 'ok'];
}

$conexoes = [
    'Banco do APASBS' => [
        'host' => (string) ($env['db_host'] ?? ''),
        'nome' => (string) ($env['db_name'] ?? ''),
        'user' => (string) ($env['db_user'] ?? ''),
        'pass' => (string) ($env['db_pass'] ?? ''),
    ],
    'Banco do AuditorHosp (Auditoria Hospitalar)' => [
        'host' => (string) ($env['aud_db_host'] ?? ''),
        'nome' => (string) ($env['aud_db_name'] ?? ''),
        'user' => (string) ($env['aud_db_user'] ?? ''),
        'pass' => (string) ($env['aud_db_pass'] ?? ''),
    ],
];

echo "\nCONFERÊNCIA DE ACESSO AOS BANCOS\n";

$abertas = [];

foreach ($conexoes as $titulo => $c) {
    [$pdo, $como] = tentar($c['host'], $c['nome'], $c['user'], $c['pass']);

    printf("\n%s\n  host %s · banco %s · usuário %s · senha %s\n",
        $titulo,
        $c['host'] !== '' ? $c['host'] : '(vazio)',
        $c['nome'] !== '' ? $c['nome'] : '(vazio)',
        $c['user'] !== '' ? $c['user'] : '(vazio)',
        $c['pass'] !== '' ? 'preenchida (' . strlen($c['pass']) . ' caracteres)' : 'EM BRANCO');

    if ($pdo instanceof PDO) {
        $abertas[$titulo] = $pdo;
        $v = $pdo->query('SELECT VERSION()')->fetchColumn();
        printf("  CONECTOU. Servidor %s.\n", $v);
        continue;
    }

    printf("  NÃO CONECTOU: %s\n", $como);
}

/**
 * O caso mais comum de todos, e o mais fácil de não enxergar: as duas linhas
 * são do MESMO usuário do MySQL, e só uma das senhas está certa. Quando o
 * APASBS entra e o AuditorHosp é recusado com o mesmo usuário, não há o que
 * investigar — é uma senha diferente da outra, e a certa já está no arquivo.
 */
$apasbs = $conexoes['Banco do APASBS'];
$audCfg = $conexoes['Banco do AuditorHosp (Auditoria Hospitalar)'];

if (isset($abertas['Banco do APASBS'])
    && !isset($abertas['Banco do AuditorHosp (Auditoria Hospitalar)'])
    && $audCfg['user'] === $apasbs['user']
    && $audCfg['host'] === $apasbs['host']
    && $audCfg['pass'] !== $apasbs['pass']) {
    echo "\n  ATENÇÃO: as duas linhas usam o mesmo usuário ('{$apasbs['user']}') no\n"
       . "  mesmo host, mas com senhas diferentes — e a do APASBS funcionou. Copie\n"
       . "  o valor de 'db_pass' para 'aud_db_pass' no config/env.php e rode de novo.\n";
}

/**
 * Se alguma conexão abriu, mostra o que aquele usuário enxerga. Metade dos
 * casos acaba aqui: o nome do banco no env está com uma letra diferente do
 * que o painel criou.
 */
$primeira = reset($abertas);

if ($primeira instanceof PDO) {
    echo "\nBANCOS QUE ESTE USUÁRIO ENXERGA\n";

    foreach ($primeira->query('SHOW DATABASES')->fetchAll(PDO::FETCH_COLUMN) as $b) {
        if (in_array($b, ['information_schema', 'performance_schema', 'mysql', 'sys'], true)) {
            continue;
        }
        echo "  $b\n";
    }
}

/**
 * Conectar no banco do AuditorHosp não basta: o módulo precisa das tabelas do
 * sistema antigo. Um banco vazio com o nome certo conecta e depois quebra na
 * primeira consulta — melhor descobrir agora.
 */
$aud = $abertas['Banco do AuditorHosp (Auditoria Hospitalar)'] ?? null;

if ($aud instanceof PDO) {
    echo "\nTABELAS QUE O MÓDULO AUDITORIA HOSPITALAR PRECISA\n";

    $faltando = 0;

    foreach (['internacoes', 'pa_ambulatorio', 'convenios', 'motivos_glosa'] as $t) {
        $existe = (bool) $aud->query("SHOW TABLES LIKE " . $aud->quote($t))->fetchColumn();
        printf("  %-16s %s\n", $t, $existe ? 'ok' : 'NÃO ENCONTRADA');
        $faltando += $existe ? 0 : 1;
    }

    if ($faltando > 0) {
        echo "\n  A conexão abriu, mas este não parece ser o banco do AuditorHosp.\n"
           . "  Confira o nome em aud_db_name: pode estar apontando para o banco errado.\n";
    }
}

echo "\n──────\n";

if (count($abertas) === count($conexoes)) {
    echo "As duas conexões abriram. Se a tela ainda reclamar, recarregue:\n"
       . "o aviso de instalação fica guardado só enquanto dura a página.\n";
} else {
    echo "Corrija o que está acima e rode de novo. Enquanto o banco do\n"
       . "AuditorHosp não abrir, o resto do sistema continua funcionando —\n"
       . "só o módulo Auditoria Hospitalar fica mostrando o aviso.\n";
}
