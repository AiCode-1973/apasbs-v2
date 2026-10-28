<?php
declare(strict_types=1);

/**
 * Base comum a todos os controllers.
 *
 * Concentra o que na v1 estava copiado em cada módulo: assertPost(),
 * redirect() e — a novidade — a checagem obrigatória de token CSRF
 * em qualquer requisição que altere dados.
 */
abstract class Controller
{
    /** Slug do módulo, usado no redirect e na auditoria. */
    protected string $modulo = '';

    /**
     * Exige POST + token CSRF válido.
     * Toda action que grava, altera ou apaga passa por aqui.
     */
    protected function exigirPost(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            http_response_code(405);
            header('Allow: POST');
            Flash::erro('Requisição inválida: esta operação exige envio por formulário.');
            $this->redirect();
        }

        // Quando o envio passa de post_max_size, o PHP descarta $_POST inteiro
        // — inclusive o token. Sem este aviso o usuário veria "sessão expirada"
        // e ficaria tentando de novo sem entender o que houve.
        if (empty($_POST) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
            Flash::erro(
                'O envio passou do tamanho máximo aceito pelo servidor ('
                . ini_get('post_max_size') . '). Envie um arquivo menor ou peça '
                . 'ao administrador para aumentar post_max_size e upload_max_filesize no php.ini.'
            );
            $this->redirect();
        }

        Csrf::exigir();
    }

    /**
     * Volta para uma tela do módulo.
     *
     * Sem argumentos — que é o caso de quase toda gravação — volta para a
     * lista como o usuário a deixou: mesmo filtro, mesma busca, mesma
     * página. Antes caía sempre na lista limpa, e quem trabalha uma fila
     * filtrada precisava refazer o filtro a cada registro gravado.
     */
    protected function redirect(string $action = 'lista', array $params = []): never
    {
        $destino = ($action === 'lista' && $params === [])
            ? Navegacao::lista($this->modulo)
            : url($this->modulo, $action, $params);

        header('Location: ' . $destino);
        exit;
    }

    /** Id numérico vindo de GET ou POST. */
    protected function id(string $campo = 'id'): int
    {
        return (int) ($_POST[$campo] ?? $_GET[$campo] ?? 0);
    }

    protected function json(mixed $dados, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    protected function auditar(string $acao, string $descricao, int|string|null $registroId = null): void
    {
        Audit::registrar($this->modulo, $acao, $descricao, $registroId);
    }
}
