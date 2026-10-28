<?php
declare(strict_types=1);

/** Escapa qualquer valor para saída em HTML. */
function e(mixed $valor): string
{
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Escapa os curingas do LIKE para que a busca digitada seja literal.
 * Use sempre junto com ESCAPE '!' na consulta:
 *     WHERE nome LIKE :busca ESCAPE '!'
 */
function like_escape(string $termo): string
{
    return str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $termo);
}

/** URL interna do sistema: url('cirurgias', 'editar', ['id' => 3]) */
function url(string $mod = 'usuarios', string $action = 'lista', array $params = []): string
{
    $query = array_merge(['mod' => $mod, 'action' => $action], $params);
    return BASE_URL . '/?' . http_build_query($query);
}

/**
 * IMC a partir de peso (kg) e altura (m). Null quando falta um dos dois.
 *
 * É conta, não dado: guardar o resultado no banco criaria duas verdades que
 * divergem na primeira correção de digitação do peso.
 */
function imc(float|string|null $peso, float|string|null $altura): ?float
{
    $peso   = (float) $peso;
    $altura = (float) $altura;

    if ($peso <= 0 || $altura <= 0) {
        return null;
    }

    return round($peso / ($altura * $altura), 1);
}

/** A faixa do IMC por escrito, como a OMS classifica. */
function imc_faixa(?float $valor): string
{
    return match (true) {
        $valor === null => '',
        $valor < 18.5   => 'Abaixo do peso',
        $valor < 25     => 'Peso adequado',
        $valor < 30     => 'Sobrepeso',
        $valor < 35     => 'Obesidade grau I',
        $valor < 40     => 'Obesidade grau II',
        default         => 'Obesidade grau III',
    };
}

/**
 * A lista do módulo como o usuário a deixou: url_lista('autorizacoes')
 *
 * Para o "Cancelar" do formulário e a seta de voltar — quem desiste de uma
 * edição espera achar a tela de onde saiu, com o filtro que tinha posto.
 * Sem memória guardada, devolve a lista limpa, igual a url($mod, 'lista').
 */
function url_lista(string $mod, array $extras = []): string
{
    return Navegacao::lista($mod, $extras);
}

/**
 * URL de um arquivo estático com selo de versão: asset('css/app.css')
 *
 * O selo é a data de modificação do arquivo. Sem ele o navegador continua
 * usando o CSS e o JS que já tem em cache depois de uma atualização — e a
 * tela quebra de formas difíceis de diagnosticar (estilo sumindo, componente
 * do Alpine que não existe mais no arquivo antigo). Mudou o arquivo, muda a
 * URL, o navegador baixa de novo. Nada muda quando o arquivo não mudou.
 */
function asset(string $caminho): string
{
    $rel  = ltrim($caminho, '/');
    $selo = @filemtime(BASE_PATH . '/assets/' . $rel);

    return BASE_URL . '/assets/' . $rel . ($selo ? '?v=' . $selo : '');
}

/** Formata bytes de forma legível. */
function formatar_tamanho(int $bytes): string
{
    if ($bytes < 1024) {
        return $bytes . ' B';
    }
    if ($bytes < 1024 * 1024) {
        return number_format($bytes / 1024, 1, ',', '.') . ' KB';
    }
    return number_format($bytes / (1024 * 1024), 1, ',', '.') . ' MB';
}

/** Formata CPF 12345678901 → 123.456.789-01 */
function formatar_cpf(string $cpf): string
{
    $cpf = preg_replace('/\D/', '', $cpf) ?? '';
    if (strlen($cpf) !== 11) {
        return $cpf;
    }
    return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
}

/** Data do banco → d/m/Y (ou traço quando vazia) */
function data_br(?string $data, string $vazio = '—'): string
{
    if (empty($data) || str_starts_with($data, '0000')) {
        return $vazio;
    }
    $ts = strtotime($data);
    return $ts ? date('d/m/Y', $ts) : $vazio;
}

/** Nomes dos meses, indexados de 1 a 12. */
function meses(): array
{
    return [
        1 => 'Janeiro',   2 => 'Fevereiro', 3 => 'Março',    4 => 'Abril',
        5 => 'Maio',      6 => 'Junho',     7 => 'Julho',    8 => 'Agosto',
        9 => 'Setembro', 10 => 'Outubro',  11 => 'Novembro', 12 => 'Dezembro',
    ];
}

/** 3 → "Março" */
function nome_mes(int $mes): string
{
    return meses()[$mes] ?? '—';
}

/** (2026, 3) → "Março de 2026" */
function competencia_br(int $ano, int $mes): string
{
    return nome_mes($mes) . ' de ' . $ano;
}

/** (2026, 3) → "03/2026" */
function competencia_curta(int $ano, int $mes): string
{
    return str_pad((string) $mes, 2, '0', STR_PAD_LEFT) . '/' . $ano;
}

/** Número inteiro com separador de milhar brasileiro. */
function numero(int $valor): string
{
    return number_format($valor, 0, ',', '.');
}

/**
 * Dígitos → telefone brasileiro: "13996486125" → "(13) 99648-6125".
 *
 * O que não tiver 10 ou 11 dígitos volta como veio — número torto exibido
 * cru é mais honesto que número torto com máscara por cima, que parece
 * certo e não é.
 */
