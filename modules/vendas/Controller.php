<?php
declare(strict_types=1);

require_once BASE_PATH . '/modules/vendas/Model.php';

/**
 * Vendas — as propostas de plano, por vendedor.
 *
 * A venda é a promessa; o contrato é o fato. Enquanto a proposta está em
 * negociação, os dados do titular moram aqui; fechada, ela cria o contrato
 * no Cadastro e passa a apontar para ele. Não existe cópia da família nos
 * dois módulos — dois lugares guardando o mesmo titular divergem na
 * primeira correção.
 *
 * Quem tem login amarrado a um vendedor só enxerga as próprias vendas. A
 * trava fica aqui, no controller, e não na tela: esconder o botão não
 * protege nada.
 */
class VendasController extends Controller
{
    protected string $modulo = 'vendas';

    private VendasModel $model;

    /** O vendedor correspondente a quem está logado, se houver. */
    private array|false $meu;

    public function __construct()
    {
        $this->model = new VendasModel();
        $this->meu   = $this->model->vendedorDoUsuario(Auth::id());
    }

    /**
     * O vendedor a que este usuário está preso.
     *
     * Admin enxerga tudo — é quem coordena. Vendedor com login enxerga
     * apenas o que é dele.
     */
    private function travaVendedor(): int
    {
        return (!Auth::isAdmin() && $this->meu) ? (int) $this->meu['id'] : 0;
    }

    private function podeVer(array $proposta): bool
    {
        $trava = $this->travaVendedor();
        return $trava === 0 || (int) $proposta['vendedor_id'] === $trava;
    }

    /**
     * O vendedor não mexe no cadastro de vendedores, de empresas nem nas
     * comissões — inclusive na própria. A tela já esconde os botões; isto
     * aqui é o que vale, porque esconder não é impedir.
     */
    private function exigirGestor(): void
    {
        if ($this->travaVendedor() > 0) {
            Flash::erro('O cadastro de vendedores e as comissões ficam com a administração.');
            $this->redirect('lista');
        }
    }

    // ── Propostas ─────────────────────────────────────────────────────────

    public function lista(): void
    {
        Auth::requirePermissao('vendas', 'pode_ver');

        $trava = $this->travaVendedor();

        $filtro = [
            'busca'       => trim((string) ($_GET['q'] ?? '')),
            'vendedor_id' => $trava ?: (int) ($_GET['vendedor'] ?? 0),
            'empresa_id'  => $trava ? 0 : (int) ($_GET['empresa'] ?? 0),
            'status'      => (string) ($_GET['status'] ?? ''),
            'de'          => $this->data((string) ($_GET['de'] ?? '')),
            'ate'         => $this->data((string) ($_GET['ate'] ?? '')),
        ];

        $porPagina = Paginacao::porPagina(50);
        $total     = $this->model->contar($filtro);
        $paginas   = max(1, (int) ceil($total / $porPagina));
        $pagina    = min(max(1, (int) ($_GET['pagina'] ?? 1)), $paginas);

        View::render('vendas', 'lista', [
            'propostas'  => $this->model->listar($filtro, $porPagina, ($pagina - 1) * $porPagina),
            'panorama'   => $this->model->panorama($filtro),
            'vendedores' => $this->model->vendedores(),
            'empresas'   => $this->model->empresas(false),
            'filtro'     => $filtro,
            'trava'      => $trava,
            'meu'        => $this->meu,
            'total'      => $total,
            'pagina'     => $pagina,
            'porPagina'  => $porPagina,
        ]);
    }

    public function ver(): void
    {
        Auth::requirePermissao('vendas', 'pode_ver');

        $p = $this->model->propostaPorId($this->id());

        if (!$p || !$this->podeVer($p)) {
            Flash::erro('Proposta não encontrada.');
            $this->redirect();
        }

        View::render('vendas', 'ver', [
            'p'        => $p,
            'comissao' => $this->model->comissao($p),
            'pessoas'  => $this->model->pessoas((int) $p['id']),
        ]);
    }

    public function novo(): void
    {
        Auth::requirePermissao('vendas', 'pode_criar');

        if (!$this->model->vendedoresParaEscolha()) {
            Flash::erro('Cadastre a empresa parceira e o vendedor antes da primeira proposta.');
            $this->redirect('empresas');
        }

        $this->formulario([
            'id'            => 0,
            'vendedor_id'   => $this->meu ? (int) $this->meu['id'] : 0,
            'tipo'          => 'com_copart',
            'vidas'         => 1,
            'data_proposta' => date('Y-m-d'),
        ]);
    }

