<?php
declare(strict_types=1);

require_once BASE_PATH . '/modules/ocupacional/Model.php';

/**
 * Saúde Ocupacional — NR-7 / PCMSO.
 *
 * Três telas de dado: empresas, trabalhadores e as fichas. A ficha é o
 * prontuário em si, e é a única que tem versão de impressão: é ela que vai
 * assinada para o arquivo de 20 anos.
 */
class OcupacionalController extends Controller
{
    protected string $modulo = 'ocupacional';

    private OcupacionalModel $model;

    public function __construct()
    {
        $this->model = new OcupacionalModel();
    }

    // ── Lista das fichas ──────────────────────────────────────────────────

    public function lista(): void
    {
        Auth::requirePermissao('ocupacional', 'pode_ver');

        $filtro = [
            'busca'      => trim((string) ($_GET['q'] ?? '')),
            'empresa_id' => (int) ($_GET['empresa'] ?? 0),
            'tipo'       => (string) ($_GET['tipo'] ?? ''),
            'conclusao'  => (string) ($_GET['conclusao'] ?? ''),
            'de'         => $this->data((string) ($_GET['de'] ?? '')),
            'ate'        => $this->data((string) ($_GET['ate'] ?? '')),
            'vencendo'   => !empty($_GET['vencendo']),
            'pendentes'  => !empty($_GET['pendentes']),
        ];

        $porPagina = Paginacao::porPagina(50);
        $total     = $this->model->contar($filtro);
        $paginas   = max(1, (int) ceil($total / $porPagina));
        $pagina    = min(max(1, (int) ($_GET['pagina'] ?? 1)), $paginas);

        View::render('ocupacional', 'lista', [
            'fichas'    => $this->model->listar($filtro, $porPagina, ($pagina - 1) * $porPagina),
            'total'     => $total,
            'pagina'    => $pagina,
            'porPagina' => $porPagina,
            'panorama'  => $this->model->panorama(),
            'empresas'  => $this->model->empresas(false),
            'filtro'    => $filtro,
        ]);
    }

    public function ver(): void
    {
        Auth::requirePermissao('ocupacional', 'pode_ver');

        $ficha = $this->model->buscarPorId($this->id());

        if (!$ficha) {
            Flash::erro('Ficha não encontrada.');
            $this->redirect();
        }

        View::render('ocupacional', 'ver', [
            'f'          => $ficha,
            'historico'  => $this->model->historico((int) $ficha['trabalhador_id']),
        ]);
    }

    /**
     * A ficha em papel, no mesmo desenho do modelo impresso.
     *
     * Sem a casca do sistema: o que sai da impressora é só o formulário, com
     * as linhas de assinatura no fim.
     */
    public function imprimir(): void
    {
        Auth::requirePermissao('ocupacional', 'pode_ver');

        $ficha = $this->model->buscarPorId($this->id());

        if (!$ficha) {
            Flash::erro('Ficha não encontrada.');
            $this->redirect();
        }

        // Ver o prontuário de alguém é acesso a dado de saúde. A impressão
        // fica registrada porque é o momento em que o dado sai do sistema.
        $this->auditar(Audit::IMPRIMIU,
            sprintf('%s — %s em %s', $ficha['trabalhador'],
                OcupacionalModel::TIPOS[$ficha['tipo']] ?? $ficha['tipo'],
                data_br($ficha['data_exame'])),
            (int) $ficha['id']);

        View::render('ocupacional', 'imprimir', ['f' => $ficha], false);
    }

    // ── Formulário da ficha ───────────────────────────────────────────────

    public function novo(): void
    {
        Auth::requirePermissao('ocupacional', 'pode_criar');

        $this->formulario([
            'id'              => 0,
            'trabalhador_id'  => (int) ($_GET['trabalhador'] ?? 0),
            'tipo'            => 'admissional',
            'data_exame'      => date('Y-m-d'),
            'conclusao'       => 'pendente',
            // Quem está preenchendo é quem está logado. Pré-preenchido, e
            // editável: às vezes quem digita não é quem examinou.
            'enfermeiro_nome' => Auth::nome(),
        ]);
    }

