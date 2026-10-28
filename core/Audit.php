<?php
declare(strict_types=1);

/**
 * Log de auditoria: quem fez o quê, quando e de onde.
 *
 * Nunca interrompe a operação principal — se a tabela não existir ou
 * o INSERT falhar, o erro vai só para o log do servidor.
 */
class Audit
{
    public const CRIOU     = 'criou';
    public const EDITOU    = 'editou';
    public const EXCLUIU   = 'excluiu';
    public const ENTROU    = 'entrou';
    public const SAIU      = 'saiu';
    public const NEGADO    = 'negado';
    public const BAIXOU    = 'baixou';
    // Ver um registro normalmente não vira log — seria ruído. A exceção é o
    // prontuário de saúde ocupacional: imprimir é o momento em que dado de
    // saúde sai do sistema em papel, e a LGPD pede saber quem o tirou.
    public const IMPRIMIU  = 'imprimiu';

    public static function registrar(
        string $modulo,
        string $acao,
        string $descricao,
        int|string|null $registroId = null,
        ?int $usuarioId = null,
        ?string $usuarioNome = null
    ): void {
        try {
            $stmt = getPDO()->prepare(
                'INSERT INTO auditoria (usuario_id, usuario_nome, modulo, acao, registro_id, descricao, ip, user_agent)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );

            $stmt->execute([
                $usuarioId   ?? Auth::id(),
                mb_substr($usuarioNome ?? (Auth::nome() ?? 'Anônimo'), 0, 150),
                mb_substr($modulo, 0, 50),
                mb_substr($acao, 0, 20),
                $registroId !== null ? mb_substr((string) $registroId, 0, 50) : null,
                mb_substr($descricao, 0, 255),
                mb_substr(self::ip(), 0, 45),
                mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
            ]);
        } catch (Throwable $e) {
            error_log('Audit — ' . $e->getMessage());
        }
    }

    public static function ip(): string
    {
        return (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    }

    /** Rótulo e cor de cada ação, usados na tela de auditoria. */
    public static function estilo(string $acao): array
    {
        return match ($acao) {
            self::CRIOU   => ['Criou',    'emerald'],
            self::EDITOU  => ['Editou',   'sky'],
            self::EXCLUIU => ['Excluiu',  'rose'],
            self::ENTROU  => ['Entrou',   'slate'],
            self::SAIU    => ['Saiu',     'slate'],
            self::BAIXOU  => ['Baixou',   'violet'],
            self::NEGADO  => ['Negado',   'amber'],
            default       => [ucfirst($acao), 'slate'],
        };
    }
}
