<?php
/**
 * Traz para dentro do banco os documentos das Autorizações que ainda estão na
 * pasta uploads/autorizacoes.
 *
 * Roda quantas vezes for preciso: cada linha só é preenchida uma vez, e o que
 * já está no banco é pulado. Nenhum arquivo é apagado — a pasta continua
 * intacta até você mesmo conferir o relatório e decidir removê-la.
 *
 * Uso, na pasta do sistema:
 *     php tools/importar-anexos-banco.php            (importa)
 *     php tools/importar-anexos-banco.php --simular  (só mostra o que faria)
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit("Este script só roda no terminal.\n");
}

$raiz    = dirname(__DIR__);
$simular = in_array('--simular', $argv, true);

require $raiz . '/config/database.php';

$pdo = getPDO();

// A tabela e a coluna são criadas pelo próprio módulo — a mesma rotina que
// roda quando alguém abre as Autorizações. Aqui ela é chamada de propósito,
// para o importador funcionar mesmo num servidor onde ninguém abriu a tela
// ainda. Se o usuário do banco não tiver permissão de criar, isto devolve
// falso e o aviso abaixo diz o que fazer.
require $raiz . '/modules/autorizacoes/Model.php';

if (!AutorizacaoModel::instalarAnexos($pdo)) {
    exit("Não consegui preparar a tabela dos documentos neste banco.\n"
       . "  O usuário do banco provavelmente não tem permissão para criar tabela.\n"
       . "  Peça a quem tem, ou rode:  mysql -u usuario -p banco < sql/amb_autorizacoes_anexos.sql\n");
}

/**
 * O maior arquivo que cabe num INSERT neste servidor.
 *
 * Um documento maior que isso não entra de jeito nenhum — melhor dizer qual é
 * e deixá-lo onde está do que tentar e derrubar a conexão no meio da leva.
 */
$pacote = (int) ($pdo->query("SHOW VARIABLES LIKE 'max_allowed_packet'")->fetch()['Value'] ?? 0);
$teto   = $pacote > 0 ? $pacote - 262144 : PHP_INT_MAX;

$linhas = $pdo->query(
    "SELECT x.id, x.autorizacao_id, x.tipo, x.nome_orig, x.nome_arq, x.tamanho,
            a.paciente, DATE_FORMAT(a.data_solicitacao, '%d/%m/%Y') AS data_br
       FROM amb_autorizacao_anexos x
       JOIN amb_autorizacoes a ON a.id = x.autorizacao_id
      WHERE x.conteudo IS NULL
      ORDER BY a.data_solicitacao, x.id"
)->fetchAll();

printf("\nIMPORTAR DOCUMENTOS DAS AUTORIZAÇÕES PARA O BANCO%s\n",
    $simular ? '  (simulação — nada será gravado)' : '');

if (!$linhas) {
    $jaTem = (int) $pdo->query('SELECT COUNT(*) FROM amb_autorizacao_anexos WHERE conteudo IS NOT NULL')->fetchColumn();
    exit("\nNão há nada a importar: os $jaTem anexo(s) desta base já estão no banco.\n");
}

$gravar = $pdo->prepare(
    'UPDATE amb_autorizacao_anexos SET conteudo = ? WHERE id = ? AND conteudo IS NULL'
);

$conferir = $pdo->prepare(
    'SELECT LENGTH(conteudo) AS bytes, SHA2(conteudo, 256) AS hash
       FROM amb_autorizacao_anexos WHERE id = ?'
);

$importados = 0;
$perdidos   = [];
$falhas     = [];
$bytes      = 0;

foreach ($linhas as $l) {
    $caminho = $raiz . '/uploads/autorizacoes/' . (int) $l['autorizacao_id'] . '/' . basename((string) $l['nome_arq']);
    $quem    = sprintf('#%-5s %s — %s (%s)', $l['id'], $l['paciente'], $l['tipo'], $l['data_br']);

    if (!is_file($caminho)) {
        $perdidos[] = $quem . ' · ' . $l['nome_orig'];
        continue;
    }

    $conteudo = file_get_contents($caminho);

    if (!is_string($conteudo) || $conteudo === '') {
        $falhas[] = $quem . ' · arquivo ilegível ou vazio em disco';
        continue;
    }

    if (strlen($conteudo) > $teto) {
        $falhas[] = sprintf('%s · %s não cabe no max_allowed_packet deste servidor (%s)',
            $quem, formatar(strlen($conteudo)), formatar($teto));
        continue;
    }

    if ($simular) {
        printf("  importaria  %s · %s\n", $quem, formatar(strlen($conteudo)));
        $importados++;
        $bytes += strlen($conteudo);
        continue;
    }

    try {
        $gravar->bindValue(1, $conteudo, PDO::PARAM_LOB);
        $gravar->bindValue(2, (int) $l['id'], PDO::PARAM_INT);
        $gravar->execute();
    } catch (PDOException $e) {
        $falhas[] = $quem . ' · o banco recusou: ' . $e->getMessage();
        continue;
    }

    // Conferência de verdade: o banco devolve o tamanho e o SHA-256 do que
    // ficou gravado, e os dois são comparados com o arquivo em disco. Sem
    // isto, um documento truncado no meio do caminho passaria por importado.
    $conferir->execute([(int) $l['id']]);
    $volta = $conferir->fetch();

    if ((int) ($volta['bytes'] ?? -1) !== strlen($conteudo)
        || strtolower((string) ($volta['hash'] ?? '')) !== hash('sha256', $conteudo)) {
        $falhas[] = $quem . ' · gravou diferente do arquivo em disco — NÃO apague este arquivo';
        continue;
    }

    printf("  importado   %s · %s\n", $quem, formatar(strlen($conteudo)));
    $importados++;
    $bytes += strlen($conteudo);
}

printf("\n──────\n%d importado(s), %s. %d sem arquivo em disco. %d falha(s).\n",
    $importados, formatar($bytes), count($perdidos), count($falhas));

if ($perdidos) {
    echo "\nSEM ARQUIVO EM DISCO — a ficha está no banco, o documento se perdeu\n";
    foreach ($perdidos as $p) {
        echo "  $p\n";
    }
    echo "  Estes precisam ser pedidos de novo ao convênio ou à paciente.\n";
}

if ($falhas) {
    echo "\nFALHAS — nada foi perdido, o arquivo continua em disco\n";
    foreach ($falhas as $f) {
        echo "  $f\n";
    }
}

if (!$simular && $importados > 0 && !$falhas) {
    echo "\nTudo o que existia em disco está agora no banco, conferido byte a byte.\n"
       . "A pasta uploads/autorizacoes pode ser removida quando você quiser —\n"
       . "guarde uma cópia dela até o primeiro backup do banco ficar pronto.\n";
}

/** Tamanho em MB ou KB, legível. */
function formatar(int $n): string
{
    return $n >= 1048576
        ? number_format($n / 1048576, 1, ',', '.') . ' MB'
        : number_format($n / 1024, 0, ',', '.') . ' KB';
}
