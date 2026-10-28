<?php
declare(strict_types=1);

require_once BASE_PATH . '/modules/cadastro/Model.php';

/**
 * Cadastro de beneficiários do plano de saúde.
 *
 * A unidade de trabalho é o CONTRATO: uma família, com um titular e seus
 * dependentes. As regras de elegibilidade vivem em Elegibilidade.php; aqui
 * o que se faz é perguntar a ela e mostrar a resposta a quem cadastra.
 */
class CadastroController extends Controller
{
    protected string $modulo = 'cadastro';

    private CadastroModel $model;

    public function __construct()
    {
        $this->model = new CadastroModel();
    }

    // ── Lista de contratos ────────────────────────────────────────────────

    public function lista(): void
    {
        Auth::requirePermissao('cadastro', 'pode_ver');

        $filtro = [
            'busca'  => trim((string) ($_GET['q'] ?? '')),
            'status' => (string) ($_GET['status'] ?? ''),
            'tipo'   => (string) ($_GET['tipo'] ?? ''),
        ];

        $porPagina = Paginacao::porPagina(50);
        $total     = $this->model->contar($filtro);
        $paginas   = max(1, (int) ceil($total / $porPagina));
        $pagina    = min(max(1, (int) ($_GET['pagina'] ?? 1)), $paginas);

        View::render('cadastro', 'lista', [
            'contratos' => $this->model->listar($filtro, $porPagina, ($pagina - 1) * $porPagina),
            'total'     => $total,
            'pagina'    => $pagina,
            'porPagina' => $porPagina,
            'panorama'  => $this->model->panorama(),
            'filtro'    => $filtro,
        ]);
    }

    /** O contrato por dentro: as pessoas e o que a família paga. */
    public function ver(): void
    {
        Auth::requirePermissao('cadastro', 'pode_ver');

        $id       = $this->id();
        $contrato = $this->model->contratoPorId($id);

        if (!$contrato) {
            Flash::erro('Contrato não encontrado.');
            $this->redirect();
        }

        $pessoas = $this->model->beneficiarios($id);

        // Reavalia todo mundo agora: idade muda, e com ela a classificação
        // de um filho que passou dos 18 desde o cadastro.
        foreach ($pessoas as &$p) {
            $p['avaliacao'] = Elegibilidade::avaliar($p, $contrato['tipo']);
        }
        unset($p);

        View::render('cadastro', 'ver', [
            'c'           => $contrato,
            'pessoas'     => $pessoas,
            'mensalidade' => $this->model->mensalidade($pessoas),
            'declaracoes' => $this->model->declaracoesDoContrato($id),
            'desalinhados' => $this->model->desalinhados($id),
        ]);
    }

    // ── Contrato ─────────────────────────────────────────────────────────

    public function novo(): void
    {
        Auth::requirePermissao('cadastro', 'pode_criar');

        $this->formContrato([
            'id'          => 0,
            'tipo'        => 'com_copart',
            'status'      => 'em_analise',
            'data_adesao' => date('Y-m-d'),
        ]);
    }

    public function editar(): void
    {
        Auth::requirePermissao('cadastro', 'pode_editar');

        $contrato = $this->model->contratoPorId($this->id());

        if (!$contrato) {
            Flash::erro('Contrato não encontrado.');
            $this->redirect();
        }

        $this->formContrato($contrato);
    }

