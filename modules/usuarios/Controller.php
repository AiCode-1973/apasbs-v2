<?php
declare(strict_types=1);

require_once BASE_PATH . '/modules/usuarios/Model.php';
require_once BASE_PATH . '/modules/usuarios/PainelModel.php';

class UsuariosController extends Controller
{
    protected string $modulo = 'usuarios';

    private UsuarioModel $model;

    public function __construct()
    {
        $this->model = new UsuarioModel();
    }

    // ── Autenticação ──────────────────────────────────────────────────────

    public function login(): void
    {
        if (Auth::check()) {
            header('Location: ' . url('usuarios', 'painel'));
            exit;
        }

        $erro = '';
        $cpf  = '';

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            Csrf::exigir();

            $cpf   = preg_replace('/\D/', '', (string) ($_POST['cpf'] ?? '')) ?? '';
            $senha = (string) ($_POST['senha'] ?? '');

            if (Auth::estaBloqueado()) {
                $minutos = (int) ceil((Auth::bloqueadoAte() - time()) / 60);
                $erro = "Muitas tentativas incorretas. Tente novamente em {$minutos} minuto"
                    . ($minutos > 1 ? 's' : '') . '.';
            } elseif (strlen($cpf) !== 11) {
                Auth::registrarFalha();
                $erro = 'CPF ou senha incorretos.';
            } else {
                $usuario = $this->model->buscarPorCpf($cpf);

                // Hash descartável mantém o tempo de resposta parecido quando o
                // CPF não existe — evita descobrir usuários válidos pelo relógio.
                $hash = $usuario['senha'] ?? '$2y$10$usuarioinexistenteusuarioinexistenteusuarioinexistente12';

                if ($usuario && password_verify($senha, $hash)) {
                    Auth::limparFalhas();
                    Auth::login($usuario);
                    Audit::registrar('usuarios', Audit::ENTROU, 'Entrou no sistema', (int) $usuario['id']);

                    header('Location: ' . url('usuarios', 'painel'));
                    exit;
                }

                Auth::registrarFalha();
                Audit::registrar('usuarios', Audit::NEGADO, 'Tentativa de login com CPF ' . formatar_cpf($cpf), null, null, 'Anônimo');
                $erro = 'CPF ou senha incorretos.';
            }
        }