    public function editar(): void
    {
        Auth::requirePermissao('vendas', 'pode_editar');

        $p = $this->model->propostaPorId($this->id());

        if (!$p || !$this->podeVer($p)) {
            Flash::erro('Proposta não encontrada.');
            $this->redirect();
        }

        // Proposta fechada já virou contrato: o que mudar aqui não mudaria
        // lá, e o cadastro da família é do Cadastro daqui em diante.
        if ($p['status'] === 'fechada') {
            Flash::aviso('Esta proposta já virou contrato. Os dados da família se'
                . ' corrigem no Cadastro, não aqui.');
            $this->redirect('ver', ['id' => (int) $p['id']]);
        }

        $this->formulario($p);
    }

    public function salvar(): void
    {
        Auth::requirePermissao('vendas', $this->id() > 0 ? 'pode_editar' : 'pode_criar');
        $this->exigirPost();

        $id = $this->id();
        $d  = $this->doPost();

        // Vendedor com login não escolhe vendedor: a venda é dele.
        $trava = $this->travaVendedor();
        if ($trava) {
            $d['vendedor_id'] = $trava;
        }

        $erros = $this->validar($d, $id);

        if ($erros) {
            $this->formulario($d + ['id' => $id], $erros);
            return;
        }

        $novoId = $this->model->salvarProposta($id, $d);
        $p = $this->model->propostaPorId($novoId);

        $this->auditar($id > 0 ? Audit::EDITOU : Audit::CRIOU, sprintf(
            'Proposta %s — %s (vendedor: %s)',
            $p['numero'] ?? '?', $d['titular_nome'], $p['vendedor'] ?? '?'
        ), $novoId);

        Flash::sucesso($id > 0
            ? 'Proposta atualizada.'
            : 'Proposta ' . ($p['numero'] ?? '') . ' registrada.');

        $this->redirect('ver', ['id' => $novoId]);
    }

    /** A venda vira contrato. */
    public function fechar(): void
    {
        Auth::requirePermissao('vendas', 'pode_editar');
        $this->exigirPost();

        $id = $this->id();
        $p  = $this->model->propostaPorId($id);

        if (!$p || !$this->podeVer($p)) {
            Flash::erro('Proposta não encontrada.');
            $this->redirect();
        }

        if ($p['status'] === 'fechada') {
            Flash::aviso('Esta proposta já estava fechada.');
            $this->redirect('ver', ['id' => $id]);
        }

        if (($p['titular_categoria'] ?? '') === '') {
            Flash::erro('Antes de fechar, informe a categoria do titular — é ela que'
                . ' decide se ele pode ser titular do plano.');
            $this->redirect('editar', ['id' => $id]);
        }

        $data = $this->data((string) ($_POST['data_fechamento'] ?? '')) ?: date('Y-m-d');

        try {
            $feito = $this->model->fechar($id, $data);
        } catch (Throwable $e) {
            error_log('Falha ao fechar proposta ' . $id . ': ' . $e->getMessage());
            Flash::erro('Não consegui criar o contrato desta venda. Nada foi gravado pela metade —'
                . ' tente de novo e, se repetir, avise quem cuida do sistema.');
            $this->redirect('ver', ['id' => $id]);
        }

        $this->auditar(Audit::EDITOU, sprintf(
            'Proposta %s fechada — contrato #%d (vendedor: %s)',
            $p['numero'], $feito['contrato_id'], $p['vendedor']
        ), $id);

        Flash::sucesso($feito['dependentes'] > 0
            ? sprintf('Venda fechada. O contrato foi criado no Cadastro com o titular e'
                      . ' %d dependente%s.', $feito['dependentes'],
                      $feito['dependentes'] === 1 ? '' : 's')
            : 'Venda fechada. O contrato foi criado no Cadastro com o titular;'
              . ' os dependentes entram por lá.');

        $this->redirect('ver', ['id' => $id]);
    }

