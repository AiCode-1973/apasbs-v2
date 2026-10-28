<?php
declare(strict_types=1);

require_once BASE_PATH . '/modules/opme/Model.php';

class OpmeController extends Controller
{
    protected string $modulo = 'opme';

    private OpmeModel $model;

    public function __construct()
    {
        $this->model = new OpmeModel();
    }

    // ── Lista de notas ────────────────────────────────────────────────────

    public function lista(): void
    {
        Auth::requirePermissao('opme', 'pode_ver');

        $filtro = [
            'busca'         => trim((string) ($_GET['q'] ?? '')),
            'fornecedor_id' => (int) ($_GET['fornecedor'] ?? 0),
            'situacao'      => (string) ($_GET['situacao'] ?? ''),
            'ano'           => (int) ($_GET['ano'] ?? 0),
            'em_aberto'     => !empty($_GET['aberto']),
        ];

        $porPagina = Paginacao::porPagina(50);
        $total     = $this->model->contarNotas($filtro);
        // A última página encolhe quando um filtro entra ou o tamanho muda;
        // sem este teto, `?pagina=14` com 3 páginas devolveria uma tela vazia.
        $paginas   = max(1, (int) ceil($total / $porPagina));
        $pagina    = min(max(1, (int) ($_GET['pagina'] ?? 1)), $paginas);

        View::render('opme', 'lista', [
            'notas'        => $this->model->notas($filtro, $porPagina, ($pagina - 1) * $porPagina),
            'total'        => $total,
            'pagina'       => $pagina,
            'porPagina'    => $porPagina,
            'totais'       => $this->model->totaisNotas($filtro),
            'panorama'     => $this->model->panorama(),
            'fornecedores' => $this->model->fornecedores(),
            'anos'         => $this->model->anosDeCirurgia(),
            'filtro'       => $filtro,
        ]);
    }

    public function ver(): void
    {
        Auth::requirePermissao('opme', 'pode_ver');

        $id   = $this->id();
        $nota = $this->model->nota($id);

        if (!$nota) {
            Flash::erro('Nota não encontrada.');
            $this->redirect();
        }

        $parcelas = $this->model->parcelas($id);

        View::render('opme', 'ver', [
            'nota'         => $nota,
            'parcelas'     => $parcelas,
            'numeracaoRuim'=> $this->model->numeracaoConfusa($parcelas),
        ]);
    }

    // ── Nota: criar e editar ──────────────────────────────────────────────

    public function novo(): void
    {
        Auth::requirePermissao('opme', 'pode_criar');

        if (!$this->model->fornecedores()) {
            Flash::erro('Cadastre um fornecedor antes de lançar a primeira nota.');
            $this->redirect('fornecedores');
        }

        $this->formulario([
            'situacao'       => 'normal',
            'total_parcelas' => 1,
            'data_cirurgia'  => date('Y-m-d'),
            'hospital'       => 'HSE',
        ], [], [], true);
    }

    public function salvar(): void
    {
        Auth::requirePermissao('opme', 'pode_criar');
        $this->exigirPost();

        $dados    = $this->sanitizar($_POST);
        $parcelas = $this->parcelasDoPost($_POST, $dados);
        $erros    = $this->validar($dados, $parcelas);

        if ($erros) {
            $this->formulario($dados, $parcelas, $erros);
            return;
        }

        $id = $this->model->salvarNota($dados);
        $this->model->salvarParcelas($id, $parcelas);

        $this->auditar(Audit::CRIOU, $this->descricao($dados), $id);
        Flash::sucesso('Nota lançada com ' . count($parcelas)
            . ' parcela' . (count($parcelas) === 1 ? '' : 's') . '.');
        $this->redirect('ver', ['id' => $id]);
    }

    public function editar(): void
    {
        Auth::requirePermissao('opme', 'pode_editar');

        $id   = $this->id();
        $nota = $this->model->nota($id);

        if (!$nota) {
            Flash::erro('Nota não encontrada.');
            $this->redirect();
        }

        $this->formulario($nota, $this->model->parcelas($id));
    }

    public function atualizar(): void
    {
        Auth::requirePermissao('opme', 'pode_editar');
        $this->exigirPost();

        $id = $this->id();
        if (!$this->model->nota($id)) {
            Flash::erro('Nota não encontrada.');
            $this->redirect();
        }

        $dados    = $this->sanitizar($_POST);
        $parcelas = $this->parcelasDoPost($_POST, $dados);
        $erros    = $this->validar($dados, $parcelas, $id);

        if ($erros) {
            $dados['id'] = $id;
            $this->formulario($dados, $parcelas, $erros);
            return;
        }

        $this->model->salvarNota($dados, $id);
        $this->model->salvarParcelas($id, $parcelas);

        $this->auditar(Audit::EDITOU, $this->descricao($dados), $id);
        Flash::sucesso('Nota atualizada.');
        $this->redirect('ver', ['id' => $id]);
    }