    public function editar(): void
    {
        Auth::requirePermissao('ocupacional', 'pode_editar');

        $ficha = $this->model->buscarPorId($this->id());

        if (!$ficha) {
            Flash::erro('Ficha não encontrada.');
            $this->redirect();
        }

        $this->formulario($ficha);
    }

    public function salvar(): void
    {
        Auth::requirePermissao('ocupacional', $this->id() > 0 ? 'pode_editar' : 'pode_criar');
        $this->exigirPost();

        $id    = $this->id();
        $dados = $this->doPost();
        $erros = $this->validar($dados);

        if ($erros) {
            $this->formulario($dados + ['id' => $id], $erros);
            return;
        }

        if ($id > 0) {
            $this->model->atualizar($id, $dados);
            Flash::sucesso('Ficha atualizada.');
        } else {
            $id = $this->model->criar($dados);
            Flash::sucesso('Ficha registrada.');
        }

        $trabalhador = $this->model->trabalhadorPorId((int) $dados['trabalhador_id']);

        $this->auditar($this->id() > 0 ? Audit::EDITOU : Audit::CRIOU, sprintf(
            '%s — %s em %s',
            $trabalhador['nome'] ?? '?',
            OcupacionalModel::TIPOS[$dados['tipo']] ?? $dados['tipo'],
            data_br($dados['data_exame'])
        ), $id);

        $this->avisos($dados, (array) $trabalhador);
        $this->redirect('ver', ['id' => $id]);
    }

    public function excluir(): void
    {
        Auth::requirePermissao('ocupacional', 'pode_excluir');
        $this->exigirPost();

        $id    = $this->id();
        $ficha = $this->model->buscarPorId($id);

        if (!$ficha) {
            Flash::erro('Ficha não encontrada.');
            $this->redirect();
        }

        $this->model->excluir($id);

        $this->auditar(Audit::EXCLUIU, sprintf(
            '%s — %s em %s',
            $ficha['trabalhador'],
            OcupacionalModel::TIPOS[$ficha['tipo']] ?? $ficha['tipo'],
            data_br($ficha['data_exame'])
        ), $id);

        Flash::sucesso('Ficha excluída.');
        $this->redirect();
    }

    // ── Entrega de atestado médico ────────────────────────────────────────

    public function atestados(): void
    {
        Auth::requirePermissao('ocupacional', 'pode_ver');

        $filtro = [
            'busca'           => trim((string) ($_GET['q'] ?? '')),
            'empresa_id'      => (int) ($_GET['empresa'] ?? 0),
            'forma'           => (string) ($_GET['forma'] ?? ''),
            'de'              => $this->data((string) ($_GET['de'] ?? '')),
            'ate'             => $this->data((string) ($_GET['ate'] ?? '')),
            'sem_recebimento' => !empty($_GET['sem_recebimento']),
            'afastados'       => !empty($_GET['afastados']),
        ];

        $porPagina = Paginacao::porPagina(50);
        $total     = $this->model->contarAtestados($filtro);
        $paginas   = max(1, (int) ceil($total / $porPagina));
        $pagina    = min(max(1, (int) ($_GET['pagina'] ?? 1)), $paginas);

        View::render('ocupacional', 'atestados', [
            'atestados' => $this->model->listarAtestados($filtro, $porPagina, ($pagina - 1) * $porPagina),
            'total'     => $total,
            'pagina'    => $pagina,
            'porPagina' => $porPagina,
            'panorama'  => $this->model->panoramaAtestados(),
            'empresas'  => $this->model->empresas(false),
            'filtro'    => $filtro,
        ]);
    }

    public function ver_atestado(): void
    {
        Auth::requirePermissao('ocupacional', 'pode_ver');

        $a = $this->model->atestadoPorId($this->id());

        if (!$a) {
            Flash::erro('Entrega de atestado não encontrada.');
            $this->redirect('atestados');
        }

        View::render('ocupacional', 'ver_atestado', [
            'a'         => $a,
            'anteriores' => $this->model->atestadosDe((int) $a['trabalhador_id']),
        ]);
    }