    public function perder(): void
    {
        Auth::requirePermissao('vendas', 'pode_editar');
        $this->exigirPost();

        $id = $this->id();
        $p  = $this->model->propostaPorId($id);

        if (!$p || !$this->podeVer($p)) {
            Flash::erro('Proposta não encontrada.');
            $this->redirect();
        }

        if ($p['status'] === 'fechada') {
            Flash::erro('Proposta fechada não vira perdida — o contrato já existe. Para'
                . ' encerrar, mude a situação do contrato no Cadastro.');
            $this->redirect('ver', ['id' => $id]);
        }

        $motivo = mb_substr(trim((string) ($_POST['motivo_perda'] ?? '')), 0, 200);

        $this->model->perder($id, $motivo);
        $this->auditar(Audit::EDITOU, sprintf('Proposta %s perdida%s', $p['numero'],
            $motivo !== '' ? ' — ' . $motivo : ''), $id);

        Flash::sucesso('Proposta marcada como perdida.');
        $this->redirect('ver', ['id' => $id]);
    }

    public function reabrir(): void
    {
        Auth::requirePermissao('vendas', 'pode_editar');
        $this->exigirPost();

        $id = $this->id();
        $p  = $this->model->propostaPorId($id);

        if (!$p || !$this->podeVer($p)) {
            Flash::erro('Proposta não encontrada.');
            $this->redirect();
        }

        $this->model->reabrir($id);
        $this->auditar(Audit::EDITOU, 'Proposta ' . $p['numero'] . ' reaberta', $id);

        Flash::sucesso('Proposta de volta para negociação.');
        $this->redirect('ver', ['id' => $id]);
    }

    public function excluir(): void
    {
        Auth::requirePermissao('vendas', 'pode_excluir');
        $this->exigirPost();

        $id = $this->id();
        $p  = $this->model->propostaPorId($id);

        if (!$p || !$this->podeVer($p)) {
            Flash::erro('Proposta não encontrada.');
            $this->redirect();
        }

        // Venda fechada é histórico de comissão e a origem de um contrato.
        // Apagar isso desfaz o rastro de quem vendeu o quê.
        if ($p['status'] === 'fechada') {
            Flash::erro('Proposta fechada não pode ser excluída — ela é a origem do contrato'
                . ' e o histórico da comissão. Se a venda caiu, cancele o contrato no Cadastro.');
            $this->redirect('ver', ['id' => $id]);
        }

        $this->model->excluirProposta($id);
        $this->auditar(Audit::EXCLUIU, sprintf('Proposta %s — %s', $p['numero'], $p['titular_nome']), $id);

        Flash::sucesso('Proposta excluída.');
        $this->redirect();
    }

    // ── As vidas da família ───────────────────────────────────────────────

    /** O formulário de uma vida da família. */
    public function pessoa(): void
    {
        Auth::requirePermissao('vendas', 'pode_criar');

        $id = (int) ($_GET['id'] ?? 0);

        if ($id > 0) {
            $pessoa = $this->model->pessoaPorId($id);

            if (!$pessoa || !$this->podeVer(['vendedor_id' => $pessoa['vendedor_id']])) {
                Flash::erro('Pessoa não encontrada.');
                $this->redirect();
            }

            $this->formPessoa($pessoa, (int) $pessoa['proposta_id']);
            return;
        }

        $propostaId = (int) ($_GET['proposta'] ?? 0);
        $proposta   = $this->model->propostaPorId($propostaId);

        if (!$proposta || !$this->podeVer($proposta)) {
            Flash::erro('Proposta não encontrada.');
            $this->redirect();
        }

        $this->formPessoa(['id' => 0, 'proposta_id' => $propostaId], $propostaId);
    }