    public function excluir(): void
    {
        Auth::requirePermissao('opme', 'pode_excluir');
        $this->exigirPost();

        $id   = $this->id();
        $nota = $this->model->nota($id);

        if (!$nota) {
            Flash::erro('Nota não encontrada.');
            $this->redirect();
        }

        $this->model->excluirNota($id);

        $this->auditar(Audit::EXCLUIU, $this->descricao($nota), $id);
        Flash::sucesso('Nota excluída junto com as parcelas dela.');
        $this->redirect();
    }

    // ── Parcelas ──────────────────────────────────────────────────────────

    public function pagar(): void
    {
        Auth::requirePermissao('opme', 'pode_editar');
        $this->exigirPost();

        $id      = $this->id();
        $parcela = $this->model->parcela($id);

        if (!$parcela) {
            Flash::erro('Parcela não encontrada.');
            $this->redirect();
        }

        $paga = !$parcela['pago'];
        $data = (string) ($_POST['data_pagamento'] ?? '');
        $data = preg_match('/^\d{4}-\d{2}-\d{2}$/', $data) ? $data : null;

        $this->model->marcarPaga($id, $paga, $data);

        $this->auditar(
            Audit::EDITOU,
            ($paga ? 'Baixou' : 'Estornou') . ' a parcela ' . $parcela['numero']
                . ' de ' . $parcela['fornecedor'] . ' — ' . $parcela['paciente']
                . ' (' . dinheiro_br((float) $parcela['valor']) . ')',
            $id
        );

        Flash::sucesso($paga ? 'Parcela baixada como paga.' : 'Baixa desfeita.');
        $this->voltar($parcela['nota_id']);
    }

    public function renumerar(): void
    {
        Auth::requirePermissao('opme', 'pode_editar');
        $this->exigirPost();

        $id = $this->id();
        if (!$this->model->nota($id)) {
            Flash::erro('Nota não encontrada.');
            $this->redirect();
        }

        $mudou = $this->model->renumerar($id);

        $this->auditar(Audit::EDITOU, 'Renumerou as parcelas pela ordem de vencimento', $id);
        Flash::sucesso($mudou > 0
            ? $mudou . ' parcela' . ($mudou === 1 ? '' : 's') . ' renumerada' . ($mudou === 1 ? '' : 's') . '.'
            : 'A numeração já estava certa.');
        $this->redirect('ver', ['id' => $id]);
    }

    // ── Vencimentos do mês ────────────────────────────────────────────────

    public function vencimentos(): void
    {
        Auth::requirePermissao('opme', 'pode_ver');

        [$ano, $mes] = $this->competencia();

        $filtro = [
            'fornecedor_id'    => (int) ($_GET['fornecedor'] ?? 0),
            'somente_abertas'  => !empty($_GET['aberto']),
        ];

        View::render('opme', 'vencimentos', [
            'ano'           => $ano,
            'mes'           => $mes,
            'parcelas'      => $this->model->vencimentos($ano, $mes, $filtro),
            'totais'        => $this->model->totaisMes($ano, $mes),
            'porFornecedor' => $this->model->porFornecedorNoMes($ano, $mes),
            'fornecedores'  => $this->model->fornecedores(),
            'meses'         => $this->model->mesesComParcela(),
            'filtro'        => $filtro,
        ]);
    }

    public function imprimir(): void
    {
        Auth::requirePermissao('opme', 'pode_ver');

        [$ano, $mes] = $this->competencia();

        $this->auditar(Audit::BAIXOU, 'Imprimiu os vencimentos de ' . competencia_br($ano, $mes));

        View::render('opme', 'imprimir', [
            'ano'           => $ano,
            'mes'           => $mes,
            'parcelas'      => $this->model->vencimentos($ano, $mes),
            'totais'        => $this->model->totaisMes($ano, $mes),
            'porFornecedor' => $this->model->porFornecedorNoMes($ano, $mes),
        ], 'limpo');
    }

    // ── Fornecedores ──────────────────────────────────────────────────────

