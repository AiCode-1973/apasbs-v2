<?php
declare(strict_types=1);

require_once BASE_PATH . '/modules/ajuda/Model.php';

class AjudaController extends Controller
{
    protected string $modulo = 'ajuda';

    private ManualModel $model;
    private string      $uploadDir;

    /**
     * MIME → extensão. A extensão gravada em disco vem DAQUI, nunca do nome
     * enviado pelo navegador — assim um arquivo chamado "manual.php" jamais
     * vira um .php dentro de uploads/.
     *
     * Só PDF e imagem de propósito. Formatos do Office chegam ao servidor
     * como "application/zip" e não dá para distinguir um .docx de um .zip
     * com qualquer coisa dentro — exporte o documento para PDF antes.
     */
    private const EXTENSOES = [
        'application/pdf' => 'pdf',
        'image/jpeg'      => 'jpg',
        'image/png'       => 'png',
        'image/webp'      => 'webp',
    ];

    private const MAX_TAMANHO = 25 * 1024 * 1024; // 25 MB por manual

    /** O que o servidor realmente aceita — o php.ini costuma ser mais apertado. */
    public static function limite(): int
    {
        return limite_upload(self::MAX_TAMANHO);
    }

    public function __construct()
    {
        $this->model     = new ManualModel();
        $this->uploadDir = BASE_PATH . '/uploads/ajuda/';
    }

    // ── Biblioteca ────────────────────────────────────────────────────────

    public function lista(): void
    {
        Auth::requirePermissao('ajuda', 'pode_ver');

        $busca    = trim((string) ($_GET['q'] ?? ''));
        $gerencia = Auth::temPermissao('ajuda', 'pode_criar')
                 || Auth::temPermissao('ajuda', 'pode_editar');

        // Quem só lê não vê manual desativado
        $manuais = $this->model->listar($busca, !$gerencia);

        $porCategoria = [];
        foreach ($manuais as $m) {
            $porCategoria[$m['categoria']][] = $m;
        }

        View::render('ajuda', 'lista', [
            'porCategoria' => $porCategoria,
            'total'        => count($manuais),
            'busca'        => $busca,
            'gerencia'     => $gerencia,
        ]);
    }

    /** Entrega o arquivo: abre no navegador ou baixa com ?baixar=1 */
    public function arquivo(): void
    {
        Auth::requirePermissao('ajuda', 'pode_ver');

        $manual = $this->model->buscarPorId($this->id());

        if (!$manual || (!$manual['ativo'] && !Auth::temPermissao('ajuda', 'pode_editar'))) {
            http_response_code(404);
            exit('Manual não encontrado.');
        }

        // basename + realpath: nem que o nome no banco fosse adulterado a
        // leitura escaparia de uploads/ajuda.
        $real = realpath($this->uploadDir . basename((string) $manual['nome_arq']));
        $raiz = realpath($this->uploadDir);

        if ($real === false || $raiz === false || !str_starts_with($real, $raiz)) {
            http_response_code(404);
            exit('Arquivo não encontrado no servidor.');
        }

        $mime = (string) $manual['mime'];
        if (!isset(self::EXTENSOES[$mime])) {
            $mime = 'application/octet-stream';
        }

        $baixar  = ($_GET['baixar'] ?? '') === '1' || $mime === 'application/octet-stream';
        $nomeSeg = str_replace(['"', "\r", "\n"], '', (string) $manual['nome_orig']);

        $this->auditar(Audit::BAIXOU, 'Manual "' . $manual['titulo'] . '"', (int) $manual['id']);

        header_remove('Content-Security-Policy');
        header('Content-Type: ' . $mime);
        header('X-Content-Type-Options: nosniff');
        header('Content-Security-Policy: default-src \'none\'; sandbox');
        header('Content-Disposition: ' . ($baixar ? 'attachment' : 'inline')
            . '; filename="' . $nomeSeg . '"; '
            . "filename*=UTF-8''" . rawurlencode((string) $manual['nome_orig']));
        header('Content-Length: ' . (string) filesize($real));
        header('Cache-Control: private, max-age=0, must-revalidate');

        readfile($real);
        exit;
    }

    // ── Publicação ────────────────────────────────────────────────────────

    public function novo(): void
    {
        Auth::requirePermissao('ajuda', 'pode_criar');

        View::render('ajuda', 'form', [
            'manual'     => ['categoria' => 'Geral', 'ordem' => 0],
            'erros'      => [],
            'categorias' => $this->model->categorias(),
            'limite'     => self::limite(),
        ]);
    }

