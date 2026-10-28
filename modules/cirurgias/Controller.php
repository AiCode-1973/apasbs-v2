<?php
declare(strict_types=1);

require_once BASE_PATH . '/modules/cirurgias/Model.php';
require_once BASE_PATH . '/modules/tuss/Model.php';

class CirurgiasController extends Controller
{
    protected string $modulo = 'cirurgias';

    private CirurgiaModel $model;
    private string        $uploadDir;

    /**
     * MIME → extensão. A extensão gravada em disco é derivada DAQUI,
     * nunca do nome enviado pelo navegador — assim um arquivo chamado
     * "laudo.php" jamais vira um .php dentro de uploads/.
     */
    private const EXTENSOES = [
        'application/pdf' => 'pdf',
        'image/jpeg'      => 'jpg',
        'image/png'       => 'png',
        'image/gif'       => 'gif',
        'image/webp'      => 'webp',
    ];

    private const MAX_TAMANHO = 10 * 1024 * 1024; // 10 MB por arquivo
    private const MAX_ANEXOS  = 20;               // por cirurgia

    public function __construct()
    {
        $this->model     = new CirurgiaModel();
        $this->uploadDir = BASE_PATH . '/uploads/cirurgias/';
    }

    // ── Listagem e leitura ────────────────────────────────────────────────

    public function lista(): void
    {
        Auth::requirePermissao('cirurgias', 'pode_ver');

        $busca = trim((string) ($_GET['q'] ?? ''));
        $esp   = (int) ($_GET['esp'] ?? 0);

        View::render('cirurgias', 'lista', [
            'cirurgias'      => $this->model->listar($busca, $esp),
            'especialidades' => $this->model->listarEspecialidades(),
            'busca'          => $busca,
            'espSelecionada' => $esp,
        ]);
    }

    public function detalhe(): void
    {
        Auth::requirePermissao('cirurgias', 'pode_ver');

        $id       = $this->id();
        $cirurgia = $this->model->buscarPorId($id);

        if (!$cirurgia) {
            Flash::erro('Cirurgia não encontrada.');
            $this->redirect();
        }

        View::render('cirurgias', 'detalhe', [
            'cirurgia' => $cirurgia,
            'anexos'   => $this->model->listarAnexos($id),
        ]);
    }

    // ── Escrita ───────────────────────────────────────────────────────────

    public function novo(): void
    {
        Auth::requirePermissao('cirurgias', 'pode_criar');

        View::render('cirurgias', 'form', [
            'cirurgia'       => null,
            'especialidades' => $this->model->listarEspecialidades(),
            'anexos'         => [],
            'erros'          => [],
        ]);
    }

    public function salvar(): void
    {
        Auth::requirePermissao('cirurgias', 'pode_criar');
        $this->exigirPost();

        $dados = $this->sanitizar($_POST);
        $erros = $this->validar($dados);

        if ($erros) {
            View::render('cirurgias', 'form', [
                'cirurgia'       => $dados,
                'especialidades' => $this->model->listarEspecialidades(),
                'anexos'         => [],
                'erros'          => $erros,
            ]);
            return;
        }

        $id = $this->model->criar($dados);
        $this->processarAnexos($id, $_FILES['anexos'] ?? []);

        $this->auditar(Audit::CRIOU, 'Cirurgia protocolo ' . $dados['protocolo'], $id);
        Flash::sucesso('Cirurgia registrada com sucesso.');
        $this->redirect('detalhe', ['id' => $id]);
    }

    public function editar(): void
    {
        Auth::requirePermissao('cirurgias', 'pode_editar');

        $id       = $this->id();
        $cirurgia = $this->model->buscarPorId($id);

        if (!$cirurgia) {
            Flash::erro('Cirurgia não encontrada.');
            $this->redirect();
        }

        View::render('cirurgias', 'form', [
            'cirurgia'       => $cirurgia,
            'especialidades' => $this->model->listarEspecialidades(),
            'anexos'         => $this->model->listarAnexos($id),
            'erros'          => [],
        ]);
    }

    public function atualizar(): void
    {
        Auth::requirePermissao('cirurgias', 'pode_editar');
        $this->exigirPost();

        $id = $this->id();

        if (!$this->model->buscarPorId($id)) {
            Flash::erro('Cirurgia não encontrada.');
            $this->redirect();
        }

        $dados = $this->sanitizar($_POST);
        $erros = $this->validar($dados, $id);

        if ($erros) {
            $dados['id'] = $id;
            View::render('cirurgias', 'form', [
                'cirurgia'       => $dados,
                'especialidades' => $this->model->listarEspecialidades(),
                'anexos'         => $this->model->listarAnexos($id),
                'erros'          => $erros,
            ]);
            return;
        }

        $this->model->atualizar($id, $dados);
        $this->processarAnexos($id, $_FILES['anexos'] ?? []);

        $this->auditar(Audit::EDITOU, 'Cirurgia protocolo ' . $dados['protocolo'], $id);
        Flash::sucesso('Cirurgia atualizada com sucesso.');
        $this->redirect('detalhe', ['id' => $id]);
    }

