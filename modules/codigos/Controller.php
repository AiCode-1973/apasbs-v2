<?php
declare(strict_types=1);

require_once BASE_PATH . '/modules/codigos/Model.php';
require_once BASE_PATH . '/modules/tuss/Model.php';

class CodigosController extends Controller
{
    protected string $modulo = 'codigos';

    private ExameCodigoModel $model;

    public function __construct()
    {
        $this->model = new ExameCodigoModel();
    }

    // ── Lista ─────────────────────────────────────────────────────────────

    public function lista(): void
    {
        Auth::requirePermissao('codigos', 'pode_ver');

        $filtro = [
            'busca'       => trim((string) ($_GET['q'] ?? '')),
            'convenio_id' => (int) ($_GET['convenio'] ?? 0),
            'so_ativos'   => !empty($_GET['ativos']),
            'sem_codigo'  => !empty($_GET['sem']),
        ];

        $porPagina = Paginacao::porPagina(50);
        $total     = $this->model->contar($filtro);
        $paginas   = max(1, (int) ceil($total / $porPagina));
        $pagina    = min(max(1, (int) ($_GET['pagina'] ?? 1)), $paginas);

        View::render('codigos', 'lista', [
            'exames'    => $this->model->listar($filtro, $porPagina, ($pagina - 1) * $porPagina),
            'total'     => $total,
            'pagina'    => $pagina,
            'porPagina' => $porPagina,
            'panorama'  => $this->model->panorama(),
            'convenios' => $this->model->convenios(),
            'filtro'    => $filtro,
        ]);
    }

    public function ver(): void
    {
        Auth::requirePermissao('codigos', 'pode_ver');

        $exame = $this->model->buscarPorId($this->id());
        if (!$exame) {
            Flash::erro('Exame não encontrado.');
            $this->redirect();
        }

        // Para cada código, onde mais ele aparece — a pergunta que o papel
        // não respondia.
        $tambem = [];
        foreach ($exame['codigos'] as $c) {
            $outros = $this->model->ondeMaisAparece($c['codigo'], (int) $exame['id']);
            if ($outros) {
                $tambem[$c['codigo']] = $outros;
            }
        }

        View::render('codigos', 'ver', [
            'exame'  => $exame,
            'tambem' => $tambem,
        ]);
    }

    // ── Formulário ────────────────────────────────────────────────────────

    public function novo(): void
    {
        Auth::requirePermissao('codigos', 'pode_criar');
        $this->formulario([
            'id'          => 0,
            'ativo'       => 1,
            'convenio_id' => (int) ($_GET['convenio'] ?? 0),
            'codigos'     => [],
        ]);
    }

    public function editar(): void
    {
        Auth::requirePermissao('codigos', 'pode_editar');

        $exame = $this->model->buscarPorId($this->id());
        if (!$exame) {
            Flash::erro('Exame não encontrado.');
            $this->redirect();
        }

        $this->formulario($exame);
    }

    private function formulario(array $exame, array $erros = []): void
    {
        View::render('codigos', 'form', [
            'exame'     => $exame,
            'erros'     => $erros,
            'convenios' => $this->model->convenios(),
        ]);
    }

    public function salvar(): void
    {
        $id = $this->id();
        Auth::requirePermissao('codigos', $id > 0 ? 'pode_editar' : 'pode_criar');
        $this->exigirPost();

        $d       = $this->doPost();
        $codigos = $this->codigosDoPost();
        $erros   = $this->validar($d, $codigos, $id);

        if ($erros) {
            $this->formulario($d + ['id' => $id, 'codigos' => $codigos], $erros);
            return;
        }

        try {
            $id = $this->model->salvar($id, $d, $codigos);
        } catch (PDOException $e) {
            error_log('Salvar código de exame: ' . $e->getMessage());
            Flash::erro('Não consegui gravar. Confira se algum código está repetido.');
            $this->formulario($d + ['id' => $id, 'codigos' => $codigos], $erros);
            return;
        }

        $quantos = count(array_filter($codigos, fn($c) => trim((string) $c['codigo']) !== ''));

        $this->auditar($this->id() > 0 ? Audit::EDITOU : Audit::CRIOU,
            sprintf('%s — %s (%d código%s)', $this->nomeConvenio($d['convenio_id']),
                $d['descricao'], $quantos, $quantos === 1 ? '' : 's'), $id);

        Flash::sucesso($quantos === 0
            ? 'Exame gravado. Falta acrescentar os códigos.'
            : 'Exame gravado com ' . $quantos . ' código' . ($quantos === 1 ? '' : 's') . '.');

        $this->redirect('ver', ['id' => $id]);
    }

