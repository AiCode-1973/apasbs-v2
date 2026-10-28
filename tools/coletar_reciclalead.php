<?php
/**
 * Coletor do ReciclaLead — busca as estatísticas da central e deixa na
 * área de espera do APASBS, para alguém conferir e confirmar.
 *
 * SÓ roda pela linha de comando:
 *
 *     php tools/coletar_reciclalead.php                  # mês corrente + os anteriores configurados
 *     php tools/coletar_reciclalead.php --mes=2026-06    # uma competência específica
 *     php tools/coletar_reciclalead.php --mes=2026-06 --mes=2026-07
 *     php tools/coletar_reciclalead.php --simular=tools/exemplo_reciclalead.json
 *
 * O que ele NÃO faz: gravar no fechamento. Ele só traz e guarda. Quem
 * transforma em fechamento é uma pessoa, na tela de conferência
 * (Central de Agendamento › Coletas). Coletor de sistema de terceiro erra
 * — o fornecedor renomeia um motivo e o número entra torto —, e número
 * errado no fechamento é pior que número nenhum.
 *
 * Sai com código 0 quando deu certo e 1 quando falhou, para a tarefa
 * agendada do Windows saber a diferença.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Este script só pode ser executado pela linha de comando.');
}

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/database.php';

// ── Argumentos ──────────────────────────────────────────────────────────

$meses   = [];
$simular = '';
$verboso = false;

foreach (array_slice($argv, 1) as $arg) {
    if (preg_match('/^--mes=(\d{4})-(\d{2})$/', $arg, $m)) {
        $meses[] = [(int) $m[1], (int) $m[2]];
    } elseif (preg_match('/^--simular=(.+)$/', $arg, $m)) {
        $simular = $m[1];
    } elseif ($arg === '--verboso' || $arg === '-v') {
        $verboso = true;
    } else {
        fwrite(STDERR, "Argumento não reconhecido: {$arg}\n");
        fwrite(STDERR, "Uso: php tools/coletar_reciclalead.php [--mes=AAAA-MM] [--simular=arquivo.json] [--verboso]\n");
        exit(1);
    }
}

// ── Configuração ────────────────────────────────────────────────────────

$arquivoConfig = BASE_PATH . '/config/reciclalead.php';

if (!is_file($arquivoConfig)) {
    erro('Falta o arquivo config/reciclalead.php. Copie o config/reciclalead.example.php e preencha.');
}

$cfg = require $arquivoConfig;
$cfg += ['url' => '', 'email' => '', 'senha' => '', 'horario_comercial' => false,
         'meses_atras' => 1, 'timeout' => 30];
$cfg['url'] = rtrim((string) $cfg['url'], '/');

if ($simular === '' && ($cfg['url'] === '' || $cfg['email'] === '' || $cfg['senha'] === '')) {
    erro('config/reciclalead.php está incompleto: url, email e senha são obrigatórios.');
}

// Sem competência na linha de comando, coleta o mês corrente e os anteriores
// configurados. Na virada do mês, a competência que fechou ainda precisa de
// uma última leitura — é para isso que serve meses_atras.
if (!$meses) {
    $hoje = new DateTimeImmutable('first day of this month');
    for ($i = (int) $cfg['meses_atras']; $i >= 0; $i--) {
        $d = $hoje->modify("-{$i} month");
        $meses[] = [(int) $d->format('Y'), (int) $d->format('n')];
    }
}

// ── Execução ────────────────────────────────────────────────────────────

$pdo = getPDO();
$falhas = 0;

try {
    $token = $simular !== '' ? 'simulado' : autenticar($cfg);
} catch (Throwable $e) {
    registrarErro($pdo, $meses[0][0], $meses[0][1], 'Login no ReciclaLead falhou: ' . $e->getMessage());
    erro('Login falhou: ' . $e->getMessage());
}

foreach ($meses as [$ano, $mes]) {
    $competencia = sprintf('%02d/%04d', $mes, $ano);

    try {
        $dados = $simular !== ''
            ? lerSimulacao($simular)
            : buscarCompetencia($cfg, $token, $ano, $mes);

        $coleta = montarColeta($dados);
        $id     = gravarColeta($pdo, $ano, $mes, $coleta);

        $aviso = $coleta['soma_motivos'] !== $coleta['total_atendimentos']
            ? sprintf(' — ATENÇÃO: a soma dos motivos (%d) não bate com o total (%d)',
                      $coleta['soma_motivos'], $coleta['total_atendimentos'])
            : '';

        linha(sprintf('%s: %d atendimentos, %d motivos, %d filas, %d atendentes%s',
            $competencia, $coleta['total_atendimentos'],
            count($coleta['motivos']), count($coleta['filas']),
            count($coleta['atendentes']), $aviso));

        if ($verboso) {
            foreach ($coleta['motivos'] as $m) {
                linha(sprintf('    %-34s %6d', $m['rotulo'], $m['quantidade']));
            }
        }

        if ($id === 0) {
            linha('    (já havia uma coleta confirmada com estes mesmos números — nada a fazer)');
        }
    } catch (Throwable $e) {
        $falhas++;
        registrarErro($pdo, $ano, $mes, $e->getMessage());
        fwrite(STDERR, sprintf("%s: FALHOU — %s\n", $competencia, $e->getMessage()));
    }
}

exit($falhas > 0 ? 1 : 0);

// ── Funções ─────────────────────────────────────────────────────────────

/** Faz o login e devolve o token. A senha não é registrada em lugar nenhum. */
function autenticar(array $cfg): string
{
    $resposta = requisitar(
        'POST',
        $cfg['url'] . '/auth/login',
        ['email' => $cfg['email'], 'password' => $cfg['senha']],
        null,
        (int) $cfg['timeout']
    );

    // O nome do campo varia conforme a versão da API; aceita os usuais.
    foreach (['token', 'access_token', 'accessToken', 'jwt'] as $campo) {
        if (!empty($resposta[$campo]) && is_string($resposta[$campo])) {
            return $resposta[$campo];
        }
    }

    if (!empty($resposta['data']['token'])) {
        return (string) $resposta['data']['token'];
    }

    throw new RuntimeException('a resposta do login não trouxe token: '
        . substr(json_encode($resposta, JSON_UNESCAPED_UNICODE) ?: '', 0, 200));
}

