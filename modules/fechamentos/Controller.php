<?php
declare(strict_types=1);

require_once BASE_PATH . '/modules/fechamentos/Model.php';

class FechamentosController extends Controller
{
    protected string $modulo = 'fechamentos';

    /** Nada antes disso é lançado — evita erro de digitação no ano. */
    private const ANO_MINIMO = 2000;

    private FechamentoModel $model;

    public function __construct()
    {
        $this->model = new FechamentoModel();
    }

    public function lista(): void
    {
        Auth::requirePermissao('fechamentos', 'pode_ver');

        $ano = (int) ($_GET['ano'] ?? 0);
        $anos = $this->model->anosLancados();

        if ($ano > 0 && !in_array($ano, $anos, true)) {
            $ano = 0;
        }

        View::render('fechamentos', 'lista', [
            'fechamentos' => $this->model->listar($ano),
            'anos'        => $anos,
            'anoFiltro'   => $ano,
            'totalPeriodo'=> $this->model->totalDoAno($ano),
        ]);
    }

    public function novo(): void
    {
        Auth::requirePermissao('fechamentos', 'pode_criar');

        // Sugere o mês anterior ao atual, que é o que normalmente se fecha
        $sugestao = new DateTimeImmutable('first day of last month');

        View::render('fechamentos', 'form', [
            'fechamento'  => [
                'ano' => (int) $sugestao->format('Y'),
                'mes' => (int) $sugestao->format('n'),
            ],
            'erros'       => [],
            'ultimo'      => $this->model->ultimo(),
            'motivos'     => $this->model->motivosDoFormulario(),
            'quantidades' => [],
        ]);
    }

    public function salvar(): void
    {
        Auth::requirePermissao('fechamentos', 'pode_criar');
        $this->exigirPost();

        $dados = $this->sanitizar($_POST);
        $erros = $this->validar($dados);

        if ($erros) {
            View::render('fechamentos', 'form', [
                'fechamento'  => $dados,
                'erros'       => $erros,
                'ultimo'      => $this->model->ultimo(),
                'motivos'     => $this->model->motivosDoFormulario(),
                'quantidades' => $this->quantidadesDoPost(),
            ]);
            return;
        }

        $id = $this->model->criar($dados);
        $this->model->salvarQuantidades($id, $this->quantidadesDoPost());

        $this->auditar(
            Audit::CRIOU,
            'Fechamento de ' . competencia_br($dados['ano'], $dados['mes'])
                . ' — ' . numero($dados['total_atendimentos']) . ' atendimentos',
            $id
        );
        Flash::sucesso('Fechamento de ' . competencia_br($dados['ano'], $dados['mes']) . ' registrado.');
        $this->redirect();
    }

    public function editar(): void
    {
        Auth::requirePermissao('fechamentos', 'pode_editar');

        $fechamento = $this->model->buscarPorId($this->id());

        if (!$fechamento) {
            Flash::erro('Fechamento não encontrado.');
            $this->redirect();
        }

        View::render('fechamentos', 'form', [
            'fechamento'  => $fechamento,
            'erros'       => [],
            'ultimo'      => false,
            'motivos'     => $this->model->motivosDoFormulario((int) $fechamento['id']),
            'quantidades' => $this->model->quantidades((int) $fechamento['id']),
        ]);
    }

    public function atualizar(): void
    {
        Auth::requirePermissao('fechamentos', 'pode_editar');
        $this->exigirPost();

        $id = $this->id();

        if (!$this->model->buscarPorId($id)) {
            Flash::erro('Fechamento não encontrado.');
            $this->redirect();
        }

        $dados = $this->sanitizar($_POST);
        $erros = $this->validar($dados, $id);

        if ($erros) {
            $dados['id'] = $id;
            View::render('fechamentos', 'form', [
                'fechamento'  => $dados,
                'erros'       => $erros,
                'ultimo'      => false,
                'motivos'     => $this->model->motivosDoFormulario($id),
                'quantidades' => $this->quantidadesDoPost(),
            ]);
            return;
        }

        $this->model->atualizar($id, $dados);
        $this->model->salvarQuantidades($id, $this->quantidadesDoPost());

        $this->auditar(
            Audit::EDITOU,
            'Fechamento de ' . competencia_br($dados['ano'], $dados['mes'])
                . ' — ' . numero($dados['total_atendimentos']) . ' atendimentos',
            $id
        );
        Flash::sucesso('Fechamento atualizado.');
        $this->redirect();
    }