    public function fornecedores(): void
    {
        Auth::requirePermissao('opme', 'pode_ver');

        $busca  = trim((string) ($_GET['q'] ?? ''));
        $editar = (int) ($_GET['editar'] ?? 0);
        $forn   = $editar > 0 ? $this->model->fornecedor($editar) : false;

        View::render('opme', 'fornecedores', [
            'fornecedores' => $this->model->fornecedores(false, $busca),
            'busca'        => $busca,
            'erros'        => [],
            'fornecedor'   => $forn ?: ['ativo' => 1],
        ]);
    }

    public function salvar_fornecedor(): void
    {
        Auth::requirePermissao('opme', 'pode_criar');
        $this->exigirPost();

        $id    = $this->id();
        $dados = [
            'nome'       => trim(preg_replace('/\s+/u', ' ', (string) ($_POST['nome'] ?? '')) ?? ''),
            'cnpj'       => mb_substr(trim((string) ($_POST['cnpj'] ?? '')), 0, 20),
            'contato'    => mb_substr(trim((string) ($_POST['contato'] ?? '')), 0, 120),
            'telefone'   => mb_substr(trim((string) ($_POST['telefone'] ?? '')), 0, 30),
            'email'      => mb_substr(trim((string) ($_POST['email'] ?? '')), 0, 150),
            'observacao' => mb_substr(trim((string) ($_POST['observacao'] ?? '')), 0, 300),
            'ativo'      => isset($_POST['ativo']) ? 1 : 0,
        ];

        $erros = [];
        if ($dados['nome'] === '') {
            $erros['nome'] = 'Informe o nome do fornecedor.';
        } elseif (mb_strlen($dados['nome']) > 120) {
            $erros['nome'] = 'No máximo 120 caracteres.';
        } elseif ($this->model->fornecedorNomeExiste($dados['nome'], $id)) {
            $erros['nome'] = 'Já existe um fornecedor com este nome.';
        }

        if ($dados['email'] !== '' && !filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
            $erros['email'] = 'E-mail inválido.';
        }

        if ($erros) {
            $dados['id'] = $id;
            View::render('opme', 'fornecedores', [
                'fornecedores' => $this->model->fornecedores(false),
                'busca'        => '',
                'erros'        => $erros,
                'fornecedor'   => $dados,
            ]);
            return;
        }

        $novoId = $this->model->salvarFornecedor($dados, $id);

        $this->auditar($id > 0 ? Audit::EDITOU : Audit::CRIOU, 'Fornecedor ' . $dados['nome'], $novoId);
        Flash::sucesso($id > 0 ? 'Fornecedor atualizado.' : 'Fornecedor cadastrado.');
        $this->redirect('fornecedores');
    }

    public function excluir_fornecedor(): void
    {
        Auth::requirePermissao('opme', 'pode_excluir');
        $this->exigirPost();

        $id   = $this->id();
        $forn = $this->model->fornecedor($id);

        if (!$forn) {
            Flash::erro('Fornecedor não encontrado.');
            $this->redirect('fornecedores');
        }

        if ($this->model->fornecedorTemNota($id)) {
            Flash::erro('Este fornecedor já tem nota lançada. Em vez de excluir, marque como inativo.');
            $this->redirect('fornecedores');
        }

        $this->model->excluirFornecedor($id);

        $this->auditar(Audit::EXCLUIU, 'Fornecedor ' . $forn['nome'], $id);
        Flash::sucesso('Fornecedor excluído.');
        $this->redirect('fornecedores');
    }

    // ── Apoio ─────────────────────────────────────────────────────────────

    private function competencia(): array
    {
        $ano = (int) ($_GET['ano'] ?? date('Y'));
        $mes = (int) ($_GET['mes'] ?? date('n'));

        if ($ano < 2000 || $ano > 2100) {
            $ano = (int) date('Y');
        }
        if ($mes < 1 || $mes > 12) {
            $mes = (int) date('n');
        }

        return [$ano, $mes];
    }

    private function formulario(array $nota, array $parcelas = [], array $erros = [], bool $novo = false): void
    {
        View::render('opme', 'form', [
            'nota'         => $nota,
            'parcelas'     => $parcelas,
            'erros'        => $erros,
            'fornecedores' => $this->model->fornecedores(),
            'medicos'      => $this->model->medicos(),
            'hospitais'    => $this->model->hospitais(),
        ]);
    }

    private function sanitizar(array $post): array
    {
        $data = function (string $valor): string {
            $valor = trim($valor);
            return preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor) ? $valor : '';
        };

        $situacao = (string) ($post['situacao'] ?? 'normal');