/** Busca as quatro estatísticas de uma competência. */
function buscarCompetencia(array $cfg, string $token, int $ano, int $mes): array
{
    $inicio = sprintf('%04d-%02d-01 00:00:00', $ano, $mes);
    $ultimo = (int) date('t', mktime(0, 0, 0, $mes, 1, $ano));
    $fim    = sprintf('%04d-%02d-%02d 23:59:00', $ano, $mes, $ultimo);

    $params = http_build_query([
        'useBusinessHours' => $cfg['horario_comercial'] ? 'true' : 'false',
        'startDate'        => $inicio,
        'endDate'          => $fim,
    ]);

    $buscar = function (string $caminho) use ($cfg, $token, $params) {
        return requisitar('GET', $cfg['url'] . $caminho . '?' . $params, null, $token, (int) $cfg['timeout']);
    };

    return [
        'tempos'     => $buscar('/statistics-tickets-times'),
        'motivos'    => $buscar('/statistics-tickets-closure-reason'),
        'filas'      => $buscar('/statistics-tickets-queue'),
        'atendentes' => $buscar('/statistics-active-tickets-user/closed'),
    ];
}

function lerSimulacao(string $arquivo): array
{
    $caminho = is_file($arquivo) ? $arquivo : BASE_PATH . '/' . ltrim($arquivo, '/');
    if (!is_file($caminho)) {
        throw new RuntimeException("arquivo de simulação não encontrado: {$arquivo}");
    }
    $dados = json_decode((string) file_get_contents($caminho), true);
    if (!is_array($dados)) {
        throw new RuntimeException('arquivo de simulação não é um JSON válido');
    }
    return $dados;
}

/** Normaliza o que veio da API para o formato que o banco guarda. */
function montarColeta(array $dados): array
{
    $tempos = $dados['tempos'][0] ?? $dados['tempos'] ?? [];
    if (!is_array($tempos)) {
        $tempos = [];
    }

    $motivos    = itens($dados['motivos'] ?? [],    'label', 'qtd',   'percentage');
    $filas      = itens($dados['filas'] ?? [],      'label', 'qtd',   'pertentual');
    $atendentes = itens($dados['atendentes'] ?? [], 'name',  'count', null);

    $soma = 0;
    foreach ($motivos as $m) {
        $soma += $m['quantidade'];
    }

    $total = (int) ($tempos['qtd_total_atendimentos'] ?? 0);
    if ($total === 0 && $soma > 0) {
        // Sem o total da API, a soma dos motivos é a melhor aproximação —
        // e a tela de conferência mostra que veio por esse caminho.
        $total = $soma;
    }

    return [
        'total_atendimentos' => $total,
        'soma_motivos'       => $soma,
        'pendentes'          => inteiroOuNulo($tempos['qtd_tickets_pendentes'] ?? null),
        'demanda_ativa'      => inteiroOuNulo($tempos['qtd_demanda_ativa'] ?? null),
        'demanda_receptiva'  => inteiroOuNulo($tempos['qtd_demanda_receptiva'] ?? null),
        'contatos_total'     => inteiroOuNulo($tempos['total_contacts'] ?? null),
        'contatos_novos'     => inteiroOuNulo($tempos['new_contacts'] ?? null),
        'tma_minutos'        => minutos($tempos['tma'] ?? null),
        'tme_minutos'        => minutos($tempos['tme'] ?? null),
        'motivos'            => $motivos,
        'filas'              => $filas,
        'atendentes'         => $atendentes,
        'bruto'              => json_encode($dados, JSON_UNESCAPED_UNICODE),
    ];
}

