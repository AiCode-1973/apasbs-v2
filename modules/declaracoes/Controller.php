<?php
declare(strict_types=1);

require_once BASE_PATH . '/modules/declaracoes/Model.php';

/**
 * Declaração de Saúde.
 *
 * O documento é do contrato — uma folha para a família inteira, assinada
 * uma vez. Mas o preenchimento é pessoa a pessoa: cada um responde sobre
 * si, e ninguém preenche 22 itens de sete pessoas numa grade só sem errar
 * de coluna. A grade fica para o papel, que é onde ela faz sentido.
 */
class DeclaracoesController extends Controller
{
    protected string $modulo = 'declaracoes';

    private DeclaracaoModel $model;

    public function __construct()
    {
        $this->model = new DeclaracaoModel();
    }

    // ── Lista ─────────────────────────────────────────────────────────────

    public function lista(): void
    {
        Auth::requirePermissao('declaracoes', 'pode_ver');

        $filtro = [
            'busca'   => trim((string) ($_GET['q'] ?? '')),
            'status'  => (string) ($_GET['status'] ?? ''),
            'com_dlp' => !empty($_GET['dlp']),
        ];

        $porPagina = Paginacao::porPagina(50);
        $total     = $this->model->contar($filtro);
        $paginas   = max(1, (int) ceil($total / $porPagina));
        $pagina    = min(max(1, (int) ($_GET['pagina'] ?? 1)), $paginas);

        View::render('declaracoes', 'lista', [
            'declaracoes' => $this->model->listar($filtro, $porPagina, ($pagina - 1) * $porPagina),
            'total'       => $total,
            'pagina'      => $pagina,
            'porPagina'   => $porPagina,
            'panorama'    => $this->model->panorama(),
            'filtro'      => $filtro,
        ]);
    }

    public function ver(): void
    {
        Auth::requirePermissao('declaracoes', 'pode_ver');

        [$d, $pessoas, $respostas] = $this->carregar($this->id());

        View::render('declaracoes', 'ver', [
            'd'       => $d,
            'pessoas' => $pessoas,
            'resumo'  => $this->model->resumo($d, $pessoas, $respostas),
            'fimCpt'  => $this->model->fimCpt($d['data_contratacao']),
        ]);
    }

    /**
     * A folha em papel, no desenho do formulário.
     *
     * Imprimir é auditado: é o momento em que dado de saúde sai do sistema
     * em papel, e a LGPD pede saber quem o tirou. Mesma regra do prontuário
     * de saúde ocupacional.
     */
    public function imprimir(): void
    {
        Auth::requirePermissao('declaracoes', 'pode_ver');

        [$d, $pessoas, $respostas] = $this->carregar($this->id());

        $titular = '';
        foreach ($pessoas as $p) {
            if ($p['tipo'] === 'titular') {
                $titular = $p['nome'];
            }
        }

        $this->auditar(Audit::IMPRIMIU, sprintf(
            'Declaração de saúde — %s (contrato %s)',
            $titular ?: '#' . $d['contrato_id'],
            $d['numero_proposta'] ?: '#' . $d['contrato_id']
        ), (int) $d['id']);

        View::render('declaracoes', 'imprimir', [
            'd'         => $d,
            'pessoas'   => $pessoas,
            'respostas' => $respostas,
        ], false);
    }

    // ── A declaração ──────────────────────────────────────────────────────

    public function novo(): void
    {
        Auth::requirePermissao('declaracoes', 'pode_criar');

        $contratoId = (int) ($_GET['contrato'] ?? 0);

        $this->formulario([
            'id'                 => 0,
            'contrato_id'        => $contratoId,
            'data_preenchimento' => date('Y-m-d'),
            'orientacao'         => 'dispensa',
            'status'             => 'em_preenchimento',
        ]);
    }

    public function editar(): void
    {
        Auth::requirePermissao('declaracoes', 'pode_editar');

        $d = $this->model->porId($this->id());

        if (!$d) {
            Flash::erro('Declaração não encontrada.');
            $this->redirect();
        }

        $this->formulario($d);
    }

