<?php
/**
 * Confere, um a um, se os arquivos que o banco diz existir estão mesmo em
 * uploads/ — e lista quem ficou órfão.
 *
 * Serve para depois de uma restauração: o banco guarda a linha do documento,
 * o disco guarda o arquivo, e os dois podem sair de sincronia quando a pasta
 * uploads se perde. Sem esta lista, a falta é descoberta de um em um, meses
 * depois, quando alguém vai procurar a guia de uma paciente.
 *
 * Uso, na pasta do sistema:
 *     php tools/conferir-anexos.php
 *
 * Só lê: não apaga linha nem arquivo. O que fazer com os órfãos é decisão de
 * quem leu o relatório.
 */
declare(strict_types=1);

$raiz = dirname(__DIR__);

require $raiz . '/config/database.php';

$pdo = getPDO();

/**
 * Cada origem de anexo: a tabela, como montar o caminho em disco e o que
 * mostrar para identificar o registro na tela do sistema.
 */
$origens = [
    'Manuais (Ajuda)' => [
        'sql'     => 'SELECT m.id, m.nome_arq, m.nome_orig, m.titulo AS referencia
                        FROM ajuda_manuais m ORDER BY m.id',
        'caminho' => static fn (array $l): string => '/uploads/ajuda/' . $l['nome_arq'],
    ],
    'Autorizações de exame' => [
        // Aqui o documento mora no banco desde outubro de 2026, então a
        // pergunta mudou: não é mais "o arquivo está na pasta?", é "o
        // conteúdo está na linha?". Quem já veio para o banco está salvo,
        // tenha ou não sobrado arquivo na pasta antiga.
        'coluna_banco' => 'conteudo',
        'sql'     => "SELECT x.id, x.nome_arq, x.nome_orig, x.autorizacao_id,
                             (x.conteudo IS NOT NULL) AS no_banco,
                             CONCAT(a.paciente, ' — ', x.tipo,
                                    ' (solicitada em ', DATE_FORMAT(a.data_solicitacao, '%d/%m/%Y'), ')') AS referencia
                        FROM amb_autorizacao_anexos x
                        JOIN amb_autorizacoes a ON a.id = x.autorizacao_id
                       ORDER BY a.data_solicitacao DESC, x.id",
        'caminho' => static fn (array $l): string => '/uploads/autorizacoes/' . (int) $l['autorizacao_id'] . '/' . $l['nome_arq'],
    ],
    'Cirurgias' => [
        'sql'     => "SELECT x.id, x.nome_arq, x.nome_orig, x.cirurgia_id,
                             CONCAT(c.protocolo, ' — ', c.tuss_termo) AS referencia
                        FROM cirurgias_anexos x
                        JOIN cirurgias c ON c.id = x.cirurgia_id
                       ORDER BY x.id",
        'caminho' => static fn (array $l): string => '/uploads/cirurgias/' . (int) $l['cirurgia_id'] . '/' . $l['nome_arq'],
    ],
];

$totalLinhas = 0;
$totalOrfaos = 0;

/** A tabela já tem a coluna onde o documento passou a morar? */
$temColuna = static function (PDO $pdo, string $tabela, string $coluna): bool {
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
          WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $stmt->execute([$tabela, $coluna]);

    return (bool) $stmt->fetchColumn();
};

foreach ($origens as $nome => $origem) {
    $sql = $origem['sql'];

    // Base que ainda não rodou a migração: a consulta cai de volta para a
    // versão sem a coluna, em vez de o relatório inteiro dizer "tabela não
    // encontrada" por causa de um campo que falta.
    if (isset($origem['coluna_banco'])
        && !$temColuna($pdo, 'amb_autorizacao_anexos', $origem['coluna_banco'])) {
        $sql = str_replace('(x.conteudo IS NOT NULL) AS no_banco,', '0 AS no_banco,', $sql);
    }

    try {
        $linhas = $pdo->query($sql)->fetchAll();
    } catch (PDOException $e) {
        printf("\n%s\n  tabela não encontrada neste banco — pulando.\n", $nome);
        continue;
    }

    $orfaos   = [];
    $noBanco  = 0;

    foreach ($linhas as $l) {
        if (!empty($l['no_banco'])) {
            $noBanco++;
            continue;
        }

        if (!is_file($raiz . $origem['caminho']($l))) {
            $orfaos[] = $l;
        }
    }

    $totalLinhas += count($linhas);
    $totalOrfaos += count($orfaos);

    printf("\n%s\n  %d registro(s) no banco, %d sem o arquivo%s.\n",
        $nome,
        count($linhas),
        count($orfaos),
        $noBanco > 0 ? " ($noBanco guardado(s) dentro do próprio banco)" : ' em disco');

    foreach ($orfaos as $l) {
        printf("    #%-6s %-44s %s\n",
            $l['id'],
            mb_strimwidth((string) $l['referencia'], 0, 44, '…'),
            $l['nome_orig']);
    }
}

printf("\n──────\n%d anexo(s) no banco · %d sem arquivo.\n", $totalLinhas, $totalOrfaos);

if ($totalOrfaos > 0) {
    echo "\nO sistema continua funcionando: a lista mostra o anexo e, ao abrir,\n"
       . "responde que o arquivo não está no servidor. Os documentos acima\n"
       . "precisam ser pedidos de novo ao convênio ou à paciente e anexados\n"
       . "outra vez — anexar de novo não cria linha duplicada, porque a antiga\n"
       . "pode ser removida pela lixeira na própria tela.\n";
}