/** Cada lista da API vem como [{rótulo, quantidade, percentual}]. */
function itens(mixed $lista, string $campoRotulo, string $campoQtd, ?string $campoPct): array
{
    if (!is_array($lista)) {
        return [];
    }

    $saida = [];
    foreach ($lista as $linha) {
        if (!is_array($linha)) {
            continue;
        }
        $rotulo = trim((string) ($linha[$campoRotulo] ?? ''));
        if ($rotulo === '') {
            $rotulo = 'Não informado';
        }
        $saida[] = [
            'rotulo'     => mb_substr($rotulo, 0, 150),
            'quantidade' => (int) ($linha[$campoQtd] ?? 0),
            'percentual' => $campoPct !== null && isset($linha[$campoPct])
                ? (float) $linha[$campoPct] : null,
        ];
    }
    return $saida;
}

/** {"hours":6,"minutes":58} → 418 */
function minutos(mixed $valor): ?int
{
    if (!is_array($valor)) {
        return null;
    }
    return ((int) ($valor['hours'] ?? 0)) * 60 + (int) ($valor['minutes'] ?? 0);
}

function inteiroOuNulo(mixed $valor): ?int
{
    return ($valor === null || $valor === '') ? null : (int) $valor;
}

/**
 * Grava a coleta. Devolve o id, ou 0 quando não havia nada a fazer.
 *
 * Regras, para a execução semanal não empilhar quatro coletas do mesmo mês:
 *   - já existe uma pendente da competência → é substituída
 *   - já existe uma confirmada com os mesmos números → não faz nada
 *   - já existe uma confirmada com números diferentes → entra uma pendente
 *     nova, avisando que o número mudou depois de confirmado
 */