    public function salvar(): void
    {
        Auth::requirePermissao('ajuda', 'pode_criar');
        $this->exigirPost();

        $dados = $this->sanitizar($_POST, true);
        $erros = $this->validar($dados);

        $arquivo = null;

        if (!$erros) {
            $arquivo = $this->receberArquivo($_FILES['arquivo'] ?? null, $erros, true);
        }

        if ($erros) {
            View::render('ajuda', 'form', [
                'manual'     => $dados,
                'erros'      => $erros,
                'categorias' => $this->model->categorias(),
                'limite'     => self::limite(),
            ]);
            return;
        }

        if ($dados['ordem'] === 0) {
            $dados['ordem'] = $this->model->proximaOrdem($dados['categoria']);
        }

        $id = $this->model->criar($dados + $arquivo);

        $this->auditar(Audit::CRIOU, 'Manual "' . $dados['titulo'] . '"', $id);
        Flash::sucesso('Manual publicado. Já aparece para quem tem acesso à Ajuda.');
        $this->redirect();
    }

    public function editar(): void
    {
        Auth::requirePermissao('ajuda', 'pode_editar');

        $manual = $this->model->buscarPorId($this->id());

        if (!$manual) {
            Flash::erro('Manual não encontrado.');
            $this->redirect();
        }

        View::render('ajuda', 'form', [
            'manual'     => $manual,
            'erros'      => [],
            'categorias' => $this->model->categorias(),
            'limite'     => self::limite(),
        ]);
    }

    public function atualizar(): void
    {
        Auth::requirePermissao('ajuda', 'pode_editar');
        $this->exigirPost();

        $id     = $this->id();
        $manual = $this->model->buscarPorId($id);

        if (!$manual) {
            Flash::erro('Manual não encontrado.');
            $this->redirect();
        }

        $dados = $this->sanitizar($_POST);
        $erros = $this->validar($dados);

        // Arquivo novo é opcional na edição
        $arquivo = $this->receberArquivo($_FILES['arquivo'] ?? null, $erros, false);

        if ($erros) {
            $dados['id']        = $id;
            $dados['nome_orig'] = $manual['nome_orig'];
            $dados['tamanho']   = $manual['tamanho'];
            $dados['mime']      = $manual['mime'];
            View::render('ajuda', 'form', [
                'manual'     => $dados,
                'erros'      => $erros,
                'categorias' => $this->model->categorias(),
                'limite'     => self::limite(),
            ]);
            return;
        }

        $this->model->atualizar($id, $dados);

        if ($arquivo !== null) {
            $this->apagarArquivo((string) $manual['nome_arq']);
            $this->model->trocarArquivo($id, $arquivo);
        }

        $this->auditar(
            Audit::EDITOU,
            'Manual "' . $dados['titulo'] . '"' . ($arquivo !== null ? ' (arquivo substituído)' : ''),
            $id
        );
        Flash::sucesso('Manual atualizado.');
        $this->redirect();
    }

    public function excluir(): void
    {
        Auth::requirePermissao('ajuda', 'pode_excluir');
        $this->exigirPost();

        $id     = $this->id();
        $manual = $this->model->buscarPorId($id);

        if (!$manual) {
            Flash::erro('Manual não encontrado.');
            $this->redirect();
        }

        $this->model->excluir($id);
        $this->apagarArquivo((string) $manual['nome_arq']);

        $this->auditar(Audit::EXCLUIU, 'Manual "' . $manual['titulo'] . '"', $id);
        Flash::sucesso('Manual excluído.');
        $this->redirect();
    }

    // ── Apoio ─────────────────────────────────────────────────────────────

    private function sanitizar(array $post, bool $criando = false): array
    {
        $categoria = trim(preg_replace('/\s+/u', ' ', (string) ($post['categoria'] ?? '')) ?? '');

        $ordem = (int) ($post['ordem'] ?? 0);
        $ordem = max(0, min($ordem, 9999));

        return [
            'titulo'    => trim((string) ($post['titulo'] ?? '')),
            'descricao' => mb_substr(trim((string) ($post['descricao'] ?? '')), 0, 400),
            'categoria' => $categoria !== '' ? mb_substr($categoria, 0, 80) : 'Geral',
            'ordem'     => $ordem,
            'ativo'     => $criando ? 1 : (isset($post['ativo']) ? 1 : 0),
        ];
    }

