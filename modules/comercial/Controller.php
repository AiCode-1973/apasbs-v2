<?php
declare(strict_types=1);

require_once BASE_PATH . '/modules/comercial/Model.php';

/**
 * Comercial — as tabelas de valores dos convênios.
 *
 * Duas telas de dado: as tabelas (um edital de um convênio, com vigência) e
 * os itens delas. A pergunta que o módulo responde é sempre a mesma —
 * "quanto o convênio paga por isto?" —, e é por isso que a lista de itens é
 * uma só, com filtro por tipo, em vez de uma tela por anexo do edital.
 */
class ComercialController extends Controller
{
    protected string $modulo = 'comercial';

    private ComercialModel $model;

    public function __construct()
    {
        $this->model = new ComercialModel();
    }

    // ── Itens ─────────────────────────────────────────────────────────────

    public function lista(): void
    {
        Auth::requirePermissao('comercial', 'pode_ver');

        $tabelas = $this->model->tabelas();

        if (!$tabelas) {
            View::render('comercial', 'lista', [
                'itens' => [], 'tabelas' => [], 'tabela' => false, 'grupos' => [],
                'panorama' => [], 'filtro' => [], 'total' => 0, 'pagina' => 1,
                'porPagina' => 50,
            ]);
            return;
        }

        $escolhida = (int) ($_GET['tabela'] ?? 0);
        $tabela = $escolhida > 0 ? $this->model->tabelaPorId($escolhida) : false;

        if (!$tabela) {
            $tabela = $this->model->tabelaVigente();
        }

        $filtro = [
            'tabela_id'  => (int) $tabela['id'],
            'busca'      => trim((string) ($_GET['q'] ?? '')),
            'tipo'       => (string) ($_GET['tipo'] ?? ''),
            'grupo'      => trim((string) ($_GET['grupo'] ?? '')),
            'de'         => $this->valor((string) ($_GET['de'] ?? '')),
            'ate'        => $this->valor((string) ($_GET['ate'] ?? '')),
            'so_manuais' => !empty($_GET['manuais']),
        ];

        $porPagina = Paginacao::porPagina(50);
        $total     = $this->model->contar($filtro);
        $paginas   = max(1, (int) ceil($total / $porPagina));
        $pagina    = min(max(1, (int) ($_GET['pagina'] ?? 1)), $paginas);

        View::render('comercial', 'lista', [
            'itens'     => $this->model->listar($filtro, $porPagina, ($pagina - 1) * $porPagina),
            'tabelas'   => $tabelas,
            'tabela'    => $tabela,
            'grupos'    => $this->model->grupos((int) $tabela['id']),
            'panorama'  => $this->model->panorama((int) $tabela['id']),
            'filtro'    => $filtro,
            'total'     => $total,
            'pagina'    => $pagina,
            'porPagina' => $porPagina,
        ]);
    }

    public function ver(): void
    {
        Auth::requirePermissao('comercial', 'pode_ver');

        $item = $this->model->itemPorId($this->id());

        if (!$item) {
            Flash::erro('Item não encontrado.');
            $this->redirect();
        }

        View::render('comercial', 'ver', [
            'i'         => $item,
            'historico' => $this->model->historico($item),
        ]);
    }

    public function novo(): void
    {
        Auth::requirePermissao('comercial', 'pode_criar');

        $tabela = $this->model->tabelaPorId((int) ($_GET['tabela'] ?? 0))
                  ?: $this->model->tabelaVigente();

        if (!$tabela) {
            Flash::erro('Cadastre antes a tabela de valores do convênio.');
            $this->redirect('tabelas');
        }

        $this->formulario([
            'id'        => 0,
            'tabela_id' => (int) $tabela['id'],
            'tipo'      => (string) ($_GET['tipo'] ?? 'procedimento'),
        ]);
    }

    public function editar(): void
    {
        Auth::requirePermissao('comercial', 'pode_editar');

        $item = $this->model->itemPorId($this->id());

        if (!$item) {
            Flash::erro('Item não encontrado.');
            $this->redirect();
        }

        $this->formulario($item);
    }

