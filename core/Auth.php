<?php
declare(strict_types=1);

/**
 * Sessão, autenticação e permissões.
 *
 * Diferença para a v1: as permissões são carregadas UMA vez no login e
 * guardadas na sessão. A sidebar deixa de bater no banco a cada link.
 */
class Auth
{
    private const CHAVE_PERM = 'usuario_permissoes';
    private const CHAVE_PERM_EM = 'usuario_permissoes_em';

    /** Segundos que o cache de permissões vale antes de ser relido do banco. */
    private const TTL_PERM = 60;

    public static function login(array $usuario): void
    {
        // Evita fixação de sessão: id novo antes de gravar os dados
        session_regenerate_id(true);
        Csrf::rotacionar();

        $_SESSION['usuario_id']    = (int) $usuario['id'];
        $_SESSION['usuario_nome']  = (string) $usuario['nome'];
        $_SESSION['usuario_cpf']   = (string) $usuario['cpf'];
        $_SESSION['usuario_setor'] = (string) ($usuario['setor_nome'] ?? '');
        $_SESSION['usuario_admin'] = !empty($usuario['is_admin']);
        $_SESSION['senha_provisoria'] = !empty($usuario['senha_provisoria']);

        // O filtro que a atendente anterior deixou não é a tela com que a
        // próxima deve começar o dia — e no balcão o computador é o mesmo.
        Navegacao::esquecer();

        self::carregarPermissoes();
    }

    /**
     * A senha atual foi criada por outra pessoa (o administrador) e ainda não
     * foi trocada pelo dono da conta.
     */
    public static function senhaProvisoria(): bool
    {
        return !empty($_SESSION['senha_provisoria']);
    }

    /** Chamado logo depois de a pessoa trocar a própria senha. */
    public static function senhaTrocada(): void
    {
        $_SESSION['senha_provisoria'] = false;
    }