    public function salvar(): void
    {
        Auth::requirePermissao('declaracoes', $this->id() > 0 ? 'pode_editar' : 'pode_criar');
        $this->exigirPost();

        $id = $this->id();
        $d  = $this->doPost();
        $erros = $this->validar($d, $id);

        if ($erros) {
            $this->formulario($d + ['id' => $id], $erros);
            return;
        }

        $novo = $id === 0;
        $id   = $this->model->salvar($id, $d);

        $contrato = (new CadastroModel())->contratoPorId((int) $d['contrato_id']);

        $this->auditar($novo ? Audit::CRIOU : Audit::EDITOU, sprintf(
            'Declaração de saúde do contrato %s',
            $contrato['numero_proposta'] ?? '#' . $d['contrato_id']
        ), $id);

        $this->avisos($d, $id);

        Flash::sucesso($novo
            ? 'Declaração criada. Agora responda o questionário de cada pessoa.'
            : 'Declaração atualizada.');

        $this->redirect('ver', ['id' => $id]);
    }

    public function excluir(): void
    {
        Auth::requirePermissao('declaracoes', 'pode_excluir');
        $this->exigirPost();

        $id = $this->id();
        $d  = $this->model->porId($id);

        if (!$d) {
            Flash::erro('Declaração não encontrada.');
            $this->redirect();
        }

        if ($d['status'] === 'concluida') {
            Flash::erro('Declaração concluída não pode ser excluída. Ela é o documento'
                . ' que protege as duas partes sobre o que foi declarado na adesão.'
                . ' Se houver erro, volte para "em preenchimento" e corrija.');
            $this->redirect('ver', ['id' => $id]);
        }

        $this->model->excluir($id);
        $this->auditar(Audit::EXCLUIU,
            'Declaração de saúde do contrato ' . ($d['numero_proposta'] ?: '#' . $d['contrato_id']), $id);

        Flash::sucesso('Declaração excluída.');
        $this->redirect();
    }

    // ── O questionário ────────────────────────────────────────────────────

    /** As 22 perguntas de uma pessoa. */
    public function responder(): void
    {
        Auth::requirePermissao('declaracoes', 'pode_editar');

        $declaracaoId  = (int) ($_GET['declaracao'] ?? 0);
        $beneficiarioId = (int) ($_GET['pessoa'] ?? 0);

        [$d, $pessoas, $respostas] = $this->carregar($declaracaoId);

        $pessoa = null;
        foreach ($pessoas as $p) {
            if ((int) $p['id'] === $beneficiarioId) {
                $pessoa = $p;
            }
        }

        if (!$pessoa) {
            Flash::erro('Pessoa não encontrada nesta declaração.');
            $this->redirect('ver', ['id' => $declaracaoId]);
        }

        View::render('declaracoes', 'responder', [
            'd'        => $d,
            'pessoa'   => $pessoa,
            'pessoas'  => $pessoas,
            'suas'     => $respostas[$beneficiarioId] ?? [],
            // A idade vem do cadastro: item 21 é dado que o sistema já tem,
            // e digitar de novo é convite a divergir.
            'idade'    => Elegibilidade::idade($pessoa['nascimento'] ?? null),
        ]);
    }