    public function salvar(): void
    {
        Auth::requirePermissao('comercial', $this->id() > 0 ? 'pode_editar' : 'pode_criar');
        $this->exigirPost();

        $id = $this->id();
        $d  = [
            'tabela_id' => (int) ($_POST['tabela_id'] ?? 0),
            'tipo'      => (string) ($_POST['tipo'] ?? 'procedimento'),
            'grupo'     => trim((string) ($_POST['grupo'] ?? '')),
            'codigo'    => trim((string) ($_POST['codigo'] ?? '')),
            'descricao' => trim((string) ($_POST['descricao'] ?? '')),
            'variante'  => trim((string) ($_POST['variante'] ?? '')),
            'valor'     => $this->valor((string) ($_POST['valor'] ?? '')),
            'extras'    => trim((string) ($_POST['extras'] ?? '')),
        ];

        $erros = $this->validar($d);

        if ($erros) {
            $this->formulario($d + ['id' => $id], $erros);
            return;
        }

        $novoId = $this->model->salvarItem($id, $d);

        $this->auditar($id > 0 ? Audit::EDITOU : Audit::CRIOU, sprintf(
            '%s%s — %s',
            ComercialModel::TIPOS[$d['tipo']] ?? $d['tipo'],
            $d['codigo'] !== '' ? ' ' . $d['codigo'] : '',
            $d['descricao']
        ), $novoId);

        Flash::sucesso($id > 0 ? 'Item atualizado.' : 'Item incluído na tabela.');
        $this->redirect('ver', ['id' => $novoId]);
    }

    public function excluir(): void
    {
        Auth::requirePermissao('comercial', 'pode_excluir');
        $this->exigirPost();

        $id   = $this->id();
        $item = $this->model->itemPorId($id);

        if (!$item) {
            Flash::erro('Item não encontrado.');
            $this->redirect();
        }

        // Item de carga volta na próxima geração do arquivo, e a exclusão
        // vira trabalho perdido — melhor dizer isso antes de apagar.
        if ($item['origem'] === 'edital') {
            Flash::aviso('Este item veio da carga do edital. Ele volta na próxima'
                . ' recarga do arquivo — corrija o edital, ou a geração, em vez de excluir.');
        }

        $this->model->excluirItem($id);
        $this->auditar(Audit::EXCLUIU, sprintf(
            '%s%s — %s',
            ComercialModel::TIPOS[$item['tipo']] ?? $item['tipo'],
            $item['codigo'] ? ' ' . $item['codigo'] : '',
            $item['descricao']
        ), $id);

        Flash::sucesso('Item excluído.');
        $this->redirect('lista', ['tabela' => (int) $item['tabela_id']]);
    }

    // ── Tabelas de valores ────────────────────────────────────────────────

    public function tabelas(): void
    {
        Auth::requirePermissao('comercial', 'pode_ver');

        View::render('comercial', 'tabelas', [
            'tabelas'  => $this->model->tabelas(),
            'erros'    => $this->errosGuardados(),
            'editando' => $this->model->tabelaPorId((int) ($_GET['id'] ?? 0)) ?: null,
        ]);
    }

    public function salvar_tabela(): void
    {
        Auth::requirePermissao('comercial', 'pode_editar');
        $this->exigirPost();

        $id = $this->id();
        $d  = [
            'convenio'   => trim((string) ($_POST['convenio'] ?? '')),
            'referencia' => trim((string) ($_POST['referencia'] ?? '')),
            'vigencia'   => $this->data((string) ($_POST['vigencia'] ?? '')),
            'observacao' => trim((string) ($_POST['observacao'] ?? '')),
            'ativo'      => isset($_POST['ativo']) ? 1 : 0,
        ];

        $erros = [];

        if ($d['convenio'] === '') {
            $erros['convenio'] = 'Informe o convênio.';
        }

        if ($d['referencia'] === '') {
            $erros['referencia'] = 'Informe o edital ou contrato de referência.';
        }

        if (!$d['vigencia']) {
            $erros['vigencia'] = 'Informe a data em que esta tabela passa a valer.';
        } elseif ($this->model->tabelaRepetida($d, $id)) {
            $erros['vigencia'] = 'Já existe esta tabela, com este convênio e esta vigência.';
        }

        if ($erros) {
            $_SESSION['com_erros'] = $erros;
            $this->redirect('tabelas', $id > 0 ? ['id' => $id] : []);
        }

        $novoId = $this->model->salvarTabela($id, $d);

        $this->auditar($id > 0 ? Audit::EDITOU : Audit::CRIOU,
            sprintf('Tabela %s — %s, vigência %s', $d['convenio'], $d['referencia'],
                    data_br($d['vigencia'])), $novoId);

        Flash::sucesso($id > 0 ? 'Tabela atualizada.' : 'Tabela cadastrada.');
        $this->redirect('tabelas');
    }