function telefone_br(?string $numero): string
{
    $d = preg_replace('/\D/', '', (string) $numero) ?? '';

    return match (strlen($d)) {
        11      => sprintf('(%s) %s-%s', substr($d, 0, 2), substr($d, 2, 5), substr($d, 7)),
        10      => sprintf('(%s) %s-%s', substr($d, 0, 2), substr($d, 2, 4), substr($d, 6)),
        default => (string) $numero,
    };
}

/** 1234.5 → "R$ 1.234,50" */
function dinheiro_br(float $valor, bool $simbolo = true): string
{
    return ($simbolo ? 'R$ ' : '') . number_format($valor, 2, ',', '.');
}

/**
 * O que o usuário digitou → float.
 *
 * Aceita as duas formas que aparecem na prática: "1.234,56" (como se
 * escreve aqui) e "1234.56" (como o teclado numérico às vezes produz).
 * A regra é olhar o último separador: se for vírgula, ela é o decimal.
 */
function valor_br(string $valor): float
{
    $valor = trim(str_replace(['R$', ' ', "\xc2\xa0"], '', $valor));
    if ($valor === '') {
        return 0.0;
    }

    $ultimaVirgula = strrpos($valor, ',');
    $ultimoPonto   = strrpos($valor, '.');

    if ($ultimaVirgula !== false && ($ultimoPonto === false || $ultimaVirgula > $ultimoPonto)) {
        $valor = str_replace('.', '', $valor);
        $valor = str_replace(',', '.', $valor);
    } elseif ($ultimaVirgula === false && $ultimoPonto !== false
              && preg_match('/\.\d{3}$/', $valor)) {
        // "4.450" é quatro mil e quatrocentos e cinquenta, não quatro reais
        // e quarenta e cinco centavos: dinheiro não tem três casas decimais.
        $valor = str_replace('.', '', $valor);
    } else {
        $valor = str_replace(',', '', $valor);
    }

    return round((float) $valor, 2);
}

/** Data e hora do banco → d/m/Y H:i */
function datahora_br(?string $data, string $vazio = '—'): string
{
    if (empty($data) || str_starts_with($data, '0000')) {
        return $vazio;
    }
    $ts = strtotime($data);
    return $ts ? date('d/m/Y H:i', $ts) : $vazio;
}

/** Dias da semana por extenso, 0 = domingo (mesma base do date('w')). */
function dias_semana(): array
{
    return [
        0 => 'Domingo', 1 => 'Segunda-feira', 2 => 'Terça-feira', 3 => 'Quarta-feira',
        4 => 'Quinta-feira', 5 => 'Sexta-feira', 6 => 'Sábado',
    ];
}

/** Dias da semana abreviados, 0 = domingo. */
function dias_semana_curto(): array
{
    return [0 => 'Dom', 1 => 'Seg', 2 => 'Ter', 3 => 'Qua', 4 => 'Qui', 5 => 'Sex', 6 => 'Sáb'];
}

/** '2026-09-01' → 'Terça-feira' */
function dia_semana_br(string $data): string
{
    $ts = strtotime($data);
    return $ts ? (dias_semana()[(int) date('w', $ts)] ?? '—') : '—';
}

/** '10:00:00' do MySQL → '10:00' */
function hora_curta(?string $hora): string
{
    return $hora ? substr($hora, 0, 5) : '—';
}

/** Turnos aceitos na agenda. */
function turnos(): array
{
    return ['manha' => 'Manhã', 'tarde' => 'Tarde'];
}

/** 'manha' → 'Manhã' */
function nome_turno(string $turno): string
{
    return turnos()[$turno] ?? '—';
}

/** "8M", "512K", "1G" do php.ini → bytes. */
function ini_bytes(string $valor): int
{
    $valor = trim($valor);
    if ($valor === '') {
        return 0;
    }

    $numero = (int) $valor;
    $unidade = strtolower(substr($valor, -1));

    return match ($unidade) {
        'g'     => $numero * 1024 * 1024 * 1024,
        'm'     => $numero * 1024 * 1024,
        'k'     => $numero * 1024,
        default => $numero,
    };
}

/**
 * Maior arquivo que este servidor realmente aceita: o menor entre o limite
 * do próprio sistema e os dois do php.ini. Serve para a tela prometer o que
 * o servidor consegue cumprir.
 */
function limite_upload(int $limiteDoSistema): int
{
    $candidatos = array_filter([
        $limiteDoSistema,
        ini_bytes((string) ini_get('upload_max_filesize')),
        ini_bytes((string) ini_get('post_max_size')),
    ]);

    return $candidatos ? (int) min($candidatos) : $limiteDoSistema;
}

/** Tira tudo que não é dígito. */
function so_digitos(string $valor): string
{
    return preg_replace('/\D/', '', $valor) ?? '';
}

/**
 * CPF válido pelos dígitos verificadores.
 * Recusa também as sequências repetidas (111.111.111-11 e afins), que
 * passam no cálculo mas não existem na Receita.
 */
function cpf_valido(string $cpf): bool
{
    if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
        return false;
    }

    for ($t = 9; $t < 11; $t++) {
        $soma = 0;
        for ($i = 0; $i < $t; $i++) {
            $soma += (int) $cpf[$i] * ($t + 1 - $i);
        }
        $resto = ($soma * 10) % 11;
        if ($resto === 10 || $resto === 11) {
            $resto = 0;
        }
        if ($resto !== (int) $cpf[$t]) {
            return false;
        }
    }

    return true;
}