    public function salvar(): void
    {
        Auth::requirePermissao('cadastro', $this->id() > 0 ? 'pode_editar' : 'pode_criar');
        $this->exigirPost();

        $id = $this->id();
        $d  = [
            'numero_proposta' => trim((string) ($_POST['numero_proposta'] ?? '')),
            'tipo'            => (string) ($_POST['tipo'] ?? 'com_copart'),
            'data_adesao'     => $this->data((string) ($_POST['data_adesao'] ?? '')),
            'status'          => (string) ($_POST['status'] ?? 'em_analise'),
            'observacao'      => trim((string) ($_POST['observacao'] ?? '')),
        ];

        $erros = [];

        if (!isset(Elegibilidade::TIPOS_CONTRATO[$d['tipo']])) {
            $erros['tipo'] = 'Escolha se o contrato é com ou sem coparticipação.';
        }

        if (!isset(CadastroModel::STATUS[$d['status']])) {
            $erros['status'] = 'Situação inválida.';
        }

        if ($d['numero_proposta'] !== '' && $this->model->propostaExiste($d['numero_proposta'], $id)) {
            $erros['numero_proposta'] = 'Já existe um contrato com este número de proposta.';
        }

        if ($erros) {
            $this->formContrato($d + ['id' => $id], $erros);
            return;
        }

        // Mudar o tipo do contrato reclassifica a família inteira — é a
        // escolha que separa o STANDARD 4 dos planos com coparticipação.
        $antes = $id > 0 ? $this->model->contratoPorId($id) : null;
        $mudouTipo = $antes && $antes['tipo'] !== $d['tipo'];

        $id = $this->model->salvarContrato($id, $d);

        $this->auditar($antes ? Audit::EDITOU : Audit::CRIOU,
            'Contrato ' . ($d['numero_proposta'] ?: '#' . $id) . ' — '
            . (Elegibilidade::TIPOS_CONTRATO[$d['tipo']] ?? $d['tipo']), $id);

        if ($mudouTipo) {
            Flash::aviso('O tipo do contrato mudou. Confira o plano de cada pessoa:'
                . ' a classificação depende dele, e quem estava no STANDARD 1 ou 2'
                . ' passa a ser STANDARD 4, e vice-versa.');
        }

        Flash::sucesso($antes ? 'Contrato atualizado.' : 'Contrato criado. Agora cadastre o titular.');

        // Contrato novo sem ninguém dentro não serve para nada: leva direto
        // ao cadastro do titular.
        if (!$antes) {
            $this->redirect('pessoa', ['contrato' => $id, 'tipo' => 'titular']);
        }

        $this->redirect('ver', ['id' => $id]);
    }

    public function excluir(): void
    {
        Auth::requirePermissao('cadastro', 'pode_excluir');
        $this->exigirPost();

        $id       = $this->id();
        $contrato = $this->model->contratoPorId($id);

        if (!$contrato) {
            Flash::erro('Contrato não encontrado.');
            $this->redirect();
        }

        // Declaração de saúde é documento assinado pela família: não some
        // junto com o contrato. Para encerrar, a situação vira Cancelado.
        $declaracoes = $this->model->declaracoesDoContrato($id);

        if ($declaracoes > 0) {
            Flash::erro(sprintf(
                'O contrato tem %d declaraç%s de saúde e não pode ser excluído — é documento'
                . ' assinado. Para encerrá-lo, mude a situação para Cancelado.',
                $declaracoes, $declaracoes === 1 ? 'ão' : 'ões'
            ));
            $this->redirect('ver', ['id' => $id]);
        }

        $saiu = $this->model->excluirContrato($id);

        $this->auditar(
            Audit::EXCLUIU,
            'Contrato ' . ($contrato['numero_proposta'] ?: '#' . $id)
                . ($saiu ? sprintf(' com %d pessoa%s', $saiu, $saiu === 1 ? '' : 's') : ''),
            $id
        );

        Flash::sucesso($saiu
            ? sprintf('Contrato excluído, com %d pessoa%s.', $saiu, $saiu === 1 ? '' : 's')
            : 'Contrato excluído.');
        $this->redirect();
    }

    // ── Beneficiários ────────────────────────────────────────────────────