    public function salvar_pessoa(): void
    {
        Auth::requirePermissao('vendas', 'pode_editar');
        $this->exigirPost();

        $id         = $this->id();
        $propostaId = (int) ($_POST['proposta_id'] ?? 0);
        $proposta   = $this->model->propostaPorId($propostaId);

        if (!$proposta || !$this->podeVer($proposta)) {
            Flash::erro('Proposta não encontrada.');
            $this->redirect();
        }

        // Fechada, a família já é do Cadastro: mexer aqui não mexeria lá.
        if ($proposta['status'] === 'fechada') {
            Flash::aviso('Esta proposta já virou contrato. Inclua ou corrija as pessoas'
                . ' no Cadastro.');
            $this->redirect('ver', ['id' => $propostaId]);
        }

        $d = [
            'proposta_id' => $propostaId,
            'nome'        => trim((string) ($_POST['nome'] ?? '')),
            'cpf'         => preg_replace('/\D/', '', (string) ($_POST['cpf'] ?? '')) ?? '',
            'nascimento'  => $this->data((string) ($_POST['nascimento'] ?? '')),
            'parentesco'  => (string) ($_POST['parentesco'] ?? ''),
            'observacao'  => trim((string) ($_POST['observacao'] ?? '')),
        ];

        foreach (array_keys(Elegibilidade::CONDICOES) as $condicao) {
            $d[$condicao] = isset($_POST[$condicao]) ? 1 : 0;
        }

        $erros = [];

        if ($d['nome'] === '') {
            $erros['nome'] = 'Informe o nome da pessoa.';
        }

        if ($d['cpf'] !== '' && !cpf_valido($d['cpf'])) {
            $erros['cpf'] = 'CPF inválido. Confira os números ou deixe em branco.';
        }

        if ($d['parentesco'] === '') {
            $erros['parentesco'] = 'Informe o parentesco com o titular — é ele que define'
                . ' em qual plano a pessoa entra.';
        } elseif (!isset(Elegibilidade::parentescos()[$d['parentesco']])) {
            $erros['parentesco'] = 'Parentesco inválido.';
        }

        if ($erros) {
            $this->formPessoa($d + ['id' => $id], $propostaId, $erros);
            return;
        }

        $novoId = $this->model->salvarPessoa($id, $d);

        $this->auditar($id > 0 ? Audit::EDITOU : Audit::CRIOU, sprintf(
            'Proposta %s — %s (%s)', $proposta['numero'], $d['nome'],
            Elegibilidade::rotuloParentesco($d['parentesco'])
        ), $novoId);

        // A avaliação é a mesma do Cadastro: dizer agora o que a regra indica
        // evita a surpresa de a mensalidade sair diferente do que foi vendido.
        $aval = Elegibilidade::avaliar($d + ['tipo' => 'dependente'], $proposta['tipo']);

        if (!$aval['elegivel']) {
            Flash::aviso($aval['impedimento'] . ' Gravei assim mesmo — confira antes de fechar.');
        } elseif ($aval['pendencias']) {
            Flash::aviso('Falta comprovar: ' . implode('; ', $aval['pendencias']) . '.');
        }

        Flash::sucesso($id > 0 ? 'Pessoa atualizada.' : $d['nome'] . ' incluída na proposta.');
        $this->redirect('ver', ['id' => $propostaId]);
    }

    public function excluir_pessoa(): void
    {
        Auth::requirePermissao('vendas', 'pode_editar');
        $this->exigirPost();

        $id     = $this->id();
        $pessoa = $this->model->pessoaPorId($id);

        if (!$pessoa || !$this->podeVer(['vendedor_id' => $pessoa['vendedor_id']])) {
            Flash::erro('Pessoa não encontrada.');
            $this->redirect();
        }

        if ($pessoa['proposta_status'] === 'fechada') {
            Flash::erro('Esta proposta já virou contrato. Para tirar alguém do plano,'
                . ' use o Cadastro.');
            $this->redirect('ver', ['id' => (int) $pessoa['proposta_id']]);
        }

        $this->model->excluirPessoa($id);
        $this->auditar(Audit::EXCLUIU, sprintf('Proposta %s — %s', $pessoa['numero'], $pessoa['nome']), $id);

        Flash::sucesso('Pessoa removida da proposta.');
        $this->redirect('ver', ['id' => (int) $pessoa['proposta_id']]);
    }

    // ── Desempenho por vendedor ───────────────────────────────────────────

    public function painel(): void
    {
        Auth::requirePermissao('vendas', 'pode_ver');

        $trava = $this->travaVendedor();

        $filtro = [
            'vendedor_id' => $trava,
            'de'          => $this->data((string) ($_GET['de'] ?? '')),
            'ate'         => $this->data((string) ($_GET['ate'] ?? '')),
        ];

        View::render('vendas', 'painel', [
            'linhas' => $this->model->desempenho($filtro),
            'filtro' => $filtro,
            'trava'  => $trava,
        ]);
    }

    // ── Vendedores ────────────────────────────────────────────────────────