    public function excluir(): void
    {
        Auth::requirePermissao('fechamentos', 'pode_excluir');
        $this->exigirPost();

        $id = $this->id();
        $fechamento = $this->model->buscarPorId($id);

        if (!$fechamento) {
            Flash::erro('Fechamento não encontrado.');
            $this->redirect();
        }

        $this->model->excluir($id);

        $this->auditar(
            Audit::EXCLUIU,
            'Fechamento de ' . competencia_br((int) $fechamento['ano'], (int) $fechamento['mes']),
            $id
        );
        Flash::sucesso('Fechamento excluído.');
        $this->redirect();
    }

    // ── Apoio ─────────────────────────────────────────────────────────────

    /** motivos[id] => quantidade, já limpos e sem valor negativo. */
    private function quantidadesDoPost(): array
    {
        $bruto = $_POST['motivos'] ?? [];

        if (!is_array($bruto)) {
            return [];
        }

        $limpo = [];
        foreach ($bruto as $motivoId => $valor) {
            $numero = (int) preg_replace('/\D/', '', (string) $valor);
            if ($numero > 0) {
                $limpo[(int) $motivoId] = min($numero, 9999999);
            }
        }

        return $limpo;
    }

    private function sanitizar(array $post): array
    {
        return [
            'ano'                => (int) ($post['ano'] ?? 0),
            'mes'                => (int) ($post['mes'] ?? 0),
            // Aceita "1.234" e "1234" — o operador costuma colar do relatório
            'total_atendimentos' => (int) preg_replace('/\D/', '', (string) ($post['total_atendimentos'] ?? '')),
            'observacao'         => trim((string) ($post['observacao'] ?? '')),
        ];
    }

    private function validar(array $dados, int $id = 0): array
    {
        $erros = [];
        $hoje  = new DateTimeImmutable('today');

        $anoMaximo = (int) $hoje->format('Y');

        if ($dados['mes'] < 1 || $dados['mes'] > 12) {
            $erros['mes'] = 'Selecione o mês.';
        }

        if ($dados['ano'] < self::ANO_MINIMO || $dados['ano'] > $anoMaximo) {
            $erros['ano'] = 'Ano deve estar entre ' . self::ANO_MINIMO . ' e ' . $anoMaximo . '.';
        }

        // Não deixa fechar mês que ainda não terminou
        if (!isset($erros['mes']) && !isset($erros['ano'])) {
            $competencia = $dados['ano'] * 12 + $dados['mes'];
            $atual       = ((int) $hoje->format('Y')) * 12 + ((int) $hoje->format('n'));

            if ($competencia > $atual) {
                $erros['mes'] = 'Não é possível fechar uma competência futura.';
            } elseif ($this->model->competenciaExiste($dados['ano'], $dados['mes'], $id)) {
                $erros['mes'] = competencia_br($dados['ano'], $dados['mes']) . ' já tem fechamento lançado.';
            }
        }

        if (($_POST['total_atendimentos'] ?? '') === '') {
            $erros['total_atendimentos'] = 'Informe o total de atendimentos.';
        } elseif ($dados['total_atendimentos'] > 9999999) {
            $erros['total_atendimentos'] = 'Valor acima do limite aceito.';
        }

        if (mb_strlen($dados['observacao']) > 500) {
            $erros['observacao'] = 'Observação deve ter no máximo 500 caracteres.';
        }

        return $erros;
    }
}