    /** O formulário de uma pessoa — titular ou dependente. */
    public function pessoa(): void
    {
        Auth::requirePermissao('cadastro', 'pode_criar');

        $id = (int) ($_GET['id'] ?? 0);

        if ($id > 0) {
            $b = $this->model->beneficiarioPorId($id);

            if (!$b) {
                Flash::erro('Beneficiário não encontrado.');
                $this->redirect();
            }

            $this->formPessoa($b, (int) $b['contrato_id']);
            return;
        }

        $contratoId = (int) ($_GET['contrato'] ?? 0);
        $contrato   = $this->model->contratoPorId($contratoId);

        if (!$contrato) {
            Flash::erro('Escolha o contrato antes de cadastrar a pessoa.');
            $this->redirect();
        }

        // Um contrato tem um titular só. Se já existe, o que se cadastra
        // daqui em diante é dependente.
        $temTitular = (bool) $this->model->titularDe($contratoId);
        $tipo = (string) ($_GET['tipo'] ?? 'dependente');
        $tipo = (!$temTitular && $tipo === 'titular') ? 'titular' : 'dependente';

        // O plano é do contrato, não da pessoa: dependente novo já nasce no
        // plano do titular. A sugestão da regra continua aparecendo ao lado,
        // como observação — e dá para trocar na hora.
        $titular = $this->model->titularDe($contratoId);

        $this->formPessoa([
            'id'            => 0,
            'contrato_id'   => $contratoId,
            'tipo'          => $tipo,
            'status'        => 'ativo',
            'plano_id'      => $tipo === 'dependente' ? (int) ($titular['plano_id'] ?? 0) : 0,
            'data_inclusao' => $contrato['data_adesao'] ?: date('Y-m-d'),
        ], $contratoId);
    }

    public function salvar_pessoa(): void
    {
        Auth::requirePermissao('cadastro', 'pode_editar');
        $this->exigirPost();

        $id         = $this->id();
        $contratoId = (int) ($_POST['contrato_id'] ?? 0);
        $contrato   = $this->model->contratoPorId($contratoId);

        if (!$contrato) {
            Flash::erro('Contrato não encontrado.');
            $this->redirect();
        }

        $d = $this->pessoaDoPost($contratoId);
        $erros = $this->validarPessoa($d, $contratoId, $id);

        // A avaliação acontece aqui, e não no Model: é ela que preenche o
        // plano sugerido que vai gravado junto.
        $avaliacao = Elegibilidade::avaliar($d, $contrato['tipo']);
        $d['plano_sugerido'] = $avaliacao['plano'] ?? '';

        // Sem plano escolhido na tela: para o dependente vale o plano do
        // titular, que é a regra da casa; só na falta dele entra o sugerido
        // pela elegibilidade. Para o titular, o sugerido.
        if ($d['plano_id'] === 0) {
            $titular = $d['tipo'] === 'dependente' ? $this->model->titularDe($contratoId) : false;
            $d['plano_id'] = (int) ($titular['plano_id'] ?? 0);
        }

        if ($d['plano_id'] === 0 && $avaliacao['plano']) {
            $plano = $this->model->planoPorNome($avaliacao['plano']);
            $d['plano_id'] = $plano ? (int) $plano['id'] : 0;
        }

        // O plano que o titular tinha antes desta gravação: é a comparação
        // que diz se o contrato inteiro precisa acompanhar.
        $planoAntes = 0;
        if ($id > 0 && $d['tipo'] === 'titular') {
            $antes = $this->model->beneficiarioPorId($id);
            $planoAntes = (int) ($antes['plano_id'] ?? 0);
        }

        if ($erros) {
            $this->formPessoa($d + ['id' => $id], $contratoId, $erros);
            return;
        }

        $novoId = $this->model->salvarBeneficiario($id, $d);

        $this->auditar($id > 0 ? Audit::EDITOU : Audit::CRIOU, sprintf(
            '%s: %s (%s)',
            $d['tipo'] === 'titular' ? 'Titular' : 'Dependente',
            $d['nome'],
            $d['tipo'] === 'titular'
                ? (Elegibilidade::CATEGORIAS[$d['categoria']] ?? '—')
                : Elegibilidade::rotuloParentesco($d['parentesco'])
        ), $novoId);

        $this->avisosPessoa($d, $avaliacao);

        // Mudou o plano do titular e há dependentes noutro plano: a tela de
        // confirmação mostra quem muda e quanto a família passa a pagar,
        // antes de mexer no cadastro dos outros.
        if ($d['tipo'] === 'titular'
            && $planoAntes !== (int) $d['plano_id']
            && $this->model->desalinhados($contratoId)) {

            Flash::sucesso('Plano do titular alterado. Confirme o que fazer com os dependentes.');
            $this->redirect('alinhar', ['id' => $contratoId]);
        }

        Flash::sucesso($id > 0 ? 'Cadastro atualizado.' : 'Pessoa incluída no contrato.');
        $this->redirect('ver', ['id' => $contratoId]);
    }