    public function vendedores(): void
    {
        Auth::requirePermissao('vendas', 'pode_ver');
        $this->exigirGestor();

        View::render('vendas', 'vendedores', [
            'vendedores' => $this->model->vendedores(),
            'empresas'   => $this->model->empresas(false),
            'usuarios'   => $this->model->usuarios(),
            'erros'      => $this->errosGuardados(),
            'editando'   => $this->model->vendedorPorId((int) ($_GET['id'] ?? 0)) ?: null,
        ]);
    }

    public function salvar_vendedor(): void
    {
        Auth::requirePermissao('vendas', 'pode_editar');
        $this->exigirGestor();
        $this->exigirPost();

        $id = $this->id();
        $d  = [
            'empresa_id' => (int) ($_POST['empresa_id'] ?? 0),
            'nome'       => trim((string) ($_POST['nome'] ?? '')),
            'cpf'        => preg_replace('/\D/', '', (string) ($_POST['cpf'] ?? '')) ?? '',
            'contato'    => preg_replace('/\D/', '', (string) ($_POST['contato'] ?? '')) ?? '',
            'email'      => trim((string) ($_POST['email'] ?? '')),
            'comissao_percent' => $this->numero((string) ($_POST['comissao_percent'] ?? '')) ?? 0.0,
            'usuario_id' => (int) ($_POST['usuario_id'] ?? 0),
            'ativo'      => isset($_POST['ativo']) ? 1 : 0,
            'observacao' => trim((string) ($_POST['observacao'] ?? '')),
        ];

        $erros = [];

        if ($d['nome'] === '') {
            $erros['nome'] = 'Informe o nome do vendedor.';
        }

        if ($d['empresa_id'] <= 0) {
            $erros['empresa_id'] = 'Escolha a empresa parceira.';
        }

        if ($d['cpf'] !== '' && !cpf_valido($d['cpf'])) {
            $erros['cpf'] = 'CPF inválido. Confira os números ou deixe em branco.';
        } elseif ($d['cpf'] !== '' && $d['empresa_id'] > 0
                  && $this->model->cpfRepetido($d['empresa_id'], $d['cpf'], $id) > 0) {
            $erros['cpf'] = 'Este CPF já está cadastrado nesta empresa.';
        }

        if ($d['email'] !== '' && !filter_var($d['email'], FILTER_VALIDATE_EMAIL)) {
            $erros['email'] = 'E-mail inválido.';
        }

        if ($d['comissao_percent'] < 0 || $d['comissao_percent'] > 100) {
            $erros['comissao_percent'] = 'A comissão é um percentual entre 0 e 100.';
        }

        // Um login só pode responder por um vendedor: dois vínculos fariam a
        // trava de visibilidade escolher o errado.
        if ($d['usuario_id'] > 0 && $this->model->usuarioJaVendedor($d['usuario_id'], $id) > 0) {
            $erros['usuario_id'] = 'Este usuário já está ligado a outro vendedor.';
        }

        if ($erros) {
            $_SESSION['vd_erros'] = $erros;
            $this->redirect('vendedores', $id > 0 ? ['id' => $id] : []);
        }

        $novoId = $this->model->salvarVendedor($id, $d);
        $this->auditar($id > 0 ? Audit::EDITOU : Audit::CRIOU, 'Vendedor: ' . $d['nome'], $novoId);

        Flash::sucesso($id > 0 ? 'Vendedor atualizado.' : 'Vendedor cadastrado.');
        $this->redirect('vendedores');
    }

    /** O mesmo percentual para todo mundo, de uma vez. */
    public function comissao_todos(): void
    {
        Auth::requirePermissao('vendas', 'pode_editar');
        $this->exigirGestor();
        $this->exigirPost();

        $percent = $this->numero((string) ($_POST['comissao_percent'] ?? ''));
        $inativos = !empty($_POST['incluir_inativos']);

        if ($percent === null || $percent < 0 || $percent > 100) {
            Flash::erro('Informe um percentual entre 0 e 100.');
            $this->redirect('vendedores');
        }

        $mudaram = $this->model->comissaoParaTodos($percent, $inativos);

        $this->auditar(Audit::EDITOU, sprintf(
            'Comissão de %s%% aplicada a %d vendedor%s%s',
            number_format($percent, 2, ',', ''), $mudaram, $mudaram === 1 ? '' : 'es',
            $inativos ? ' (inclusive inativos)' : ''
        ), null);

        Flash::sucesso($mudaram === 0
            ? 'Todos já estavam com esse percentual.'
            : sprintf('%d vendedor%s passaram a ter %s%% de comissão. As propostas já'
                      . ' fechadas não mudam — o percentual delas foi congelado no fechamento.',
                      $mudaram, $mudaram === 1 ? '' : 'es',
                      number_format($percent, 2, ',', '')));

        $this->redirect('vendedores');
    }