    public function excluir(): void
    {
        Auth::requirePermissao('cirurgias', 'pode_excluir');
        $this->exigirPost();

        $id       = $this->id();
        $cirurgia = $this->model->buscarPorId($id);

        if (!$cirurgia) {
            Flash::erro('Cirurgia não encontrada.');
            $this->redirect();
        }

        foreach ($this->model->listarAnexos($id) as $a) {
            $this->removerArquivo((int) $a['cirurgia_id'], (string) $a['nome_arq']);
        }

        $this->model->excluir($id);
        $this->removerPasta($id);

        $this->auditar(Audit::EXCLUIU, 'Cirurgia protocolo ' . $cirurgia['protocolo'], $id);
        Flash::sucesso('Cirurgia excluída.');
        $this->redirect();
    }

    public function excluir_anexo(): void
    {
        Auth::requirePermissao('cirurgias', 'pode_editar');
        $this->exigirPost();

        $id         = $this->id();
        $cirurgiaId = (int) ($_POST['cirurgia_id'] ?? 0);

        $anexo = $this->model->buscarAnexo($id);

        if ($anexo && (int) $anexo['cirurgia_id'] === $cirurgiaId) {
            $this->removerArquivo($cirurgiaId, (string) $anexo['nome_arq']);
            $this->model->excluirAnexo($id);

            $this->auditar(Audit::EXCLUIU, 'Anexo "' . $anexo['nome_orig'] . '" da cirurgia #' . $cirurgiaId, $cirurgiaId);
            Flash::sucesso('Anexo removido.');
        } else {
            Flash::erro('Anexo não encontrado.');
        }

        $this->redirect('detalhe', ['id' => $cirurgiaId]);
    }

    public function download(): void
    {
        Auth::requirePermissao('cirurgias', 'pode_ver');

        $anexo = $this->model->buscarAnexo($this->id());

        if (!$anexo) {
            http_response_code(404);
            exit('Arquivo não encontrado.');
        }

        // basename() + realpath() barram qualquer tentativa de path traversal
        $nomeArq = basename((string) $anexo['nome_arq']);
        $path    = $this->uploadDir . (int) $anexo['cirurgia_id'] . '/' . $nomeArq;
        $real    = realpath($path);
        $raiz    = realpath($this->uploadDir);

        if ($real === false || $raiz === false || !str_starts_with($real, $raiz)) {
            http_response_code(404);
            exit('Arquivo não encontrado no servidor.');
        }

        // Só devolve os tipos que o próprio sistema aceita gravar
        $mime = (string) $anexo['mime'];
        if (!isset(self::EXTENSOES[$mime])) {
            $mime = 'application/octet-stream';
        }

        $baixar   = ($_GET['baixar'] ?? '') === '1' || $mime === 'application/octet-stream';
        $disp     = $baixar ? 'attachment' : 'inline';
        $nomeSeg  = str_replace(['"', "\r", "\n"], '', (string) $anexo['nome_orig']);

        $this->auditar(Audit::BAIXOU, 'Anexo "' . $anexo['nome_orig'] . '"', (int) $anexo['cirurgia_id']);

        header_remove('Content-Security-Policy');
        header('Content-Type: ' . $mime);
        header('X-Content-Type-Options: nosniff');
        header('Content-Security-Policy: default-src \'none\'; sandbox');
        header('Content-Disposition: ' . $disp . '; filename="' . $nomeSeg . '"; '
            . "filename*=UTF-8''" . rawurlencode((string) $anexo['nome_orig']));
        header('Content-Length: ' . (string) filesize($real));
        header('Cache-Control: private, max-age=0, must-revalidate');

        readfile($real);
        exit;
    }

    /** AJAX — autocomplete TUSS (JSON) */
    public function buscar_tuss(): void
    {
        Auth::requirePermissao('cirurgias', 'pode_ver');

        $q = trim((string) ($_GET['q'] ?? ''));

        if (mb_strlen($q) < 2) {
            $this->json([]);
        }

        $this->json((new TussModel())->sugerir($q, 20));
    }

    // ── Apoio ─────────────────────────────────────────────────────────────

    private function sanitizar(array $post): array
    {
        return [
            'protocolo'        => trim((string) ($post['protocolo'] ?? '')),
            'tuss_codigo'      => (int) ($post['tuss_codigo'] ?? 0),
            'tuss_termo'       => trim((string) ($post['tuss_termo'] ?? '')),
            'medico'           => trim((string) ($post['medico'] ?? '')),
            'especialidade_id' => (int) ($post['especialidade_id'] ?? 0),
        ];
    }