        View::render('usuarios', 'login', ['erro' => $erro, 'cpf' => $cpf], 'auth');
    }

    public function logout(): void
    {
        // Sair também é uma alteração de estado: exige POST + token
        $this->exigirPost();

        Audit::registrar('usuarios', Audit::SAIU, 'Saiu do sistema');
        Auth::logout();

        header('Location: ' . url('usuarios', 'login'));
        exit;
    }

    public function painel(): void
    {
        Auth::requireLogin();

        $acessiveis = array_filter(
            Modules::bySlugs(),
            fn($slug) => Auth::temPermissao($slug),
            ARRAY_FILTER_USE_KEY
        );

        $painel   = new PainelModel();
        $verCirur = Auth::temPermissao('cirurgias');

        View::render('usuarios', 'painel', [
            'modulos'        => $acessiveis,
            'contadores'     => $painel->contadores(),
            'cirurgiasMes'   => $verCirur ? $painel->cirurgiasNoMes() : 0,
            'ultimas'        => $verCirur ? $painel->ultimasCirurgias(6) : [],
            'porEspec'       => $verCirur ? $painel->porEspecialidade(6) : [],
            'atividade'      => Auth::temPermissao('auditoria') ? $painel->atividadeRecente(7) : [],
        ]);
    }

    // ── Minha conta ───────────────────────────────────────────────────────

    /**
     * A própria conta: dados de leitura e a troca de senha.
     *
     * Não pede permissão de módulo de propósito — trocar a própria senha não
     * é privilégio, é a única forma de a pessoa deixar de usar a senha que
     * outra pessoa digitou por ela.
     */
    public function minhaConta(): void
    {
        Auth::requireLogin();

        $usuario = $this->model->buscarPorId((int) Auth::id());

        if (!$usuario) {
            Flash::erro('Não encontrei seu usuário.');
            Auth::logout();
            header('Location: ' . url('usuarios', 'login'));
            exit;
        }

        View::render('usuarios', 'conta', [
            'usuario'    => $usuario,
            'provisoria' => Auth::senhaProvisoria(),
            'erros'      => $_SESSION['conta_erros'] ?? [],
        ]);

        unset($_SESSION['conta_erros']);
    }

    public function trocarSenha(): void
    {
        Auth::requireLogin();
        $this->exigirPost();

        $atual      = (string) ($_POST['senha_atual'] ?? '');
        $nova       = (string) ($_POST['senha_nova'] ?? '');
        $confirmada = (string) ($_POST['senha_confirmacao'] ?? '');

        $erros = [];

        if ($atual === '') {
            $erros['senha_atual'] = 'Informe sua senha atual.';
        }

        if ($nova === '') {
            $erros['senha_nova'] = 'Informe a senha nova.';
        } elseif (($e = self::erroSenha($nova)) !== null) {
            $erros['senha_nova'] = $e;
        } elseif ($nova !== $confirmada) {
            $erros['senha_confirmacao'] = 'A confirmação não é igual à senha nova.';
        } elseif ($this->model->senhaEhAtual((int) Auth::id(), $nova)) {
            // Sem isso, quem é obrigado a trocar repetiria a provisória e o
            // bloqueio sairia sem que a senha mudasse de dono.
            $erros['senha_nova'] = 'A senha nova precisa ser diferente da atual.';
        }

        if (!$erros && !$this->model->trocarSenha((int) Auth::id(), $atual, $nova)) {
            // A senha atual só é conferida depois que a nova passou nas regras:
            // assim uma tentativa de adivinhar a senha atual não recebe, de
            // brinde, a informação de que o resto do formulário estava certo.
            $erros['senha_atual'] = 'Senha atual incorreta.';
            Audit::registrar('usuarios', Audit::NEGADO,
                'Troca de senha recusada: senha atual incorreta', (int) Auth::id());
        }

        if ($erros) {
            $_SESSION['conta_erros'] = $erros;
            $this->redirect('minhaConta');
        }

        Auth::senhaTrocada();

        // A sessão continua valendo, mas com id novo: se alguém tivesse
        // copiado o cookie, a senha nova não o herda.
        session_regenerate_id(true);
        Csrf::rotacionar();

        $this->auditar(Audit::EDITOU, 'Trocou a própria senha', (int) Auth::id());

        Flash::sucesso('Senha alterada. Use a senha nova no próximo acesso.');
        $this->redirect('minhaConta');
    }

    // ── CRUD ──────────────────────────────────────────────────────────────

    public function lista(): void
    {
        Auth::requirePermissao('usuarios', 'pode_ver');

        $busca = trim((string) ($_GET['q'] ?? ''));

        View::render('usuarios', 'lista', [
            'usuarios' => $this->model->listar($busca),
            'busca'    => $busca,
        ]);
    }

    public function novo(): void
    {
        Auth::requirePermissao('usuarios', 'pode_criar');

        View::render('usuarios', 'form', [
            'usuario' => null,
            'setores' => $this->model->listarSetores(),
            'erros'   => [],
        ]);
    }

    public function salvar(): void
    {
        Auth::requirePermissao('usuarios', 'pode_criar');
        $this->exigirPost();

        $dados = $this->sanitizar($_POST);
        $erros = $this->validar($dados);

        if ($erros) {
            View::render('usuarios', 'form', [
                'usuario' => $dados,
                'setores' => $this->model->listarSetores(),
                'erros'   => $erros,
            ]);
            return;
        }

        $id = $this->model->criar($dados);

        $this->auditar(Audit::CRIOU, 'Usuário ' . $dados['nome'] . ' (' . formatar_cpf($dados['cpf']) . ')', $id);
        Flash::sucesso('Usuário cadastrado com sucesso.');
        $this->redirect('lista');
    }

    public function editar(): void
    {
        Auth::requirePermissao('usuarios', 'pode_editar');

        $usuario = $this->model->buscarPorId($this->id());

        if (!$usuario) {
            Flash::erro('Usuário não encontrado.');
            $this->redirect('lista');
        }

        View::render('usuarios', 'form', [
            'usuario' => $usuario,
            'setores' => $this->model->listarSetores(),
            'erros'   => [],
        ]);
    }

    public function atualizar(): void
    {
        Auth::requirePermissao('usuarios', 'pode_editar');
        $this->exigirPost();

        $id      = $this->id();
        $atual   = $this->model->buscarPorId($id);

        if (!$atual) {
            Flash::erro('Usuário não encontrado.');
            $this->redirect('lista');
        }

        $dados = $this->sanitizar($_POST, $atual);
        $erros = $this->validar($dados, $id, $atual);

        if ($erros) {
            $dados['id'] = $id;
            View::render('usuarios', 'form', [
                'usuario' => $dados,
                'setores' => $this->model->listarSetores(),
                'erros'   => $erros,
            ]);
            return;
        }

        $this->model->atualizar($id, $dados);

        // Se o usuário mexeu no próprio cadastro, a sessão precisa acompanhar
        if ($id === Auth::id()) {
            $_SESSION['usuario_nome'] = $dados['nome'];
            Auth::atualizarAdmin((bool) $dados['is_admin']);
            Auth::carregarPermissoes();
        }

        $this->auditar(Audit::EDITOU, 'Usuário ' . $dados['nome'] . ' (' . formatar_cpf($dados['cpf']) . ')', $id);
        Flash::sucesso('Usuário atualizado com sucesso.');
        $this->redirect('lista');
    }

    public function excluir(): void
    {
        Auth::requirePermissao('usuarios', 'pode_excluir');
        $this->exigirPost();

        $id      = $this->id();
        $usuario = $this->model->buscarPorId($id);

        if (!$usuario) {
            Flash::erro('Usuário não encontrado.');
            $this->redirect('lista');
        }

        if ($id === Auth::id()) {
            Flash::aviso('Você não pode excluir seu próprio usuário.');
            $this->redirect('lista');
        }

        if (!empty($usuario['is_admin']) && $this->model->outrosAdmins($id) === 0) {
            Flash::aviso('Este é o único administrador ativo do sistema. Promova outro usuário antes de excluí-lo.');
            $this->redirect('lista');
        }

        try {
            $this->model->excluir($id);
        } catch (PDOException $e) {
            error_log('Excluir usuário: ' . $e->getMessage());
            Flash::erro('Não foi possível excluir: existem registros vinculados a este usuário.');
            $this->redirect('lista');
        }

        $this->auditar(Audit::EXCLUIU, 'Usuário ' . $usuario['nome'] . ' (' . formatar_cpf($usuario['cpf']) . ')', $id);
        Flash::sucesso('Usuário excluído.');
        $this->redirect('lista');
    }

    // ── Apoio ─────────────────────────────────────────────────────────────

    private function sanitizar(array $post, array|false $atual = false): array
    {
        // Só um administrador pode conceder ou remover o status de administrador.
        $isAdmin = Auth::isAdmin()
            ? (isset($post['is_admin']) ? 1 : 0)
            : (int) (bool) ($atual['is_admin'] ?? 0);

        return [
            'nome'     => trim((string) ($post['nome'] ?? '')),
            'cpf'      => preg_replace('/\D/', '', (string) ($post['cpf'] ?? '')) ?? '',
            'senha'    => (string) ($post['senha'] ?? ''),
            'setor_id' => (int) ($post['setor_id'] ?? 0),
            'ativo'    => isset($post['ativo']) ? 1 : 0,
            'is_admin' => $isAdmin,
        ];
    }

    private function validar(array $dados, int $id = 0, array|false $atual = false): array
    {
        $erros = [];

        if ($dados['nome'] === '') {
            $erros['nome'] = 'Nome é obrigatório.';
        } elseif (mb_strlen($dados['nome']) > 150) {
            $erros['nome'] = 'Nome deve ter no máximo 150 caracteres.';
        }

        if (strlen($dados['cpf']) !== 11 || !$this->validarCpf($dados['cpf'])) {
            $erros['cpf'] = 'CPF inválido.';
        } elseif ($this->model->cpfExiste($dados['cpf'], $id)) {
            $erros['cpf'] = 'CPF já cadastrado.';
        }

        if ($id === 0 && $dados['senha'] === '') {
            $erros['senha'] = 'Senha é obrigatória.';
        } elseif ($dados['senha'] !== '' && ($e = self::erroSenha($dados['senha'])) !== null) {
            $erros['senha'] = $e;
        }

        if ($dados['setor_id'] === 0) {
            $erros['setor_id'] = 'Setor é obrigatório.';
        }

        // Não deixa o sistema ficar sem nenhum administrador
        if ($atual && !empty($atual['is_admin'])) {
            $perdeuAdmin = empty($dados['is_admin']) || empty($dados['ativo']);
            if ($perdeuAdmin && $this->model->outrosAdmins($id) === 0) {
                $erros['is_admin'] = 'Este é o único administrador ativo. Promova outro usuário antes de alterar este.';
            }
        }

        return $erros;
    }

    /** A conta mora em core/helpers.php — a agenda usa a mesma. */
    /**
     * As regras de força da senha, em um lugar só.
     *
     * O cadastro do administrador e a troca que o usuário faz sozinho chamam
     * este método — se cada tela tivesse a sua cópia, uma delas ficaria para
     * trás na primeira vez que a regra mudasse.
     *
     * Devolve a mensagem do problema, ou null quando a senha serve.
     */
    public static function erroSenha(string $senha): ?string
    {
        if (mb_strlen($senha) < 8) {
            return 'A senha precisa ter no mínimo 8 caracteres.';
        }
        if (!preg_match('/[A-Za-zÀ-ÿ]/', $senha)) {
            return 'A senha precisa conter ao menos uma letra.';
        }
        if (!preg_match('/\d/', $senha)) {
            return 'A senha precisa conter ao menos um número.';
        }
        return null;
    }

    private function validarCpf(string $cpf): bool
    {
        return cpf_valido($cpf);
    }
}