    public function novo_atestado(): void
    {
        Auth::requirePermissao('ocupacional', 'pode_criar');

        $this->formularioAtestado([
            'id'             => 0,
            'trabalhador_id' => (int) ($_GET['trabalhador'] ?? 0),
            'emissao'        => date('Y-m-d'),
            'entrega_data'   => date('Y-m-d'),
            'entrega_hora'   => date('H:i'),
            'forma'          => 'presencial',
            // Quem está no balcão recebendo é quem está logado. Preenchido,
            // e editável: às vezes quem digita não é quem recebeu o papel.
            'recebido_por'   => Auth::nome(),
            'recebido_data'  => date('Y-m-d'),
            'recebido_hora'  => date('H:i'),
        ]);
    }

    public function editar_atestado(): void
    {
        Auth::requirePermissao('ocupacional', 'pode_editar');

        $a = $this->model->atestadoPorId($this->id());

        if (!$a) {
            Flash::erro('Entrega de atestado não encontrada.');
            $this->redirect('atestados');
        }

        $this->formularioAtestado($a);
    }

    public function salvar_atestado(): void
    {
        Auth::requirePermissao('ocupacional', $this->id() > 0 ? 'pode_editar' : 'pode_criar');
        $this->exigirPost();

        $id = $this->id();
        $d  = [
            'trabalhador_id'  => (int) ($_POST['trabalhador_id'] ?? 0),
            'emissao'         => $this->data((string) ($_POST['emissao'] ?? '')),
            'inicio'          => $this->data((string) ($_POST['inicio'] ?? '')),
            'dias'            => (int) ($_POST['dias'] ?? 0),
            'retorno'         => $this->data((string) ($_POST['retorno'] ?? '')),
            'medico_nome'     => trim((string) ($_POST['medico_nome'] ?? '')),
            'medico_registro' => trim((string) ($_POST['medico_registro'] ?? '')),

            'entrega_data' => $this->data((string) ($_POST['entrega_data'] ?? '')),
            'entrega_hora' => $this->hora((string) ($_POST['entrega_hora'] ?? '')),
            'forma'        => (string) ($_POST['forma'] ?? 'presencial'),
            'forma_outro'  => trim((string) ($_POST['forma_outro'] ?? '')),

            'recebido_por'  => trim((string) ($_POST['recebido_por'] ?? '')),
            'recebido_data' => $this->data((string) ($_POST['recebido_data'] ?? '')),
            'recebido_hora' => $this->hora((string) ($_POST['recebido_hora'] ?? '')),

            'observacao' => trim((string) ($_POST['observacao'] ?? '')),
        ];

        // Retorno em branco com início e dias preenchidos: o sistema calcula.
        // É conta de papel — voltar no dia seguinte ao último dia coberto.
        if (!$d['retorno'] && $d['inicio'] && $d['dias'] > 0) {
            $d['retorno'] = date('Y-m-d', strtotime($d['inicio'] . ' +' . $d['dias'] . ' day'));
        }

        $erros = $this->validarAtestado($d);

        if ($erros) {
            $this->formularioAtestado($d + ['id' => $id], $erros);
            return;
        }

        $novoId = $this->model->salvarAtestado($id, $d);
        $a      = $this->model->atestadoPorId($novoId);

        $this->auditar($id > 0 ? Audit::EDITOU : Audit::CRIOU, sprintf(
            'Atestado %s — %s, entrega em %s',
            $a['protocolo'] ?? '?', $a['trabalhador'] ?? '?', data_br($d['entrega_data'])
        ), $novoId);

        $this->avisosAtestado($d);

        Flash::sucesso($id > 0
            ? 'Entrega atualizada.'
            : 'Entrega registrada com o protocolo ' . ($a['protocolo'] ?? '') . '.');

        $this->redirect('ver_atestado', ['id' => $novoId]);
    }

    /**
     * O comprovante em papel: entrega, recebimento e a 2ª via do colaborador,
     * na mesma folha, como no modelo.
     */
    public function imprimir_atestado(): void
    {
        Auth::requirePermissao('ocupacional', 'pode_ver');

        $a = $this->model->atestadoPorId($this->id());

        if (!$a) {
            Flash::erro('Entrega de atestado não encontrada.');
            $this->redirect('atestados');
        }

        // Aqui não há diagnóstico, mas há afastamento e nome — dado de saúde
        // do mesmo jeito. A impressão é o momento em que ele sai do sistema.
        $this->auditar(Audit::IMPRIMIU,
            sprintf('Atestado %s — %s', $a['protocolo'], $a['trabalhador']),
            (int) $a['id']);

        View::render('ocupacional', 'imprimir_atestado', ['a' => $a], false);
    }