    private function validar(array $dados, int $id = 0): array
    {
        $erros = [];

        if ($dados['protocolo'] === '') {
            $erros['protocolo'] = 'Número de protocolo é obrigatório.';
        } elseif (mb_strlen($dados['protocolo']) > 50) {
            $erros['protocolo'] = 'Protocolo deve ter no máximo 50 caracteres.';
        } elseif ($this->model->protocoloExiste($dados['protocolo'], $id)) {
            $erros['protocolo'] = 'Já existe uma cirurgia com este protocolo.';
        }

        if (empty($dados['tuss_codigo'])) {
            $erros['tuss_codigo'] = 'Selecione um procedimento TUSS.';
        } elseif ($dados['tuss_termo'] === '') {
            $erros['tuss_codigo'] = 'Selecione o procedimento na lista de sugestões.';
        }

        if ($dados['medico'] === '') {
            $erros['medico'] = 'Nome do médico é obrigatório.';
        } elseif (mb_strlen($dados['medico']) > 150) {
            $erros['medico'] = 'Nome deve ter no máximo 150 caracteres.';
        }

        if (empty($dados['especialidade_id'])) {
            $erros['especialidade_id'] = 'Selecione uma especialidade.';
        } elseif (!$this->model->especialidadeExiste($dados['especialidade_id'])) {
            $erros['especialidade_id'] = 'Especialidade inválida ou inativa.';
        }

        return $erros;
    }

    /**
     * Grava os anexos enviados.
     *
     * Regras: arquivo precisa ter vindo mesmo por upload, respeitar o limite
     * de tamanho, ter MIME reconhecido pelo conteúdo e receber um nome novo,
     * aleatório, com extensão derivada do MIME.
     */
    private function processarAnexos(int $cirurgiaId, array $files): void
    {
        if (empty($files['name']) || !is_array($files['name'])) {
            return;
        }

        $dir = $this->uploadDir . $cirurgiaId . '/';

        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            Flash::erro('Não foi possível criar a pasta de anexos. Verifique as permissões de uploads/cirurgias.');
            return;
        }

        $jaTem     = $this->model->contarAnexos($cirurgiaId);
        $recusados = [];
        $aceitos   = 0;
        $total     = count($files['name']);

        for ($i = 0; $i < $total; $i++) {
            $nomeEnviado = (string) $files['name'][$i];

            if ($nomeEnviado === '' || (int) $files['error'][$i] === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            if ($jaTem + $aceitos >= self::MAX_ANEXOS) {
                $recusados[] = $nomeEnviado . ' (limite de ' . self::MAX_ANEXOS . ' anexos por cirurgia)';
                continue;
            }

            if ((int) $files['error'][$i] !== UPLOAD_ERR_OK) {
                $recusados[] = $nomeEnviado . ' (falha no envio)';
                continue;
            }

            $tmp = (string) $files['tmp_name'][$i];

            if (!is_uploaded_file($tmp)) {
                $recusados[] = $nomeEnviado . ' (origem inválida)';
                continue;
            }

            $tamanho = (int) $files['size'][$i];

            if ($tamanho <= 0 || $tamanho > self::MAX_TAMANHO) {
                $recusados[] = $nomeEnviado . ' (acima de 10 MB)';
                continue;
            }

            $mime = $this->detectarMime($tmp);

            if ($mime === null) {
                $recusados[] = $nomeEnviado . ' (tipo não permitido)';
                continue;
            }

            // Nome novo: 32 caracteres aleatórios + extensão vinda do MIME
            $nomeArq = bin2hex(random_bytes(16)) . '.' . self::EXTENSOES[$mime];

            if (!move_uploaded_file($tmp, $dir . $nomeArq)) {
                $recusados[] = $nomeEnviado . ' (falha ao gravar)';
                continue;
            }

            @chmod($dir . $nomeArq, 0644);

            $this->model->inserirAnexo($cirurgiaId, [
                'nome_orig' => mb_substr($this->limparNome($nomeEnviado), 0, 255),
                'nome_arq'  => $nomeArq,
                'mime'      => $mime,
                'tamanho'   => $tamanho,
            ]);

            $aceitos++;
        }

        if ($recusados) {
            Flash::aviso('Alguns arquivos não foram anexados: ' . implode('; ', $recusados) . '.');
        }
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

    /** Guarda o nome original de forma legível, sem caracteres de controle. */
    private function limparNome(string $nome): string
    {
        $nome = basename($nome);
        $nome = preg_replace('/[\x00-\x1F\x7F]/u', '', $nome) ?? '';
        return trim($nome) !== '' ? $nome : 'arquivo';
    }

    private function removerArquivo(int $cirurgiaId, string $nomeArq): void
    {
        $path = $this->uploadDir . $cirurgiaId . '/' . basename($nomeArq);

        if (is_file($path)) {
            @unlink($path);
        }
    }

    private function removerPasta(int $cirurgiaId): void
    {
        $dir = $this->uploadDir . $cirurgiaId;

        if (is_dir($dir) && count((array) scandir($dir)) <= 2) {
            @rmdir($dir);
        }
    }
}
