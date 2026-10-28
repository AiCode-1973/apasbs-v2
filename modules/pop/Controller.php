<?php
declare(strict_types=1);

require_once BASE_PATH . '/modules/pop/Model.php';

class PopController extends Controller
{
    protected string $modulo = 'pop';

    private PopModel $model;

    public function __construct()
    {
        $this->model = new PopModel();
    }

    // ── Leitura ───────────────────────────────────────────────────────────

    public function lista(): void
    {
        Auth::requirePermissao('pop', 'pode_ver');

        $busca    = trim((string) ($_GET['q'] ?? ''));
        $gerencia = Auth::temPermissao('pop', 'pode_criar')
                 || Auth::temPermissao('pop', 'pode_editar');

        $pops = $this->model->listar($busca, !$gerencia);

        $porCategoria = [];
        foreach ($pops as $p) {
            $porCategoria[$p['categoria']][] = $p;
        }

        View::render('pop', 'lista', [
            'porCategoria' => $porCategoria,
            'total'        => count($pops),
            'busca'        => $busca,
            'gerencia'     => $gerencia,
        ]);
    }

    public function ver(): void
    {
        Auth::requirePermissao('pop', 'pode_ver');

        $id  = $this->id();
        $pop = $this->model->buscarPorId($id);

        if (!$pop || (!$pop['ativo'] && !Auth::temPermissao('pop', 'pode_editar'))) {
            Flash::erro('Procedimento não encontrado.');
            $this->redirect();
        }

        View::render('pop', 'ver', [
            'pop'    => $pop,
            'blocos' => $this->model->blocos($id),
        ]);
    }

    /** Folha para imprimir e deixar no balcão. */
    public function imprimir(): void
    {
        Auth::requirePermissao('pop', 'pode_ver');

        $id  = $this->id();
        $pop = $this->model->buscarPorId($id);

        if (!$pop) {
            Flash::erro('Procedimento não encontrado.');
            $this->redirect();
        }

        $this->auditar(Audit::BAIXOU, 'Imprimiu ' . $pop['codigo'] . ' — ' . $pop['titulo'], $id);

        View::render('pop', 'imprimir', [
            'pop'    => $pop,
            'blocos' => $this->model->blocos($id),
        ], 'limpo');
    }

    // ── Manutenção ────────────────────────────────────────────────────────

    public function novo(): void
    {
        Auth::requirePermissao('pop', 'pode_criar');

        View::render('pop', 'form', [
            'pop'        => ['categoria' => 'Geral', 'versao' => '1.0',
                             'revisado_em' => date('Y-m-d'), 'ordem' => 0],
            'blocos'     => [],
            'erros'      => [],
            'categorias' => $this->model->categorias(),
        ]);
    }

    public function salvar(): void
    {
        Auth::requirePermissao('pop', 'pode_criar');
        $this->exigirPost();

        $dados  = $this->sanitizar($_POST, true);
        $blocos = $this->blocosDoPost($_POST);
        $erros  = $this->validar($dados, $blocos);

        if ($erros) {
            View::render('pop', 'form', [
                'pop'        => $dados,
                'blocos'     => $blocos,
                'erros'      => $erros,
                'categorias' => $this->model->categorias(),
            ]);
            return;
        }

        if ($dados['ordem'] === 0) {
            $dados['ordem'] = $this->model->proximaOrdem($dados['categoria']);
        }

        $id = $this->model->salvar($dados, $blocos);

        $this->auditar(Audit::CRIOU, 'POP ' . $dados['codigo'] . ' — ' . $dados['titulo'], $id);
        Flash::sucesso('Procedimento publicado.');
        $this->redirect('ver', ['id' => $id]);
    }

    public function editar(): void
    {
        Auth::requirePermissao('pop', 'pode_editar');

        $id  = $this->id();
        $pop = $this->model->buscarPorId($id);

        if (!$pop) {
            Flash::erro('Procedimento não encontrado.');
            $this->redirect();
        }

        View::render('pop', 'form', [
            'pop'        => $pop,
            'blocos'     => $this->model->blocos($id),
            'erros'      => [],
            'categorias' => $this->model->categorias(),
        ]);
    }