    /**
     * A avaliação da elegibilidade em JSON, para a tela consultar enquanto
     * a pessoa digita.
     *
     * Existe para que as regras morem num lugar só. Repetir a matriz do
     * regulamento em JavaScript daria duas verdades que divergem na
     * primeira correção — e a que o usuário vê na tela seria a errada.
     */
    public function avaliar(): void
    {
        Auth::requirePermissao('cadastro', 'pode_ver');

        $tipoContrato = (string) ($_GET['contrato_tipo'] ?? 'com_copart');

        if (!isset(Elegibilidade::TIPOS_CONTRATO[$tipoContrato])) {
            $tipoContrato = 'com_copart';
        }

        $p = [
            'tipo'       => (string) ($_GET['tipo'] ?? 'dependente'),
            'categoria'  => (string) ($_GET['categoria'] ?? ''),
            'parentesco' => (string) ($_GET['parentesco'] ?? ''),
            'nascimento' => (string) ($_GET['nascimento'] ?? ''),
        ];

        foreach (array_keys(Elegibilidade::CONDICOES) as $condicao) {
            $p[$condicao] = !empty($_GET[$condicao]) ? 1 : 0;
        }

        $r = Elegibilidade::avaliar($p, $tipoContrato);
        $r['idade'] = Elegibilidade::idade($p['nascimento'] ?: null);
        $r['faixa'] = Elegibilidade::rotuloFaixa(Elegibilidade::faixa($r['idade']));

        $this->json($r);
    }

    public function excluir_pessoa(): void
    {
        Auth::requirePermissao('cadastro', 'pode_excluir');
        $this->exigirPost();

        $id = $this->id();
        $b  = $this->model->beneficiarioPorId($id);

        if (!$b) {
            Flash::erro('Beneficiário não encontrado.');
            $this->redirect();
        }

        $contratoId = (int) $b['contrato_id'];

        // Tirar o titular deixaria os dependentes pendurados em ninguém.
        if ($b['tipo'] === 'titular') {
            $outros = count($this->model->beneficiarios($contratoId)) - 1;

            if ($outros > 0) {
                Flash::erro(sprintf(
                    'O titular não pode ser excluído enquanto houver %d dependente%s no'
                    . ' contrato — todos dependem do vínculo dele. Exclua os dependentes'
                    . ' primeiro, ou marque o titular como inativo.',
                    $outros, $outros === 1 ? '' : 's'
                ));
                $this->redirect('ver', ['id' => $contratoId]);
            }
        }

        // Quem já respondeu a declaração de saúde faz parte de um documento
        // assinado: a resposta dela não pode ficar órfã.
        if ($this->model->temRespostas($id)) {
            Flash::erro(
                $b['nome'] . ' consta numa declaração de saúde e não pode ser excluído.'
                . ' Para tirá-lo do contrato, marque como inativo.'
            );
            $this->redirect('ver', ['id' => $contratoId]);
        }

        $this->model->excluirBeneficiario($id);
        $this->auditar(Audit::EXCLUIU, $b['nome'], $id);

        Flash::sucesso('Beneficiário excluído.');
        $this->redirect('ver', ['id' => $contratoId]);
    }

    // ── O plano do titular aplicado à família ────────────────────────────

    /** A tela de confirmação: quem muda de plano e quanto passa a custar. */
    public function alinhar(): void
    {
        Auth::requirePermissao('cadastro', 'pode_editar');

        $id       = $this->id();
        $contrato = $this->model->contratoPorId($id);

        if (!$contrato) {
            Flash::erro('Contrato não encontrado.');
            $this->redirect();
        }

        $titular = $this->model->titularDe($id);

        if (!$titular || !$titular['plano_id']) {
            Flash::aviso('O titular deste contrato está sem plano definido. Defina o plano'
                . ' dele primeiro — é ele que vale para a família inteira.');
            $this->redirect('ver', ['id' => $id]);
        }

        $simulacao = $this->model->simularAlinhamento($id);

        // Sem ninguém para mudar, a tela não tem o que perguntar.
        if (empty($simulacao['linhas'])) {
            Flash::aviso('Todos os dependentes já estão no plano do titular.');
            $this->redirect('ver', ['id' => $id]);
        }

        View::render('cadastro', 'alinhar', [
            'c' => $contrato,
            's' => $simulacao,
        ]);
    }