    public function excluir_atestado(): void
    {
        Auth::requirePermissao('ocupacional', 'pode_excluir');
        $this->exigirPost();

        $id = $this->id();
        $a  = $this->model->atestadoPorId($id);

        if (!$a) {
            Flash::erro('Entrega de atestado não encontrada.');
            $this->redirect('atestados');
        }

        $this->model->excluirAtestado($id);

        $this->auditar(Audit::EXCLUIU, sprintf(
            'Atestado %s — %s, entrega em %s',
            $a['protocolo'], $a['trabalhador'], data_br($a['entrega_data'])
        ), $id);

        // O número não é reaproveitado: ele já pode estar impresso na mão do
        // colaborador, e dois papéis com o mesmo protocolo é o que o
        // comprovante existe para evitar.
        Flash::sucesso('Entrega excluída. O protocolo ' . $a['protocolo'] . ' não será reutilizado.');
        $this->redirect('atestados');
    }

    // ── Trabalhadores ─────────────────────────────────────────────────────

    public function trabalhadores(): void
    {
        Auth::requirePermissao('ocupacional', 'pode_ver');

        $filtro = [
            'busca'      => trim((string) ($_GET['q'] ?? '')),
            'empresa_id' => (int) ($_GET['empresa'] ?? 0),
            'so_ativos'  => !empty($_GET['ativos']),
        ];

        View::render('ocupacional', 'trabalhadores', [
            'trabalhadores' => $this->model->trabalhadores($filtro),
            'empresas'      => $this->model->empresas(false),
            'filtro'        => $filtro,
            'erros'         => $this->errosGuardados(),
            'editando'      => $this->model->trabalhadorPorId((int) ($_GET['id'] ?? 0)) ?: null,
        ]);
    }

    public function salvar_trabalhador(): void
    {
        Auth::requirePermissao('ocupacional', 'pode_editar');
        $this->exigirPost();

        $id = $this->id();
        $d  = [
            'empresa_id' => (int) ($_POST['empresa_id'] ?? 0),
            'nome'       => trim((string) ($_POST['nome'] ?? '')),
            // CPF só com dígitos, como no cadastro de usuários: a pontuação
            // é da tela, não do dado.
            'cpf'        => preg_replace('/\D/', '', (string) ($_POST['cpf'] ?? '')) ?? '',
            'matricula'  => trim((string) ($_POST['matricula'] ?? '')),
            'nascimento' => $this->data((string) ($_POST['nascimento'] ?? '')),
            'funcao'     => trim((string) ($_POST['funcao'] ?? '')),
            'setor'      => trim((string) ($_POST['setor'] ?? '')),
            'admissao'   => $this->data((string) ($_POST['admissao'] ?? '')),
            'ativo'      => isset($_POST['ativo']) ? 1 : 0,
            'observacao' => trim((string) ($_POST['observacao'] ?? '')),
        ];

        $erros = [];

        if ($d['nome'] === '') {
            $erros['nome'] = 'Informe o nome do trabalhador.';
        } elseif (mb_strlen($d['nome']) > 150) {
            $erros['nome'] = 'O nome passa de 150 caracteres.';
        }

        if ($d['empresa_id'] <= 0) {
            $erros['empresa_id'] = 'Escolha a empresa.';
        }

        // CPF não é obrigatório — o admissional às vezes acontece antes de a
        // papelada chegar — mas o que vier tem de ser um CPF de verdade.
        if ($d['cpf'] !== '' && !cpf_valido($d['cpf'])) {
            $erros['cpf'] = 'CPF inválido. Confira os números ou deixe em branco.';
        } elseif ($d['cpf'] !== '' && $d['empresa_id'] > 0) {
            $jaExiste = $this->model->cpfRepetido($d['empresa_id'], $d['cpf'], $id);
            if ($jaExiste > 0) {
                $erros['cpf'] = 'Este CPF já está cadastrado nesta empresa.';
                $erros['_link'] = url('ocupacional', 'ver_trabalhador', ['id' => $jaExiste]);
            }
        }

        if ($erros) {
            $_SESSION['ocup_erros'] = $erros;
            $this->redirect('trabalhadores', $id > 0 ? ['id' => $id] : []);
        }

        $novoId = $this->model->salvarTrabalhador($id, $d);

        $this->auditar($id > 0 ? Audit::EDITOU : Audit::CRIOU,
            'Trabalhador: ' . $d['nome'], $novoId);

        Flash::sucesso($id > 0 ? 'Trabalhador atualizado.' : 'Trabalhador cadastrado.');
        $this->redirect('ver_trabalhador', ['id' => $novoId]);
    }