function gravarColeta(PDO $pdo, int $ano, int $mes, array $c): int
{
    $stmt = $pdo->prepare(
        "SELECT id, status, total_atendimentos FROM central_coletas
          WHERE ano = ? AND mes = ? AND status IN ('pendente','confirmada')
          ORDER BY FIELD(status,'pendente','confirmada'), id DESC LIMIT 1"
    );
    $stmt->execute([$ano, $mes]);
    $anterior = $stmt->fetch();

    $mensagem = null;

    if ($anterior && $anterior['status'] === 'confirmada') {
        if ((int) $anterior['total_atendimentos'] === $c['total_atendimentos']) {
            return 0;
        }
        $mensagem = 'Esta competência já havia sido confirmada com '
            . (int) $anterior['total_atendimentos'] . ' atendimentos e agora veio com '
            . $c['total_atendimentos'] . '. Confira antes de regravar.';
    }

    if ($c['soma_motivos'] !== $c['total_atendimentos']) {
        $mensagem = trim(($mensagem ? $mensagem . ' ' : '')
            . 'A soma dos motivos (' . $c['soma_motivos'] . ') não bate com o total ('
            . $c['total_atendimentos'] . ').');
    }

    $pdo->beginTransaction();
    try {
        if ($anterior && $anterior['status'] === 'pendente') {
            $pdo->prepare('DELETE FROM central_coletas WHERE id = ?')->execute([$anterior['id']]);
        }

        $sql = $pdo->prepare(
            'INSERT INTO central_coletas
                (ano, mes, origem, status, total_atendimentos, soma_motivos, pendentes,
                 demanda_ativa, demanda_receptiva, contatos_total, contatos_novos,
                 tma_minutos, tme_minutos, mensagem, resposta_bruta)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $sql->execute([
            $ano, $mes, 'reciclalead', 'pendente',
            $c['total_atendimentos'], $c['soma_motivos'], $c['pendentes'],
            $c['demanda_ativa'], $c['demanda_receptiva'], $c['contatos_total'],
            $c['contatos_novos'], $c['tma_minutos'], $c['tme_minutos'],
            $mensagem, $c['bruto'],
        ]);
        $id = (int) $pdo->lastInsertId();

        $item = $pdo->prepare(
            'INSERT INTO central_coleta_itens (coleta_id, tipo, rotulo, quantidade, percentual, motivo_id)
             VALUES (?, ?, ?, ?, ?, ?)'
        );

        foreach (['motivo' => $c['motivos'], 'fila' => $c['filas'], 'atendente' => $c['atendentes']] as $tipo => $lista) {
            foreach ($lista as $l) {
                $item->execute([
                    $id, $tipo, $l['rotulo'], $l['quantidade'], $l['percentual'],
                    $tipo === 'motivo' ? casarMotivo($pdo, $l['rotulo']) : null,
                ]);
            }
        }

        $pdo->commit();
        return $id;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Tenta casar o rótulo que veio de fora com um motivo já cadastrado.
 * Primeiro pelo de/para gravado por quem conferiu antes, depois pelo nome
 * ignorando acento, caixa e espaço sobrando ("Dúvidas " vem com espaço no
 * fim; "Agendamento Concluido" vem sem acento).
 */
function casarMotivo(PDO $pdo, string $rotulo): ?int
{
    static $mapa = null, $motivos = null;

    if ($mapa === null) {
        $mapa = [];
        foreach ($pdo->query("SELECT rotulo, motivo_id FROM central_coleta_mapa WHERE origem = 'reciclalead'") as $l) {
            $mapa[chaveTexto($l['rotulo'])] = (int) $l['motivo_id'];
        }
        $motivos = [];
        foreach ($pdo->query('SELECT id, nome FROM central_motivos') as $l) {
            $motivos[chaveTexto($l['nome'])] = (int) $l['id'];
        }
    }

    $chave = chaveTexto($rotulo);
    return $mapa[$chave] ?? $motivos[$chave] ?? null;
}

function chaveTexto(string $texto): string
{
    $t = mb_strtolower(trim($texto));
    $t = strtr($t, [
        'á'=>'a','à'=>'a','ã'=>'a','â'=>'a','ä'=>'a','é'=>'e','ê'=>'e','è'=>'e',
        'í'=>'i','ì'=>'i','î'=>'i','ó'=>'o','ô'=>'o','õ'=>'o','ò'=>'o','ö'=>'o',
        'ú'=>'u','ù'=>'u','û'=>'u','ü'=>'u','ç'=>'c',
    ]);
    return preg_replace('/[^a-z0-9]/', '', $t) ?? '';
}

/** Guarda a falha no banco: quem abrir a tela amanhã precisa saber. */
function registrarErro(PDO $pdo, int $ano, int $mes, string $mensagem): void
{
    try {
        $pdo->prepare(
            "INSERT INTO central_coletas (ano, mes, origem, status, mensagem)
             VALUES (?, ?, 'reciclalead', 'erro', ?)"
        )->execute([$ano, $mes, mb_substr($mensagem, 0, 400)]);
    } catch (Throwable $e) {
        // Se nem isso funciona, o problema é o banco — e aí a saída de erro
        // da tarefa agendada é o que sobra.
        fwrite(STDERR, 'Não consegui registrar a falha no banco: ' . $e->getMessage() . "\n");
    }
}

/** HTTP com cURL. Devolve o JSON já decodificado. */
function requisitar(string $metodo, string $url, ?array $corpo, ?string $token, int $timeout): array
{
    $ch = curl_init($url);

    $cabecalhos = ['Accept: application/json'];
    if ($token !== null) {
        $cabecalhos[] = 'Authorization: Bearer ' . $token;
    }

    $opcoes = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_USERAGENT      => 'APASBS-Coletor/1.0',
    ];

    if ($metodo === 'POST') {
        $opcoes[CURLOPT_POST]       = true;
        $opcoes[CURLOPT_POSTFIELDS] = json_encode($corpo ?? [], JSON_UNESCAPED_UNICODE);
        $cabecalhos[] = 'Content-Type: application/json';
    }

    $opcoes[CURLOPT_HTTPHEADER] = $cabecalhos;
    curl_setopt_array($ch, $opcoes);

    $resposta = curl_exec($ch);
    $codigo   = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $erroCurl = curl_error($ch);
    curl_close($ch);

    if ($resposta === false) {
        throw new RuntimeException('não consegui falar com o ReciclaLead: ' . $erroCurl);
    }

    if ($codigo === 401 || $codigo === 403) {
        throw new RuntimeException('o ReciclaLead recusou a credencial (HTTP ' . $codigo
            . '). Confira usuário e senha em config/reciclalead.php.');
    }

    if ($codigo >= 400) {
        throw new RuntimeException('o ReciclaLead respondeu HTTP ' . $codigo . ': '
            . substr((string) $resposta, 0, 200));
    }

    $json = json_decode((string) $resposta, true);
    if (!is_array($json)) {
        throw new RuntimeException('a resposta não veio em JSON: ' . substr((string) $resposta, 0, 200));
    }

    return $json;
}

function linha(string $texto): void
{
    fwrite(STDOUT, date('[Y-m-d H:i:s] ') . $texto . "\n");
}

function erro(string $texto): never
{
    fwrite(STDERR, date('[Y-m-d H:i:s] ') . $texto . "\n");
    exit(1);
}