    public function alinhar_confirmar(): void
    {
        Auth::requirePermissao('cadastro', 'pode_editar');
        $this->exigirPost();

        $id       = $this->id();
        $contrato = $this->model->contratoPorId($id);

        if (!$contrato) {
            Flash::erro('Contrato não encontrado.');
            $this->redirect();
        }

        $titular = $this->model->titularDe($id);

        if (!$titular || !$titular['plano_id']) {
            Flash::erro('O titular deste contrato está sem plano definido —'
                . ' não há plano para aplicar aos dependentes.');
            $this->redirect('ver', ['id' => $id]);
        }

        $mudaram = $this->model->alinharPlanos($id);

        $this->auditar(Audit::EDITOU, sprintf(
            'Contrato %s: %d dependente%s movido%s para o plano do titular',
            $contrato['numero_proposta'] ?: '#' . $id,
            $mudaram, $mudaram === 1 ? '' : 's', $mudaram === 1 ? '' : 's'
        ), $id);

        Flash::sucesso($mudaram === 1
            ? '1 dependente passou para o plano do titular.'
            : sprintf('%d dependentes passaram para o plano do titular.', $mudaram));

        $this->redirect('ver', ['id' => $id]);
    }

    // ── Tabela de preços ─────────────────────────────────────────────────

    public function precos(): void
    {
        Auth::requirePermissao('cadastro', 'pode_ver');

        $planoId  = (int) ($_GET['plano'] ?? 0);
        $vigencia = $this->data((string) ($_GET['vigencia'] ?? ''));

        View::render('cadastro', 'precos', [
            'planos'    => $this->model->planos(false),
            'vigencias' => $this->model->vigencias(),
            'vigentes'  => $this->model->precosVigentes(),
            'editando'  => $planoId > 0 && $vigencia
                           ? ['plano_id' => $planoId, 'vigencia' => $vigencia,
                              'valores' => $this->model->precosDe($planoId, $vigencia)]
                           : null,
            'erros'     => $this->errosGuardados(),
        ]);
    }

    public function salvar_precos(): void
    {
        Auth::requirePermissao('cadastro', 'pode_editar');
        $this->exigirPost();

        $planoId  = (int) ($_POST['plano_id'] ?? 0);
        $vigencia = $this->data((string) ($_POST['vigencia'] ?? ''));
        $valores  = (array) ($_POST['valor'] ?? []);

        $erros = [];

        if ($planoId <= 0) {
            $erros['plano_id'] = 'Escolha o plano.';
        }

        if (!$vigencia) {
            $erros['vigencia'] = 'Informe a data em que esta tabela passa a valer.';
        }

        $limpos = [];
        foreach (range(0, 9) as $faixa) {
            $v = valor_br((string) ($valores[$faixa] ?? ''));

            if ($v <= 0) {
                $erros['valor'] = 'Preencha o valor das dez faixas etárias.';
                break;
            }
            $limpos[$faixa] = $v;
        }

        if ($erros) {
            $_SESSION['cadastro_erros'] = $erros;
            $this->redirect('precos', array_filter(['plano' => $planoId, 'vigencia' => $vigencia]));
        }

        $this->model->salvarPrecos($planoId, $vigencia, $limpos);

        $plano = $this->model->planos(false);
        $nome  = '';
        foreach ($plano as $p) {
            if ((int) $p['id'] === $planoId) {
                $nome = $p['nome'];
            }
        }

        $this->auditar(Audit::EDITOU,
            sprintf('Tabela de preços %s, vigência %s', $nome, data_br($vigencia)), $planoId);

        // Vigência futura não muda nada hoje — e quem acabou de digitar
        // precisa saber disso antes de procurar o valor novo na tela.
        if ($vigencia > date('Y-m-d')) {
            Flash::aviso('Tabela gravada com vigência em ' . data_br($vigencia)
                . '. Ela só passa a valer nessa data — até lá, as mensalidades'
                . ' continuam sendo calculadas pela tabela atual.');
        }

        Flash::sucesso('Tabela de preços gravada.');
        $this->redirect('precos');
    }