    public function excluir_tabela(): void
    {
        Auth::requirePermissao('comercial', 'pode_excluir');
        $this->exigirPost();

        $id     = $this->id();
        $tabela = $this->model->tabelaPorId($id);

        if (!$tabela) {
            Flash::erro('Tabela não encontrada.');
            $this->redirect('tabelas');
        }

        $itens = $this->model->panorama($id);
        $quantos = array_sum($itens);

        // Apagar a tabela leva os itens junto (CASCADE). Com milhares de
        // linhas dentro, isso não pode acontecer por um clique distraído:
        // a saída normal é desativar.
        if ($quantos > 0 && empty($_POST['confirmo_itens'])) {
            Flash::erro(sprintf(
                'Esta tabela tem %s itens. Excluí-la apaga todos eles. Se é isso mesmo,'
                . ' desative-a primeiro e confirme na tela da tabela.',
                numero($quantos)
            ));
            $this->redirect('tabelas');
        }

        $this->model->excluirTabela($id);
        $this->auditar(Audit::EXCLUIU, sprintf(
            'Tabela %s — %s (%s itens)', $tabela['convenio'], $tabela['referencia'],
            numero($quantos)
        ), $id);

        Flash::sucesso('Tabela excluída.');
        $this->redirect('tabelas');
    }

    // ── Apoio ─────────────────────────────────────────────────────────────

    private function formulario(array $item, array $erros = []): void
    {
        View::render('comercial', 'form', [
            'i'       => $item,
            'erros'   => $erros,
            'tabelas' => $this->model->tabelas(),
            'grupos'  => $this->model->grupos((int) ($item['tabela_id'] ?? 0)),
        ]);
    }

    private function validar(array $d): array
    {
        $erros = [];

        if ($d['tabela_id'] <= 0 || !$this->model->tabelaPorId($d['tabela_id'])) {
            $erros['tabela_id'] = 'Escolha a tabela de valores.';
        }

        if (!isset(ComercialModel::TIPOS[$d['tipo']])) {
            $erros['tipo'] = 'Tipo inválido.';
        }

        if ($d['descricao'] === '') {
            $erros['descricao'] = 'Informe a descrição do item.';
        } elseif (mb_strlen($d['descricao']) > 400) {
            $erros['descricao'] = 'A descrição passa de 400 caracteres.';
        }

        if ($d['valor'] !== null && ($d['valor'] < 0 || $d['valor'] > 9999999)) {
            $erros['valor'] = 'Valor fora do possível.';
        }

        // JSON quebrado viraria tela em branco na hora de ler os extras.
        if ($d['extras'] !== '' && json_decode($d['extras'], true) === null) {
            $erros['extras'] = 'Os dados extras precisam ser um JSON válido — ex.: {"porte":"3A"}.';
        }

        return $erros;
    }

    /** "1.234,56" e "1234.56" viram 1234.56; vazio vira nulo. */
    private function valor(string $texto): ?float
    {
        $texto = trim($texto);
        if ($texto === '') {
            return null;
        }

        if (str_contains($texto, ',')) {
            $texto = str_replace('.', '', $texto);
            $texto = str_replace(',', '.', $texto);
        }

        return is_numeric($texto) ? (float) $texto : null;
    }

    private function data(string $valor): ?string
    {
        $valor = trim($valor);
        if ($valor === '') {
            return null;
        }

        $d = DateTimeImmutable::createFromFormat('Y-m-d', $valor);
        return ($d && $d->format('Y-m-d') === $valor) ? $valor : null;
    }

    private function errosGuardados(): array
    {
        $erros = (array) ($_SESSION['com_erros'] ?? []);
        unset($_SESSION['com_erros']);
        return $erros;
    }
}