    public function excluir_vendedor(): void
    {
        Auth::requirePermissao('vendas', 'pode_excluir');
        $this->exigirGestor();
        $this->exigirPost();

        $id = $this->id();
        $v  = $this->model->vendedorPorId($id);

        if (!$v) {
            Flash::erro('Vendedor não encontrado.');
            $this->redirect('vendedores');
        }

        $propostas = $this->model->propostasDe($id);

        if ($propostas > 0) {
            Flash::erro(sprintf(
                '%s tem %d proposta%s registrada%s e não pode ser excluído — é o histórico'
                . ' de quem vendeu o quê. Desmarque "Ativo" para tirar das listas.',
                $v['nome'], $propostas, $propostas === 1 ? '' : 's', $propostas === 1 ? '' : 's'
            ));
            $this->redirect('vendedores');
        }

        $this->model->excluirVendedor($id);
        $this->auditar(Audit::EXCLUIU, 'Vendedor: ' . $v['nome'], $id);

        Flash::sucesso('Vendedor excluído.');
        $this->redirect('vendedores');
    }

    // ── Empresas parceiras ────────────────────────────────────────────────

    public function empresas(): void
    {
        Auth::requirePermissao('vendas', 'pode_ver');
        $this->exigirGestor();

        View::render('vendas', 'empresas', [
            'empresas' => $this->model->empresas(false),
            'erros'    => $this->errosGuardados(),
            'editando' => $this->model->empresaPorId((int) ($_GET['id'] ?? 0)) ?: null,
        ]);
    }

    public function salvar_empresa(): void
    {
        Auth::requirePermissao('vendas', 'pode_editar');
        $this->exigirGestor();
        $this->exigirPost();

        $id = $this->id();
        $d  = [
            'nome'       => trim((string) ($_POST['nome'] ?? '')),
            'cnpj'       => trim((string) ($_POST['cnpj'] ?? '')),
            'contato'    => preg_replace('/\D/', '', (string) ($_POST['contato'] ?? '')) ?? '',
            'email'      => trim((string) ($_POST['email'] ?? '')),
            'observacao' => trim((string) ($_POST['observacao'] ?? '')),
            'ativo'      => isset($_POST['ativo']) ? 1 : 0,
        ];

        $erros = [];

        if ($d['nome'] === '') {
            $erros['nome'] = 'Informe o nome da empresa.';
        } elseif ($this->model->nomeEmpresaExiste($d['nome'], $id)) {
            $erros['nome'] = 'Já existe uma empresa com esse nome.';
        }

        if ($d['contato'] !== '' && !in_array(strlen($d['contato']), [10, 11], true)) {
            $erros['contato'] = 'O telefone precisa ter 10 ou 11 dígitos, com o DDD.';
        }

        if ($d['email'] !== '' && !filter_var($d['email'], FILTER_VALIDATE_EMAIL)) {
            $erros['email'] = 'E-mail inválido.';
        }

        if ($erros) {
            $_SESSION['vd_erros'] = $erros;
            $this->redirect('empresas', $id > 0 ? ['id' => $id] : []);
        }

        $novoId = $this->model->salvarEmpresa($id, $d);
        $this->auditar($id > 0 ? Audit::EDITOU : Audit::CRIOU, 'Empresa parceira: ' . $d['nome'], $novoId);

        Flash::sucesso($id > 0 ? 'Empresa atualizada.' : 'Empresa cadastrada.');
        $this->redirect('empresas');
    }