    public function excluir_precos(): void
    {
        Auth::requirePermissao('cadastro', 'pode_excluir');
        $this->exigirPost();

        $planoId  = (int) ($_POST['plano_id'] ?? 0);
        $vigencia = $this->data((string) ($_POST['vigencia'] ?? ''));

        if ($planoId <= 0 || !$vigencia) {
            Flash::erro('Vigência não encontrada.');
            $this->redirect('precos');
        }

        $this->model->excluirVigencia($planoId, $vigencia);
        $this->auditar(Audit::EXCLUIU, 'Tabela de preços, vigência ' . data_br($vigencia), $planoId);

        Flash::sucesso('Vigência excluída.');
        $this->redirect('precos');
    }

    // ── Apoio ─────────────────────────────────────────────────────────────

    private function formContrato(array $c, array $erros = []): void
    {
        View::render('cadastro', 'form', ['c' => $c, 'erros' => $erros]);
    }

    private function formPessoa(array $b, int $contratoId, array $erros = []): void
    {
        $contrato = $this->model->contratoPorId($contratoId);

        View::render('cadastro', 'pessoa', [
            'b'         => $b,
            'c'         => $contrato,
            'erros'     => $erros,
            'planos'    => $this->model->planos(),
            'avaliacao' => Elegibilidade::avaliar($b, $contrato['tipo'] ?? 'com_copart'),
            'temTitular' => (bool) $this->model->titularDe($contratoId),
        ]);
    }

    private function pessoaDoPost(int $contratoId): array
    {
        return [
            'contrato_id' => $contratoId,
            'tipo'        => (string) ($_POST['tipo'] ?? 'dependente'),
            'nome'        => trim((string) ($_POST['nome'] ?? '')),
            // CPF só com dígitos, como no resto do sistema: a pontuação é da
            // tela, não do dado.
            'cpf'         => preg_replace('/\D/', '', (string) ($_POST['cpf'] ?? '')) ?? '',
            'nascimento'  => $this->data((string) ($_POST['nascimento'] ?? '')),
            'sexo'        => (string) ($_POST['sexo'] ?? ''),
            'nome_mae'    => trim((string) ($_POST['nome_mae'] ?? '')),
            'cns'         => preg_replace('/\D/', '', (string) ($_POST['cns'] ?? '')) ?? '',
            'contato'     => preg_replace('/\D/', '', (string) ($_POST['contato'] ?? '')) ?? '',
            'email'       => trim((string) ($_POST['email'] ?? '')),

            'categoria'  => (string) ($_POST['categoria'] ?? ''),
            'matricula'  => trim((string) ($_POST['matricula'] ?? '')),
            'parentesco' => (string) ($_POST['parentesco'] ?? ''),

            'guarda_judicial'       => isset($_POST['guarda_judicial']) ? 1 : 0,
            'tutela_curatela'       => isset($_POST['tutela_curatela']) ? 1 : 0,
            'dependencia_economica' => isset($_POST['dependencia_economica']) ? 1 : 0,
            'uniao_estavel'         => isset($_POST['uniao_estavel']) ? 1 : 0,
            'emancipado'            => isset($_POST['emancipado']) ? 1 : 0,
            'vinculo_comprovado'    => isset($_POST['vinculo_comprovado']) ? 1 : 0,

            'plano_id'       => (int) ($_POST['plano_id'] ?? 0),
            'plano_sugerido' => '',

            'data_inclusao'   => $this->data((string) ($_POST['data_inclusao'] ?? '')),
            'data_exclusao'   => $this->data((string) ($_POST['data_exclusao'] ?? '')),
            'motivo_exclusao' => trim((string) ($_POST['motivo_exclusao'] ?? '')),
            'status'          => (string) ($_POST['status'] ?? 'ativo'),
            'observacao'      => trim((string) ($_POST['observacao'] ?? '')),
        ];
    }