    public function salvar_respostas(): void
    {
        Auth::requirePermissao('declaracoes', 'pode_editar');
        $this->exigirPost();

        $declaracaoId   = (int) ($_POST['declaracao_id'] ?? 0);
        $beneficiarioId = (int) ($_POST['beneficiario_id'] ?? 0);

        $d = $this->model->porId($declaracaoId);

        if (!$d) {
            Flash::erro('Declaração não encontrada.');
            $this->redirect();
        }

        $benef = $this->model->beneficiarioPorId($beneficiarioId);

        if (!$benef || (int) $benef['contrato_id'] !== (int) $d['contrato_id']) {
            Flash::erro('Esta pessoa não pertence ao contrato da declaração.');
            $this->redirect('ver', ['id' => $declaracaoId]);
        }

        $itens = [];
        foreach (array_keys(DeclaracaoModel::ITENS) as $n) {
            $itens[$n] = [
                'resposta'  => (string) ($_POST['resposta'][$n] ?? ''),
                'valor'     => (string) ($_POST['valor'][$n] ?? ''),
                'descricao' => (string) ($_POST['descricao'][$n] ?? ''),
            ];
        }

        $this->model->salvarRespostas($declaracaoId, $beneficiarioId, $itens);

        $this->auditar(Audit::EDITOU,
            'Declaração de saúde: respostas de ' . $benef['nome'], $declaracaoId);

        // "Sim" sem dizer qual doença não protege ninguém: nem a operadora,
        // que não sabe o que é CPT, nem o beneficiário, que fica exposto a
        // alegação de omissão. Avisa, mas não impede gravar.
        $semDescricao = [];
        foreach (DeclaracaoModel::ITENS as $n => $def) {
            if ($def['tipo'] === 'sn'
                && ($itens[$n]['resposta'] ?? '') === 'S'
                && trim($itens[$n]['descricao'] ?? '') === '') {
                $semDescricao[] = $n;
            }
        }

        if ($semDescricao) {
            Flash::aviso(sprintf(
                '%s: %s marcado%s como "sim" sem descrição. Sem dizer qual é a doença,'
                . ' a declaração não protege o beneficiário de alegação de omissão.',
                $benef['nome'],
                'o item ' . implode(', ', $semDescricao),
                count($semDescricao) === 1 ? '' : 's'
            ));
        }

        Flash::sucesso('Respostas de ' . $benef['nome'] . ' gravadas.');

        // Vai direto para quem ainda não foi respondido: é o gesto seguinte
        // de quem está com a família na frente.
        $proxima = $this->proximaPendente($declaracaoId, $beneficiarioId);

        if ($proxima > 0) {
            $this->redirect('responder', ['declaracao' => $declaracaoId, 'pessoa' => $proxima]);
        }

        $this->redirect('ver', ['id' => $declaracaoId]);
    }

    // ── Apoio ─────────────────────────────────────────────────────────────

    /** Carrega a declaração com as pessoas e as respostas, ou volta para a lista. */
    private function carregar(int $id): array
    {
        $d = $this->model->porId($id);

        if (!$d) {
            Flash::erro('Declaração não encontrada.');
            $this->redirect();
        }

        return [
            $d,
            $this->model->pessoasDe((int) $d['contrato_id']),
            $this->model->respostas($id),
        ];
    }

    /** A primeira pessoa depois desta que ainda não respondeu tudo. */
    private function proximaPendente(int $declaracaoId, int $atual): int
    {
        $d = $this->model->porId($declaracaoId);
        $pessoas   = $this->model->pessoasDe((int) $d['contrato_id']);
        $respostas = $this->model->respostas($declaracaoId);
        $resumo    = $this->model->resumo($d, $pessoas, $respostas);

        $passouPeloAtual = false;

        foreach ($resumo as $r) {
            if ($r['id'] === $atual) {
                $passouPeloAtual = true;
                continue;
            }
            if ($passouPeloAtual && $r['faltam'] > 0) {
                return $r['id'];
            }
        }

        // Ninguém depois: procura desde o começo, para o caso de ter sido
        // preenchido fora de ordem.
        foreach ($resumo as $r) {
            if ($r['id'] !== $atual && $r['faltam'] > 0) {
                return $r['id'];
            }
        }

        return 0;
    }

    private function formulario(array $d, array $erros = []): void
    {
        $contratoId = (int) ($d['contrato_id'] ?? 0);
        $cadastro   = new CadastroModel();

        View::render('declaracoes', 'form', [
            'd'         => $d,
            'erros'     => $erros,
            'contratos' => $this->model->contratosSemDeclaracao(),
            'contrato'  => $contratoId > 0 ? $cadastro->contratoPorId($contratoId) : null,
            'pessoas'   => $contratoId > 0 ? $this->model->pessoasDe($contratoId) : [],
        ]);
    }