    /** A pessoa por dentro: o cadastro e todas as fichas dela. */
    public function ver_trabalhador(): void
    {
        Auth::requirePermissao('ocupacional', 'pode_ver');

        $t = $this->model->trabalhadorPorId($this->id());

        if (!$t) {
            Flash::erro('Trabalhador não encontrado.');
            $this->redirect('trabalhadores');
        }

        View::render('ocupacional', 'ver_trabalhador', [
            't'         => $t,
            'historico' => $this->model->historico((int) $t['id']),
        ]);
    }

    public function excluir_trabalhador(): void
    {
        Auth::requirePermissao('ocupacional', 'pode_excluir');
        $this->exigirPost();

        $id = $this->id();
        $t  = $this->model->trabalhadorPorId($id);

        if (!$t) {
            Flash::erro('Trabalhador não encontrado.');
            $this->redirect('trabalhadores');
        }

        // Prontuário tem prazo legal de 20 anos. Apagar o cadastro levando as
        // fichas junto seria destruir documento que a NR-7 manda guardar —
        // por isso a recusa é explícita, e diz quantas fichas existem.
        $fichas    = $this->model->fichasDe($id);
        $atestados = $this->model->atestadosContagem($id);

        if ($atestados > 0 && $fichas === 0) {
            Flash::erro(sprintf(
                '%s tem %d entrega%s de atestado registrada%s e não pode ser excluído —'
                . ' o comprovante de entrega é documento. Para tirar de circulação,'
                . ' desmarque "Ativo".',
                $t['nome'], $atestados, $atestados === 1 ? '' : 's', $atestados === 1 ? '' : 's'
            ));
            $this->redirect('ver_trabalhador', ['id' => $id]);
        }

        if ($fichas > 0) {
            Flash::erro(sprintf(
                '%s tem %d ficha%s de saúde ocupacional e não pode ser excluído — o prontuário'
                . ' precisa ser guardado por 20 anos. Para tirar de circulação, desmarque "Ativo".',
                $t['nome'], $fichas, $fichas === 1 ? '' : 's'
            ));
            $this->redirect('ver_trabalhador', ['id' => $id]);
        }

        $this->model->excluirTrabalhador($id);
        $this->auditar(Audit::EXCLUIU, 'Trabalhador: ' . $t['nome'], $id);

        Flash::sucesso('Trabalhador excluído.');
        $this->redirect('trabalhadores');
    }

    // ── Empresas ──────────────────────────────────────────────────────────

    public function empresas(): void
    {
        Auth::requirePermissao('ocupacional', 'pode_ver');

        View::render('ocupacional', 'empresas', [
            'empresas' => $this->model->empresas(false),
            'uso'      => $this->model->usoEmpresas(),
            'erros'    => $this->errosGuardados(),
            'editando' => $this->model->empresaPorId((int) ($_GET['id'] ?? 0)) ?: null,
        ]);
    }