    private function validar(array $dados): array
    {
        $erros = [];

        if ($dados['titulo'] === '') {
            $erros['titulo'] = 'Dê um título ao manual.';
        } elseif (mb_strlen($dados['titulo']) > 150) {
            $erros['titulo'] = 'Título deve ter no máximo 150 caracteres.';
        }

        return $erros;
    }

    /**
     * Grava o arquivo enviado e devolve os dados para o banco.
     *
     * O arquivo precisa ter vindo mesmo por upload, respeitar o limite de
     * tamanho, ter MIME reconhecido pelo conteúdo, e recebe um nome novo,
     * aleatório, com extensão derivada do MIME.
     *
     * @return array|null null quando não veio arquivo (permitido na edição)
     */
    private function receberArquivo(?array $file, array &$erros, bool $obrigatorio): ?array
    {
        if ($file === null || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            if ($obrigatorio) {
                $erros['arquivo'] = 'Escolha o arquivo do manual.';
            }
            return null;
        }

        $erro = (int) $file['error'];

        if ($erro === UPLOAD_ERR_INI_SIZE || $erro === UPLOAD_ERR_FORM_SIZE) {
            $erros['arquivo'] = 'O arquivo passa do limite do servidor ('
                . formatar_tamanho(self::limite()) . '). Envie um menor, ou peça ao '
                . 'administrador para aumentar upload_max_filesize e post_max_size no php.ini.';
            return null;
        }

        if ($erro === UPLOAD_ERR_NO_TMP_DIR || $erro === UPLOAD_ERR_CANT_WRITE) {
            $erros['arquivo'] = 'O servidor não conseguiu gravar o arquivo temporário. '
                . 'Avise o administrador.';
            return null;
        }

        if ($erro !== UPLOAD_ERR_OK) {
            $erros['arquivo'] = 'Falha no envio do arquivo. Tente de novo.';
            return null;
        }

        $tmp = (string) $file['tmp_name'];

        if (!is_uploaded_file($tmp)) {
            $erros['arquivo'] = 'Origem do arquivo inválida.';
            return null;
        }

        $tamanho = (int) $file['size'];

        if ($tamanho <= 0 || $tamanho > self::limite()) {
            $erros['arquivo'] = 'O arquivo precisa ter até ' . formatar_tamanho(self::limite()) . '.';
            return null;
        }

        $mime = $this->detectarMime($tmp);

        if ($mime === null) {
            $erros['arquivo'] = 'Tipo não aceito. Envie um PDF ou uma imagem (PNG, JPG, WEBP).';
            return null;
        }

        if (!is_dir($this->uploadDir) && !mkdir($this->uploadDir, 0755, true) && !is_dir($this->uploadDir)) {
            $erros['arquivo'] = 'Não foi possível criar a pasta uploads/ajuda. Verifique as permissões.';
            return null;
        }

        $nomeArq = bin2hex(random_bytes(16)) . '.' . self::EXTENSOES[$mime];

        if (!move_uploaded_file($tmp, $this->uploadDir . $nomeArq)) {
            $erros['arquivo'] = 'Não foi possível gravar o arquivo no servidor.';
            return null;
        }

        @chmod($this->uploadDir . $nomeArq, 0644);

        return [
            'nome_arq'  => $nomeArq,
            'nome_orig' => mb_substr($this->limparNome((string) $file['name']), 0, 255),
            'mime'      => $mime,
            'tamanho'   => $tamanho,
        ];
    }

    /** Detecta o MIME pelo conteúdo e confirma que está na lista permitida. */
    private function detectarMime(string $caminho): ?string
    {
        $mime = null;

        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $detectado = finfo_file($finfo, $caminho);
                finfo_close($finfo);
                $mime = is_string($detectado) ? $detectado : null;
            }
        }

        if ($mime === null && function_exists('mime_content_type')) {
            $detectado = mime_content_type($caminho);
            $mime = is_string($detectado) ? $detectado : null;
        }

        if ($mime === 'image/jpg') {
            $mime = 'image/jpeg';
        }

        return isset(self::EXTENSOES[$mime]) ? $mime : null;
    }

    private function apagarArquivo(string $nomeArq): void
    {
        $real = realpath($this->uploadDir . basename($nomeArq));
        $raiz = realpath($this->uploadDir);

        if ($real !== false && $raiz !== false && str_starts_with($real, $raiz) && is_file($real)) {
            @unlink($real);
        }
    }

    private function limparNome(string $nome): string
    {
        $nome = basename($nome);
        $nome = preg_replace('/[\x00-\x1F\x7F]/u', '', $nome) ?? '';
        return trim($nome) !== '' ? $nome : 'manual';
    }
}
