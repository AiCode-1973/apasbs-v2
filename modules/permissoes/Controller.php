<?php
declare(strict_types=1);

require_once BASE_PATH . '/modules/permissoes/Model.php';

class PermissoesController extends Controller
{
    protected string $modulo = 'permissoes';

    private PermissaoModel $model;

    public function __construct()
    {
        $this->model = new PermissaoModel();
    }

    public function gerenciar(): void
    {
        Auth::requirePermissao('permissoes', 'pode_ver');

        $usuarioId  = (int) ($_GET['usuario_id'] ?? 0);
        $usuario    = $usuarioId > 0 ? $this->model->buscarUsuario($usuarioId) : false;
        $permissoes = $usuario ? $this->model->buscarPermissoesUsuario($usuarioId) : [];

        View::render('permissoes', 'gerenciar', [
            'usuarios'    => $this->model->listarUsuarios(),
            'modulos'     => $this->model->listarModulos(),
            'permissoes'  => $permissoes,
            'usuarioId'   => $usuario ? $usuarioId : 0,
            'usuarioAlvo' => $usuario ?: null,
        ]);
    }

    public function salvar(): void
    {
        Auth::requirePermissao('permissoes', 'pode_editar');
        $this->exigirPost();

        $usuarioId  = (int) ($_POST['usuario_id'] ?? 0);
        $permissoes = is_array($_POST['permissoes'] ?? null) ? $_POST['permissoes'] : [];

        $usuario = $usuarioId > 0 ? $this->model->buscarUsuario($usuarioId) : false;

        if (!$usuario) {
            Flash::erro('Selecione um usuário válido.');
            $this->redirect('gerenciar');
        }

        try {
            $this->model->salvar($usuarioId, $permissoes);
        } catch (Throwable $e) {
            error_log('Salvar permissões: ' . $e->getMessage());
            Flash::erro('Não foi possível salvar as permissões.');
            $this->redirect('gerenciar', ['usuario_id' => $usuarioId]);
        }

        // Se o alvo é o próprio usuário logado, a sessão precisa refletir na hora
        Auth::invalidarCache($usuarioId);

        $this->auditar(Audit::EDITOU, 'Permissões de ' . $usuario['nome'], $usuarioId);
        Flash::sucesso('Permissões de ' . $usuario['nome'] . ' salvas com sucesso.');
        $this->redirect('gerenciar', ['usuario_id' => $usuarioId]);
    }
}