    public function excluir_empresa(): void
    {
        Auth::requirePermissao('vendas', 'pode_excluir');
        $this->exigirGestor();
        $this->exigirPost();

        $id      = $this->id();
        $empresa = $this->model->empresaPorId($id);

        if (!$empresa) {
            Flash::erro('Empresa não encontrada.');
            $this->redirect('empresas');
        }

        $vendedores = 0;
        foreach ($this->model->empresas(false) as $e) {
            if ((int) $e['id'] === $id) {
                $vendedores = (int) $e['vendedores'];
            }
        }

        if ($vendedores > 0) {
            Flash::erro(sprintf(
                '%s tem %d vendedor%s cadastrado%s e não pode ser excluída. Desmarque'
                . ' "Ativa" para tirar das listas sem perder o histórico.',
                $empresa['nome'], $vendedores,
                $vendedores === 1 ? '' : 'es', $vendedores === 1 ? '' : 's'
            ));
            $this->redirect('empresas');
        }

        $this->model->excluirEmpresa($id);
        $this->auditar(Audit::EXCLUIU, 'Empresa parceira: ' . $empresa['nome'], $id);

        Flash::sucesso('Empresa excluída.');
        $this->redirect('empresas');
    }

    // ── Apoio ─────────────────────────────────────────────────────────────

    private function formulario(array $p, array $erros = []): void
    {
        View::render('vendas', 'form', [
            'p'          => $p,
            'erros'      => $erros,
            'vendedores' => $this->model->vendedoresParaEscolha(),
            'trava'      => $this->travaVendedor(),
        ]);
    }

    private function formPessoa(array $pessoa, int $propostaId, array $erros = []): void
    {
        $proposta = $this->model->propostaPorId($propostaId);

        View::render('vendas', 'pessoa', [
            'v'        => $pessoa,
            'p'        => $proposta,
            'erros'    => $erros,
        ]);
    }

    private function doPost(): array
    {
        return [
            'vendedor_id'       => (int) ($_POST['vendedor_id'] ?? 0),
            'titular_nome'      => trim((string) ($_POST['titular_nome'] ?? '')),
            'titular_cpf'       => preg_replace('/\D/', '', (string) ($_POST['titular_cpf'] ?? '')) ?? '',
            'titular_nascimento' => $this->data((string) ($_POST['titular_nascimento'] ?? '')),
            'titular_categoria' => (string) ($_POST['titular_categoria'] ?? ''),
            'contato'           => preg_replace('/\D/', '', (string) ($_POST['contato'] ?? '')) ?? '',
            'email'             => trim((string) ($_POST['email'] ?? '')),
            'tipo'              => (string) ($_POST['tipo'] ?? 'com_copart'),
            'vidas'             => (int) ($_POST['vidas'] ?? 1),
            'data_proposta'     => $this->data((string) ($_POST['data_proposta'] ?? '')),
            'observacao'        => trim((string) ($_POST['observacao'] ?? '')),
        ];
    }

    private function validar(array $d, int $id): array
    {
        $erros = [];

        if ($d['vendedor_id'] <= 0 || !$this->model->vendedorPorId($d['vendedor_id'])) {
            $erros['vendedor_id'] = 'Escolha o vendedor.';
        }

        if ($d['titular_nome'] === '') {
            $erros['titular_nome'] = 'Informe o nome de quem será titular.';
        }

        // CPF não é obrigatório na proposta — na primeira conversa nem sempre
        // se tem o documento —, mas o que vier tem de ser CPF de verdade.
        if ($d['titular_cpf'] !== '' && !cpf_valido($d['titular_cpf'])) {
            $erros['titular_cpf'] = 'CPF inválido. Confira os números ou deixe em branco.';
        }

        if ($d['titular_categoria'] !== ''
            && !isset(Elegibilidade::CATEGORIAS[$d['titular_categoria']])) {
            $erros['titular_categoria'] = 'Categoria inválida.';
        }

        if ($d['email'] !== '' && !filter_var($d['email'], FILTER_VALIDATE_EMAIL)) {
            $erros['email'] = 'E-mail inválido.';
        }

        if (!isset(Elegibilidade::TIPOS_CONTRATO[$d['tipo']])) {
            $erros['tipo'] = 'Tipo de contrato inválido.';
        }

        if ($d['vidas'] < 1 || $d['vidas'] > 30) {
            $erros['vidas'] = 'Quantidade de vidas fora do possível (1 a 30).';
        }

        if (!$d['data_proposta']) {
            $erros['data_proposta'] = 'Informe a data da proposta.';
        }

        return $erros;
    }

    /** "30" e "2,5" viram número; vazio vira nulo. */
    private function numero(string $texto): ?float
    {
        $texto = trim(str_replace(',', '.', $texto));
        return $texto === '' ? null : (float) $texto;
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
        $erros = (array) ($_SESSION['vd_erros'] ?? []);
        unset($_SESSION['vd_erros']);
        return $erros;
    }
}