        return [
            'fornecedor_id'   => (int) ($post['fornecedor_id'] ?? 0),
            'numero_nf'       => mb_substr(trim((string) ($post['numero_nf'] ?? '')), 0, 40),
            'paciente'        => mb_substr(trim(preg_replace('/\s+/u', ' ', (string) ($post['paciente'] ?? '')) ?? ''), 0, 150),
            'data_cirurgia'   => $data((string) ($post['data_cirurgia'] ?? '')),
            'hospital'        => mb_substr(trim((string) ($post['hospital'] ?? '')), 0, 80),
            'medico'          => mb_substr(trim(preg_replace('/\s+/u', ' ', (string) ($post['medico'] ?? '')) ?? ''), 0, 120),
            'valor_total'     => valor_br((string) ($post['valor_total'] ?? '0')),
            'data_nf'         => $data((string) ($post['data_nf'] ?? '')),
            'total_parcelas'  => max(1, min((int) ($post['total_parcelas'] ?? 1), 60)),
            'situacao'        => isset(OpmeModel::SITUACOES[$situacao]) ? $situacao : 'normal',
            'encaminhado_hse' => isset($post['encaminhado_hse']) ? 1 : 0,
            'observacao'      => mb_substr(trim((string) ($post['observacao'] ?? '')), 0, 300),
        ];
    }

    /** numero[], vencimento[] e valor[] chegam alinhados por índice. */
    private function parcelasDoPost(array $post, array $nota): array
    {
        $vencimentos = (array) ($post['vencimento'] ?? []);
        $valores     = (array) ($post['valor'] ?? []);

        $parcelas = [];
        foreach ($vencimentos as $i => $venc) {
            $venc = trim((string) $venc);
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $venc)) {
                continue;
            }
            $parcelas[] = [
                'numero'     => count($parcelas) + 1,
                'vencimento' => $venc,
                'valor'      => valor_br((string) ($valores[$i] ?? '0')),
            ];
        }

        usort($parcelas, fn($a, $b) => strcmp($a['vencimento'], $b['vencimento']));
        foreach ($parcelas as $i => &$p) {
            $p['numero'] = $i + 1;
        }
        unset($p);

        return $parcelas;
    }

    private function validar(array $d, array $parcelas, int $id = 0): array
    {
        $erros = [];

        if ($d['paciente'] === '') {
            $erros['paciente'] = 'Informe a paciente.';
        }

        if (!$this->model->fornecedor($d['fornecedor_id'])) {
            $erros['fornecedor_id'] = 'Escolha o fornecedor.';
        }

        if ($d['numero_nf'] === '') {
            $erros['numero_nf'] = 'Informe o número da nota.';
        } elseif (!$erros && $this->model->notaExiste($d['fornecedor_id'], $d['numero_nf'], $d['paciente'], $id)) {
            $erros['numero_nf'] = 'Este fornecedor já tem esta nota lançada para esta paciente.';
        }

        if ($d['valor_total'] <= 0 && $d['situacao'] === 'normal') {
            $erros['valor_total'] = 'Informe o valor da nota.';
        }

        // Vencimentos repetidos quebrariam a chave da parcela, e na prática
        // significam a mesma parcela lançada duas vezes.
        $datas = array_column($parcelas, 'vencimento');
        if (count($datas) !== count(array_unique($datas))) {
            $erros['parcelas'] = 'Há duas parcelas com o mesmo vencimento.';
        }

        $soma = 0.0;
        foreach ($parcelas as $p) {
            $soma += (float) $p['valor'];
        }

        // Diferença de centavos é normal ao dividir; acima disso é engano.
        if ($parcelas && $d['valor_total'] > 0 && abs($soma - $d['valor_total']) > 1.00) {
            $erros['soma'] = 'A soma das parcelas (' . dinheiro_br($soma) . ') não bate com o valor da nota ('
                . dinheiro_br($d['valor_total']) . ').';
        }

        return $erros;
    }

    private function descricao(array $d): string
    {
        $forn = $this->model->fornecedor((int) $d['fornecedor_id']);

        return 'NF ' . ($d['numero_nf'] ?? '?')
            . ' — ' . ($forn['nome'] ?? '?')
            . ' — ' . ($d['paciente'] ?? '?')
            . ' (' . dinheiro_br((float) ($d['valor_total'] ?? 0)) . ')';
    }

    /** Volta para onde o usuário estava: a nota ou a tela de vencimentos. */
    private function voltar(int $notaId): void
    {
        $origem = (string) ($_POST['origem'] ?? '');

        if ($origem === 'vencimentos') {
            $this->redirect('vencimentos', [
                'ano' => (int) ($_POST['ano'] ?? date('Y')),
                'mes' => (int) ($_POST['mes'] ?? date('n')),
            ]);
        }

        $this->redirect('ver', ['id' => $notaId]);
    }
}