    /**
     * Só o que impede o cadastro de existir vira erro.
     *
     * Falta de documento é pendência, não erro: travar por papel faria a
     * atendente anotar o cadastro num caderno para digitar quando a pasta
     * chegasse — que é o que o sistema veio evitar.
     */
    private function validarPessoa(array $d, int $contratoId, int $id): array
    {
        $erros = [];

        if ($d['nome'] === '') {
            $erros['nome'] = 'Informe o nome.';
        } elseif (mb_strlen($d['nome']) > 150) {
            $erros['nome'] = 'O nome passa de 150 caracteres.';
        }

        if ($d['cpf'] !== '' && !cpf_valido($d['cpf'])) {
            $erros['cpf'] = 'CPF inválido. Confira os números ou deixe em branco.';
        } elseif ($d['cpf'] !== '') {
            $repetido = $this->model->cpfNoContrato($contratoId, $d['cpf'], $id);
            if ($repetido > 0) {
                $erros['cpf'] = 'Este CPF já está neste contrato.';
                $erros['_link'] = url('cadastro', 'pessoa', ['id' => $repetido]);
            }
        }

        if ($d['nascimento'] && $d['nascimento'] > date('Y-m-d')) {
            $erros['nascimento'] = 'A data de nascimento está no futuro.';
        }

        if ($d['contato'] !== '' && !in_array(strlen($d['contato']), [10, 11], true)) {
            $erros['contato'] = 'O telefone precisa ter 10 ou 11 dígitos, com o DDD.';
        }

        if ($d['email'] !== '' && !filter_var($d['email'], FILTER_VALIDATE_EMAIL)) {
            $erros['email'] = 'E-mail inválido.';
        }

        // O titular é a porta de entrada do contrato: sem categoria válida
        // não há plano, e o documento é explícito nisso.
        if ($d['tipo'] === 'titular' && !isset(Elegibilidade::CATEGORIAS[$d['categoria']])) {
            $erros['categoria'] = 'Só pode ser titular quem é Policial Militar ativo,'
                                . ' inativo ou pensionista. Escolha a categoria.';
        }

        if ($d['tipo'] === 'dependente' && $d['parentesco'] === '') {
            $erros['parentesco'] = 'Informe o grau de parentesco com o titular.';
        }

        // Dependente sem titular ficaria pendurado em ninguém.
        if ($d['tipo'] === 'dependente' && $id === 0 && !$this->model->titularDe($contratoId)) {
            $erros['parentesco'] = 'Cadastre o titular do contrato antes dos dependentes.';
        }

        return $erros;
    }

    /** O que merece alerta depois de gravar, sem impedir a gravação. */
    private function avisosPessoa(array $d, array $avaliacao): void
    {
        if (!$avaliacao['elegivel'] && $avaliacao['impedimento']) {
            Flash::aviso('Atenção: ' . $avaliacao['impedimento']);
            return;
        }

        if ($avaliacao['pendencias']) {
            Flash::aviso('Falta comprovar: ' . implode(', ', $avaliacao['pendencias'])
                . '. O cadastro foi gravado — a pendência fica marcada na tela do contrato.');
        }

        // Plano trocado à mão em relação ao sugerido: não é erro, mas
        // precisa ficar visível, porque muda o valor da mensalidade.
        if ($d['plano_id'] > 0 && $d['plano_sugerido'] !== '') {
            $escolhido = null;
            foreach ($this->model->planos(false) as $p) {
                if ((int) $p['id'] === $d['plano_id']) {
                    $escolhido = $p['nome'];
                }
            }

            if ($escolhido && $escolhido !== $d['plano_sugerido']) {
                Flash::aviso(sprintf(
                    'O plano gravado (%s) é diferente do que a regra indicou (%s).'
                    . ' Gravei a sua escolha — só confira, porque muda a mensalidade.',
                    $escolhido, $d['plano_sugerido']
                ));
            }
        }
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
        $erros = (array) ($_SESSION['cadastro_erros'] ?? []);
        unset($_SESSION['cadastro_erros']);
        return $erros;
    }
}