    public function salvar_empresa(): void
    {
        Auth::requirePermissao('ocupacional', 'pode_editar');
        $this->exigirPost();

        $id = $this->id();
        $d  = [
            'nome'       => trim((string) ($_POST['nome'] ?? '')),
            'cnpj'       => trim((string) ($_POST['cnpj'] ?? '')),
            'contato'    => preg_replace('/\D/', '', (string) ($_POST['contato'] ?? '')) ?? '',
            'observacao' => trim((string) ($_POST['observacao'] ?? '')),
            'ativo'      => isset($_POST['ativo']) ? 1 : 0,
        ];

        $erros = [];

        if ($d['nome'] === '') {
            $erros['nome'] = 'Informe o nome da empresa.';
        } elseif (mb_strlen($d['nome']) > 150) {
            $erros['nome'] = 'O nome passa de 150 caracteres.';
        } elseif ($this->model->nomeEmpresaExiste($d['nome'], $id)) {
            $erros['nome'] = 'Já existe uma empresa com esse nome.';
        }

        if ($d['contato'] !== '' && !in_array(strlen($d['contato']), [10, 11], true)) {
            $erros['contato'] = 'O telefone precisa ter 10 ou 11 dígitos, com o DDD.';
        }

        if ($erros) {
            $_SESSION['ocup_erros'] = $erros;
            $this->redirect('empresas', $id > 0 ? ['id' => $id] : []);
        }

        $this->model->salvarEmpresa($id, $d);
        $this->auditar($id > 0 ? Audit::EDITOU : Audit::CRIOU, 'Empresa: ' . $d['nome'], $id ?: null);

        Flash::sucesso($id > 0 ? 'Empresa atualizada.' : 'Empresa cadastrada.');
        $this->redirect('empresas');
    }

    public function excluir_empresa(): void
    {
        Auth::requirePermissao('ocupacional', 'pode_excluir');
        $this->exigirPost();

        $id      = $this->id();
        $empresa = $this->model->empresaPorId($id);

        if (!$empresa) {
            Flash::erro('Empresa não encontrada.');
            $this->redirect('empresas');
        }

        $quantos = $this->model->usoEmpresas()[$id] ?? 0;

        if ($quantos > 0) {
            Flash::erro(sprintf(
                '%s tem %d trabalhador%s cadastrado%s e não pode ser excluída. Desmarque "Ativa"'
                . ' para tirar da lista sem perder o histórico.',
                $empresa['nome'], $quantos,
                $quantos === 1 ? '' : 'es', $quantos === 1 ? '' : 's'
            ));
            $this->redirect('empresas');
        }

        $this->model->excluirEmpresa($id);
        $this->auditar(Audit::EXCLUIU, 'Empresa: ' . $empresa['nome'], $id);

        Flash::sucesso('Empresa excluída.');
        $this->redirect('empresas');
    }

    // ── Apoio ─────────────────────────────────────────────────────────────

    private function formulario(array $ficha, array $erros = []): void
    {
        View::render('ocupacional', 'form', [
            'f'             => $ficha,
            'erros'         => $erros,
            'trabalhadores' => $this->model->trabalhadoresParaEscolha(),
        ]);
    }

    private function formularioAtestado(array $a, array $erros = []): void
    {
        View::render('ocupacional', 'atestado_form', [
            'a'             => $a,
            'erros'         => $erros,
            'trabalhadores' => $this->model->trabalhadoresParaEscolha(),
            'proximo'       => $this->model->proximaSequencia((int) date('Y')),
        ]);
    }

    /** Só o que impede o comprovante de existir vira erro. */
    private function validarAtestado(array $d): array
    {
        $erros = [];

        if ($d['trabalhador_id'] <= 0) {
            $erros['trabalhador_id'] = 'Escolha o colaborador.';
        } elseif (!$this->model->trabalhadorPorId($d['trabalhador_id'])) {
            $erros['trabalhador_id'] = 'Colaborador não encontrado.';
        }

        if (!$d['emissao']) {
            $erros['emissao'] = 'Informe a data de emissão do atestado.';
        }

        if (!$d['entrega_data']) {
            $erros['entrega_data'] = 'Informe a data da entrega.';
        }

        if (!isset(OcupacionalModel::FORMAS[$d['forma']])) {
            $erros['forma'] = 'Forma de entrega inválida.';
        }

        if ($d['forma'] === 'outro' && $d['forma_outro'] === '') {
            $erros['forma_outro'] = 'Escreva qual foi a forma de entrega.';
        }

        // Faixa larga de propósito: pega dedo escorregado no teclado, não
        // discute o afastamento que o médico escreveu.
        if ($d['dias'] < 0 || $d['dias'] > 365) {
            $erros['dias'] = 'Quantidade de dias fora do possível (0 a 365).';
        }

        return $erros;
    }