    public function atualizar(): void
    {
        Auth::requirePermissao('pop', 'pode_editar');
        $this->exigirPost();

        $id = $this->id();

        if (!$this->model->buscarPorId($id)) {
            Flash::erro('Procedimento não encontrado.');
            $this->redirect();
        }

        $dados  = $this->sanitizar($_POST);
        $blocos = $this->blocosDoPost($_POST);
        $erros  = $this->validar($dados, $blocos, $id);

        if ($erros) {
            $dados['id'] = $id;
            View::render('pop', 'form', [
                'pop'        => $dados,
                'blocos'     => $blocos,
                'erros'      => $erros,
                'categorias' => $this->model->categorias(),
            ]);
            return;
        }

        $this->model->salvar($dados, $blocos, $id);

        $this->auditar(Audit::EDITOU, 'POP ' . $dados['codigo'] . ' — ' . $dados['titulo'], $id);
        Flash::sucesso('Procedimento atualizado.');
        $this->redirect('ver', ['id' => $id]);
    }

    public function excluir(): void
    {
        Auth::requirePermissao('pop', 'pode_excluir');
        $this->exigirPost();

        $id  = $this->id();
        $pop = $this->model->buscarPorId($id);

        if (!$pop) {
            Flash::erro('Procedimento não encontrado.');
            $this->redirect();
        }

        $this->model->excluir($id);

        $this->auditar(Audit::EXCLUIU, 'POP ' . $pop['codigo'] . ' — ' . $pop['titulo'], $id);
        Flash::sucesso('Procedimento excluído.');
        $this->redirect();
    }

    // ── Apoio ─────────────────────────────────────────────────────────────

    private function sanitizar(array $post, bool $criando = false): array
    {
        $codigo = mb_strtoupper(trim((string) ($post['codigo'] ?? '')));
        $codigo = preg_replace('/[^A-Z0-9._\-]/u', '', $codigo) ?? '';

        $categoria = trim(preg_replace('/\s+/u', ' ', (string) ($post['categoria'] ?? '')) ?? '');

        $revisado = (string) ($post['revisado_em'] ?? '');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $revisado)) {
            $revisado = '';
        }

        $ordem = (int) ($post['ordem'] ?? 0);

        return [
            'codigo'      => $codigo,
            'titulo'      => trim((string) ($post['titulo'] ?? '')),
            'categoria'   => $categoria !== '' ? mb_substr($categoria, 0, 80) : 'Geral',
            'resumo'      => mb_substr(trim((string) ($post['resumo'] ?? '')), 0, 400),
            'versao'      => mb_substr(trim((string) ($post['versao'] ?? '1.0')), 0, 10) ?: '1.0',
            'revisado_em' => $revisado,
            'ordem'       => max(0, min($ordem, 9999)),
            'ativo'       => $criando ? 1 : (isset($post['ativo']) ? 1 : 0),
        ];
    }

    /** tipo[], titulo_bloco[] e conteudo[] chegam alinhados por índice. */
    private function blocosDoPost(array $post): array
    {
        $tipos     = (array) ($post['tipo'] ?? []);
        $titulos   = (array) ($post['titulo_bloco'] ?? []);
        $conteudos = (array) ($post['conteudo'] ?? []);

        $blocos = [];
        foreach ($conteudos as $i => $conteudo) {
            $blocos[] = [
                'tipo'     => (string) ($tipos[$i] ?? 'texto'),
                'titulo'   => (string) ($titulos[$i] ?? ''),
                'conteudo' => (string) $conteudo,
            ];
        }
        return $blocos;
    }

    private function validar(array $dados, array $blocos, int $id = 0): array
    {
        $erros = [];

        if ($dados['codigo'] === '') {
            $erros['codigo'] = 'Informe o código do POP (ex.: POP-CA-014).';
        } elseif (mb_strlen($dados['codigo']) > 30) {
            $erros['codigo'] = 'Código deve ter no máximo 30 caracteres.';
        } elseif ($this->model->codigoExiste($dados['codigo'], $id)) {
            $erros['codigo'] = 'Já existe um POP com este código.';
        }

        if ($dados['titulo'] === '') {
            $erros['titulo'] = 'Informe o título.';
        } elseif (mb_strlen($dados['titulo']) > 180) {
            $erros['titulo'] = 'Título deve ter no máximo 180 caracteres.';
        }

        $temConteudo = false;
        foreach ($blocos as $b) {
            if (trim($b['conteudo']) !== '') {
                $temConteudo = true;
                break;
            }
        }

        if (!$temConteudo) {
            $erros['blocos'] = 'Escreva pelo menos um bloco de conteúdo.';
        }

        return $erros;
    }
}