    public static function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires'  => time() - 42000,
                'path'     => $p['path'],
                'domain'   => $p['domain'],
                'secure'   => $p['secure'],
                'httponly' => $p['httponly'],
                'samesite' => $p['samesite'] ?? 'Lax',
            ]);
        }

        session_destroy();
    }

    public static function check(): bool
    {
        return isset($_SESSION['usuario_id']);
    }

    public static function id(): ?int
    {
        return isset($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : null;
    }

    public static function nome(): ?string
    {
        return $_SESSION['usuario_nome'] ?? null;
    }

    public static function setor(): string
    {
        return (string) ($_SESSION['usuario_setor'] ?? '');
    }

    public static function isAdmin(): bool
    {
        return !empty($_SESSION['usuario_admin']);
    }

    public static function iniciais(): string
    {
        $nome = trim((string) self::nome());
        if ($nome === '') {
            return '?';
        }
        $partes = preg_split('/\s+/', $nome) ?: [];
        $primeira = mb_substr($partes[0], 0, 1);
        $ultima   = count($partes) > 1 ? mb_substr((string) end($partes), 0, 1) : '';
        return mb_strtoupper($primeira . $ultima);
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            header('Location: ' . url('usuarios', 'login'));
            exit;
        }

        self::exigirSenhaPropria();
    }

    /**
     * Quem está com senha provisória não passa daqui: qualquer tela do sistema
     * devolve para a troca de senha. Fica no requireLogin/requirePermissao, e
     * não numa checagem espalhada por controller, para não existir um caminho
     * esquecido que deixe entrar.
     */
    private static function exigirSenhaPropria(): void
    {
        if (!self::senhaProvisoria()) {
            return;
        }

        $mod    = (string) ($_GET['mod'] ?? '');
        $action = (string) ($_GET['action'] ?? '');

        // As telas que a pessoa PRECISA alcançar para se livrar do bloqueio.
        $liberado = $mod === 'usuarios'
            && in_array($action, ['minhaConta', 'trocarSenha', 'logout'], true);

        if ($liberado) {
            return;
        }

        Flash::aviso('Sua senha foi criada pelo administrador. Escolha uma senha sua para continuar.');
        header('Location: ' . url('usuarios', 'minhaConta'));
        exit;
    }

    public static function requirePermissao(string $modulo, string $tipo = 'pode_ver'): void
    {
        self::requireLogin();

        if (!self::temPermissao($modulo, $tipo)) {
            Flash::erro('Você não tem permissão para acessar este recurso.');
            header('Location: ' . url('usuarios', 'painel'));
            exit;
        }
    }

    public static function temPermissao(string $modulo, string $tipo = 'pode_ver'): bool
    {
        if (!self::check()) {
            return false;
        }

        // Admin tem acesso irrestrito
        if (self::isAdmin()) {
            return true;
        }

        $tiposValidos = ['pode_ver', 'pode_criar', 'pode_editar', 'pode_excluir'];
        if (!in_array($tipo, $tiposValidos, true)) {
            return false;
        }

        $carregadoEm = (int) ($_SESSION[self::CHAVE_PERM_EM] ?? 0);

        // Recarrega quando não há cache ou quando ele passou do TTL — assim
        // uma mudança feita na matriz de permissões vale em até um minuto,
        // sem voltar a consultar o banco a cada link da sidebar.
        if (!isset($_SESSION[self::CHAVE_PERM]) || (time() - $carregadoEm) > self::TTL_PERM) {
            self::carregarPermissoes();
        }

        return !empty($_SESSION[self::CHAVE_PERM][$modulo][$tipo]);
    }

    /** Lê as permissões do banco e guarda na sessão. */
    public static function carregarPermissoes(): void
    {
        $_SESSION[self::CHAVE_PERM]    = [];
        $_SESSION[self::CHAVE_PERM_EM] = time();

        if (!self::check()) {
            return;
        }

        try {
            $stmt = getPDO()->prepare(
                'SELECT m.slug, p.pode_ver, p.pode_criar, p.pode_editar, p.pode_excluir
                   FROM permissoes p
                   JOIN modulos m ON m.id = p.modulo_id
                  WHERE p.usuario_id = ?'
            );
            $stmt->execute([self::id()]);

            foreach ($stmt->fetchAll() as $row) {
                $_SESSION[self::CHAVE_PERM][$row['slug']] = [
                    'pode_ver'     => (bool) $row['pode_ver'],
                    'pode_criar'   => (bool) $row['pode_criar'],
                    'pode_editar'  => (bool) $row['pode_editar'],
                    'pode_excluir' => (bool) $row['pode_excluir'],
                ];
            }
        } catch (PDOException $e) {
            error_log('Falha ao carregar permissões: ' . $e->getMessage());
        }
    }

    /**
     * Invalida o cache de permissões. Chamado quando a matriz é salva —
     * se o usuário afetado for o da sessão atual, recarrega na hora.
     */
    public static function invalidarCache(?int $usuarioId = null): void
    {
        if ($usuarioId === null || $usuarioId === self::id()) {
            unset($_SESSION[self::CHAVE_PERM], $_SESSION[self::CHAVE_PERM_EM]);
        }
    }

    /** Reflete na sessão uma mudança de is_admin feita sobre o próprio usuário. */
    public static function atualizarAdmin(bool $admin): void
    {
        $_SESSION['usuario_admin'] = $admin;
    }

    // ── Proteção contra força bruta no login ──────────────────────────────

    public static function tentativasRestantes(): int
    {
        $t = (int) ($_SESSION['login_tentativas'] ?? 0);
        return max(0, LOGIN_MAX_TENTATIVAS - $t);
    }

    public static function bloqueadoAte(): int
    {
        return (int) ($_SESSION['login_bloqueio'] ?? 0);
    }

    public static function estaBloqueado(): bool
    {
        return self::bloqueadoAte() > time();
    }

    public static function registrarFalha(): void
    {
        $_SESSION['login_tentativas'] = (int) ($_SESSION['login_tentativas'] ?? 0) + 1;

        if ($_SESSION['login_tentativas'] >= LOGIN_MAX_TENTATIVAS) {
            $_SESSION['login_bloqueio']   = time() + LOGIN_BLOQUEIO_SEG;
            $_SESSION['login_tentativas'] = 0;
        }
    }

    public static function limparFalhas(): void
    {
        unset($_SESSION['login_tentativas'], $_SESSION['login_bloqueio']);
    }
}