    public function excluir(): void
    {
        Auth::requirePermissao('codigos', 'pode_excluir');
        $this->exigirPost();

        $id    = $this->id();
        $exame = $this->model->buscarPorId($id);

        if (!$exame) {
            Flash::erro('Exame não encontrado.');
            $this->redirect();
        }

        $this->model->excluir($id);
        $this->auditar(Audit::EXCLUIU, $exame['convenio'] . ' — ' . $exame['descricao'], $id);

        Flash::sucesso('Exame excluído, com os códigos dele.');
        $this->redirect();
    }

    /** Sugestões da Tabela 22 para o autocomplete das linhas de código. */
    public function buscar_tuss(): void
    {
        Auth::requirePermissao('codigos', 'pode_ver');

        $q = trim((string) ($_GET['q'] ?? ''));
        if (mb_strlen($q) < 2) {
            $this->json([]);
        }

        $this->json((new TussModel())->sugerir($q, 20));
    }

    // ── Apoio ─────────────────────────────────────────────────────────────

    private function doPost(): array
    {
        return [
            'convenio_id' => (int) ($_POST['convenio_id'] ?? 0),
            'descricao'   => trim((string) ($_POST['descricao'] ?? '')),
            'observacao'  => trim((string) ($_POST['observacao'] ?? '')),
            'ativo'       => isset($_POST['ativo']) ? 1 : 0,
        ];
    }

    /**
     * As linhas de código vêm em dois arrays paralelos. Linha em branco é
     * descartada aqui — a tela sempre oferece uma linha vazia a mais, e
     * ninguém deveria precisar apagá-la antes de salvar.
     */
    private function codigosDoPost(): array
    {
        $codigos = (array) ($_POST['codigo'] ?? []);
        $termos  = (array) ($_POST['termo'] ?? []);

        $saida = [];
        foreach ($codigos as $i => $codigo) {
            $codigo = trim((string) $codigo);
            if ($codigo === '') {
                continue;
            }
            $saida[] = [
                'codigo' => $codigo,
                'termo'  => trim((string) ($termos[$i] ?? '')),
            ];
        }
        return $saida;
    }

    private function validar(array $d, array $codigos, int $id): array
    {
        $erros = [];

        if ($d['convenio_id'] === 0) {
            $erros['convenio_id'] = 'Escolha o convênio.';
        }

        if ($d['descricao'] === '') {
            $erros['descricao'] = 'Informe a descrição do exame.';
        } elseif (mb_strlen($d['descricao']) > 200) {
            $erros['descricao'] = 'A descrição passa de 200 caracteres.';
        } elseif ($d['convenio_id'] > 0) {
            $jaTem = $this->model->existe($d['convenio_id'], $d['descricao'], $id);
            if ($jaTem) {
                // Mandar editar o que existe, em vez de criar um segundo:
                // a mesma informação em duas linhas é a origem de metade
                // das divergências de cadastro.
                $erros['descricao'] = 'Este convênio já tem este exame cadastrado. '
                    . 'Edite o registro existente para acrescentar códigos.';
                $erros['_link'] = url('codigos', 'ver', ['id' => (int) $jaTem['id']]);
            }
        }

        // Códigos repetidos na mesma tela: o banco recusaria, mas com uma
        // mensagem que ninguém entende.
        $vistos = [];
        foreach ($codigos as $c) {
            if (isset($vistos[$c['codigo']])) {
                $erros['codigos'] = 'O código ' . $c['codigo'] . ' está repetido na lista.';
                break;
            }
            $vistos[$c['codigo']] = true;

            if (mb_strlen($c['codigo']) > 30) {
                $erros['codigos'] = 'O código ' . mb_substr($c['codigo'], 0, 12) . '… passa de 30 caracteres.';
                break;
            }
        }

        return $erros;
    }

    private function nomeConvenio(int $id): string
    {
        foreach ($this->model->convenios(false) as $c) {
            if ((int) $c['id'] === $id) {
                return (string) $c['nome'];
            }
        }
        return '?';
    }
}