    /** O que merece alerta depois de gravar, sem impedir a gravação. */
    private function avisosAtestado(array $d): void
    {
        if ($d['inicio'] && $d['emissao'] && $d['inicio'] < $d['emissao']) {
            Flash::aviso('O afastamento começa antes da emissão do atestado. Gravei assim'
                . ' mesmo — acontece, mas vale conferir as datas.');
        }

        if ($d['entrega_data'] && $d['emissao'] && $d['entrega_data'] < $d['emissao']) {
            Flash::aviso('A entrega está datada antes da emissão do atestado. Confira o dia.');
        }

        if ($d['recebido_por'] === '') {
            Flash::aviso('Ninguém consta como quem recebeu. O comprovante sai com a linha em'
                . ' branco para assinar à mão — dá para completar aqui depois.');
        }
    }

    /** hh:mm do <input type="time">; qualquer outra coisa vira nulo. */
    private function hora(string $valor): ?string
    {
        $valor = trim($valor);
        if ($valor === '') {
            return null;
        }

        return preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $valor) ? $valor : null;
    }

    private function doPost(): array
    {
        return [
            'trabalhador_id' => (int) ($_POST['trabalhador_id'] ?? 0),
            'tipo'           => (string) ($_POST['tipo'] ?? 'admissional'),
            'data_exame'     => $this->data((string) ($_POST['data_exame'] ?? '')),

            'hist_profissional'  => trim((string) ($_POST['hist_profissional'] ?? '')),
            'risco_ruido'        => isset($_POST['risco_ruido']) ? 1 : 0,
            'risco_quimico'      => isset($_POST['risco_quimico']) ? 1 : 0,
            'risco_biologico'    => isset($_POST['risco_biologico']) ? 1 : 0,
            'risco_ergonomico'   => isset($_POST['risco_ergonomico']) ? 1 : 0,
            'risco_psicossocial' => isset($_POST['risco_psicossocial']) ? 1 : 0,
            // '' = não perguntado, e é diferente de "não usa".
            'epi'                => ($_POST['epi'] ?? '') === '' ? null : (int) $_POST['epi'],
            'antecedentes'       => trim((string) ($_POST['antecedentes'] ?? '')),

            'pa_sistolica'  => (int) ($_POST['pa_sistolica'] ?? 0),
            'pa_diastolica' => (int) ($_POST['pa_diastolica'] ?? 0),
            'fc'            => (int) ($_POST['fc'] ?? 0),
            'peso'          => $this->numero((string) ($_POST['peso'] ?? '')),
            'altura'        => $this->numero((string) ($_POST['altura'] ?? '')),
            'acuidade_od'   => trim((string) ($_POST['acuidade_od'] ?? '')),
            'acuidade_oe'   => trim((string) ($_POST['acuidade_oe'] ?? '')),
            'queixas'       => trim((string) ($_POST['queixas'] ?? '')),

            'audiometria_em'  => $this->data((string) ($_POST['audiometria_em'] ?? '')),
            'espirometria_em' => $this->data((string) ($_POST['espirometria_em'] ?? '')),
            'laboratorial_em' => $this->data((string) ($_POST['laboratorial_em'] ?? '')),
            'outros_exames'   => trim((string) ($_POST['outros_exames'] ?? '')),
            'obs_enfermagem'  => trim((string) ($_POST['obs_enfermagem'] ?? '')),

            'encaminhado_em' => $this->data((string) ($_POST['encaminhado_em'] ?? '')),
            'conclusao'      => (string) ($_POST['conclusao'] ?? 'pendente'),
            'restricoes'     => trim((string) ($_POST['restricoes'] ?? '')),
            'validade'       => $this->data((string) ($_POST['validade'] ?? '')),

            'enfermeiro_nome'  => trim((string) ($_POST['enfermeiro_nome'] ?? '')),
            'enfermeiro_coren' => trim((string) ($_POST['enfermeiro_coren'] ?? '')),
            'medico_nome'      => trim((string) ($_POST['medico_nome'] ?? '')),
            'medico_crm'       => trim((string) ($_POST['medico_crm'] ?? '')),
            'ciencia_em'       => $this->data((string) ($_POST['ciencia_em'] ?? '')),
        ];
    }

    /**
     * Só o que impede a ficha de existir vira erro.
     *
     * Data fora de ordem, sinal vital estranho e conclusão sem validade são
     * avisos depois de gravar: a regra é nossa, o dado é da casa, e recusar
     * a gravação faria perder tudo que já foi digitado.
     */
    private function validar(array $d): array
    {
        $erros = [];

        if ($d['trabalhador_id'] <= 0) {
            $erros['trabalhador_id'] = 'Escolha o trabalhador.';
        } elseif (!$this->model->trabalhadorPorId($d['trabalhador_id'])) {
            $erros['trabalhador_id'] = 'Trabalhador não encontrado.';
        }

        if (!$d['data_exame']) {
            $erros['data_exame'] = 'Informe a data do exame.';
        }

        if (!isset(OcupacionalModel::TIPOS[$d['tipo']])) {
            $erros['tipo'] = 'Tipo de exame inválido.';
        }

        if (!isset(OcupacionalModel::CONCLUSOES[$d['conclusao']])) {
            $erros['conclusao'] = 'Conclusão inválida.';
        }

        // Faixas largas de propósito: servem para pegar dedo escorregado no
        // teclado, não para discutir o achado clínico com quem examinou.
        if ($d['pa_sistolica'] && ($d['pa_sistolica'] < 50 || $d['pa_sistolica'] > 300)) {
            $erros['pa_sistolica'] = 'Pressão sistólica fora do possível (50 a 300).';
        }

        if ($d['pa_diastolica'] && ($d['pa_diastolica'] < 30 || $d['pa_diastolica'] > 200)) {
            $erros['pa_diastolica'] = 'Pressão diastólica fora do possível (30 a 200).';
        }

        if ($d['fc'] && ($d['fc'] < 20 || $d['fc'] > 250)) {
            $erros['fc'] = 'Frequência cardíaca fora do possível (20 a 250).';
        }

        if ($d['peso'] !== null && ($d['peso'] < 20 || $d['peso'] > 400)) {
            $erros['peso'] = 'Peso fora do possível (20 a 400 kg).';
        }

        if ($d['altura'] !== null && ($d['altura'] < 0.5 || $d['altura'] > 2.5)) {
            $erros['altura'] = 'Altura em metros (entre 0,50 e 2,50).';
        }

        return $erros;
    }

    /** O que merece um alerta depois de gravar, sem impedir a gravação. */
    private function avisos(array $d, array $trabalhador): void
    {
        if ($d['conclusao'] === 'pendente') {
            return;   // ficha de triagem recém-feita: é o estado normal
        }

        if (!$d['validade']) {
            Flash::aviso('Ficha concluída sem data de validade. Sem ela o sistema não'
                . ' consegue avisar quando o exame precisa ser refeito.');
        }

        if ($d['conclusao'] === 'apto_restricao' && $d['restricoes'] === '') {
            Flash::aviso('Apto com restrição, mas a restrição não foi escrita —'
                . ' quem ler a ficha depois não vai saber qual é.');
        }

        if ($d['validade'] && $d['data_exame'] && $d['validade'] < $d['data_exame']) {
            Flash::aviso('A validade (' . data_br($d['validade']) . ') é anterior à data do exame ('
                . data_br($d['data_exame']) . '). Gravei assim mesmo — confira o ano.');
        }

        if (!empty($trabalhador['admissao']) && $d['data_exame']
            && $d['tipo'] === 'admissional' && $d['data_exame'] > $trabalhador['admissao']) {
            Flash::aviso('O admissional está com data posterior à admissão ('
                . data_br($trabalhador['admissao']) . '). Acontece de verdade, mas vale conferir.');
        }
    }

    /** Vírgula ou ponto: a atendente digita 1,72 e o banco quer 1.72. */
    private function numero(string $valor): ?float
    {
        $valor = trim(str_replace(',', '.', $valor));
        return $valor === '' ? null : (float) $valor;
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

    /** Erros de validação atravessando o redirect das telas de cadastro. */
    private function errosGuardados(): array
    {
        $erros = (array) ($_SESSION['ocup_erros'] ?? []);
        unset($_SESSION['ocup_erros']);
        return $erros;
    }
}