    private function doPost(): array
    {
        return [
            'contrato_id'        => (int) ($_POST['contrato_id'] ?? 0),
            'data_preenchimento' => $this->data((string) ($_POST['data_preenchimento'] ?? '')),
            'data_contratacao'   => $this->data((string) ($_POST['data_contratacao'] ?? '')),
            'orientacao'         => (string) ($_POST['orientacao'] ?? 'dispensa'),
            'medico_nome'        => trim((string) ($_POST['medico_nome'] ?? '')),
            'medico_crm'         => trim((string) ($_POST['medico_crm'] ?? '')),
            'assinante_nome'     => trim((string) ($_POST['assinante_nome'] ?? '')),
            'assinante_cpf'      => preg_replace('/\D/', '', (string) ($_POST['assinante_cpf'] ?? '')) ?? '',
            'data_assinatura'    => $this->data((string) ($_POST['data_assinatura'] ?? '')),
            'status'             => (string) ($_POST['status'] ?? 'em_preenchimento'),
            'observacao'         => trim((string) ($_POST['observacao'] ?? '')),
        ];
    }

    private function validar(array $d, int $id): array
    {
        $erros = [];

        if ($d['contrato_id'] <= 0) {
            $erros['contrato_id'] = 'Escolha o contrato da declaração.';
        } elseif ($id === 0) {
            $jaTem = $this->model->doContrato($d['contrato_id']);
            if ($jaTem) {
                $erros['contrato_id'] = 'Este contrato já tem declaração de saúde.';
                $erros['_link'] = url('declaracoes', 'ver', ['id' => (int) $jaTem['id']]);
            }
        }

        if (!$d['data_preenchimento']) {
            $erros['data_preenchimento'] = 'Informe a data do preenchimento.';
        }

        if (!isset(DeclaracaoModel::ORIENTACOES[$d['orientacao']])) {
            $erros['orientacao'] = 'Escolha como foi a orientação médica.';
        }

        // A ANS exige o registro de quem orientou quando houve orientação.
        if ($d['orientacao'] !== 'dispensa' && $d['medico_nome'] === '') {
            $erros['medico_nome'] = 'Informe o médico que fez a orientação.';
        }

        if ($d['assinante_cpf'] !== '' && !cpf_valido($d['assinante_cpf'])) {
            $erros['assinante_cpf'] = 'CPF inválido. Confira os números ou deixe em branco.';
        }

        if (!isset(DeclaracaoModel::STATUS[$d['status']])) {
            $erros['status'] = 'Situação inválida.';
        }

        return $erros;
    }

    /** O que merece alerta depois de gravar, sem impedir a gravação. */
    private function avisos(array $d, int $id): void
    {
        if ($d['status'] !== 'concluida') {
            return;
        }

        $decl      = $this->model->porId($id);
        $pessoas   = $this->model->pessoasDe((int) $d['contrato_id']);
        $respostas = $this->model->respostas($id);
        $resumo    = $this->model->resumo($decl, $pessoas, $respostas);

        $incompletas = array_filter($resumo, fn($r) => $r['faltam'] > 0);

        if ($incompletas) {
            $nomes = array_map(fn($r) => $r['nome'], $incompletas);
            Flash::aviso('Marcada como concluída, mas o questionário de '
                . implode(', ', $nomes) . ' está incompleto. Gravei assim mesmo —'
                . ' só confira antes de colher a assinatura.');
        }

        if (!$d['data_contratacao']) {
            Flash::aviso('Sem a data de contratação, o sistema não consegue calcular'
                . ' até quando corre a Cobertura Parcial Temporária de quem declarou doença.');
        }

        if (!$d['data_assinatura']) {
            Flash::aviso('Declaração concluída sem data de assinatura. A declaração só'
                . ' vale assinada pelo contratante ou representante legal.');
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
}
