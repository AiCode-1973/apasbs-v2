<?php
declare(strict_types=1);

require_once BASE_PATH . '/modules/autorizacoes/Model.php';

class AutorizacoesController extends Controller
{
    protected string $modulo = 'autorizacoes';

    private AutorizacaoModel $model;
    private string           $uploadDir;

    /**
     * Os tipos que o sistema aceita, e a extensão de cada um.
     *
     * Esta lista é a porteira: o arquivo entra pelo que ele É, detectado no
     * conteúdo, e não pelo nome que o navegador mandou — um "guia.php" é
     * recusado aqui, não renomeado adiante. A extensão sai daqui também, e
     * nunca do nome enviado.
     *
     * Hoje o documento vai para o banco e não existe mais arquivo nenhum na
     * pasta, mas a regra continua valendo por dois motivos: é esta lista que
     * decide o Content-Type com que o documento é devolvido ao navegador, e é
     * ela que nomeia o arquivo quando ele sai do banco.
     */
    private const EXTENSOES = [
        'application/pdf' => 'pdf',
        'image/jpeg'      => 'jpg',
        'image/png'       => 'png',
        'image/webp'      => 'webp',
        'image/gif'       => 'gif',
        // Vários portais de convênio entregam a guia como página salva. Entra
        // pelo conteúdo, como os outros: `.htm` e `.html` chegam os dois como
        // text/html e são gravados com a mesma extensão — o nome que a pessoa
        // enviou continua guardado à parte, para aparecer na tela.
        'text/html'       => 'html',
    ];

    private const MAX_TAMANHO = 10 * 1024 * 1024; // 10 MB por arquivo
    private const MAX_ANEXOS  = 8;                // por tipo, em cada autorização

    public function __construct()
    {
        $this->model     = new AutorizacaoModel();
        $this->uploadDir = BASE_PATH . '/uploads/autorizacoes/';
    }

    // ── Lista ─────────────────────────────────────────────────────────────

    public function lista(): void
    {
        Auth::requirePermissao('autorizacoes', 'pode_ver');

        $filtro = [
            'busca'           => trim((string) ($_GET['q'] ?? '')),
            'status'          => (string) ($_GET['status'] ?? ''),
            'convenio_id'     => (int) ($_GET['convenio'] ?? 0),
            'procedimento_id' => (int) ($_GET['procedimento'] ?? 0),
            'de'              => $this->data((string) ($_GET['de'] ?? '')),
            'ate'             => $this->data((string) ($_GET['ate'] ?? '')),
            'em_aberto'       => !empty($_GET['aberto']),
            'parado'          => !empty($_GET['parado']),
        ];

        $porPagina = Paginacao::porPagina(50);
        $total     = $this->model->contar($filtro);
        $paginas   = max(1, (int) ceil($total / $porPagina));
        $pagina    = min(max(1, (int) ($_GET['pagina'] ?? 1)), $paginas);

        $autorizacoes = $this->model->listar($filtro, $porPagina, ($pagina - 1) * $porPagina);

        View::render('autorizacoes', 'lista', [
            'autorizacoes'  => $autorizacoes,
            // Quem já tem guia e quem já tem pedido, numa consulta só para a
            // página inteira — uma por linha encheria o banco de ida e volta.
            'documentos'    => $this->model->anexosDasAutorizacoes(array_column($autorizacoes, 'id')),
            'total'         => $total,
            'pagina'        => $pagina,
            'porPagina'     => $porPagina,
            'contagem'      => $this->model->porStatus(),
            'paradas'       => $this->model->paradas(),
            'convenios'     => $this->model->convenios(),
            'procedimentos' => $this->model->procedimentos(),
            'filtro'        => $filtro,
        ]);
    }

    // ── Formulário ────────────────────────────────────────────────────────

    public function novo(): void
    {
        Auth::requirePermissao('autorizacoes', 'pode_criar');
        $this->formulario([
            'id'               => 0,
            'data_solicitacao' => date('Y-m-d'),
            'status'           => 'aguardando',
        ]);
    }

    public function editar(): void
    {
        Auth::requirePermissao('autorizacoes', 'pode_editar');

        $registro = $this->model->buscarPorId($this->id());
        if (!$registro) {
            Flash::erro('Autorização não encontrada.');
            $this->redirect();
        }

        $this->formulario($registro);
    }

    private function formulario(array $registro, array $erros = []): void
    {
        $id = (int) ($registro['id'] ?? 0);

        View::render('autorizacoes', 'form', [
            'registro'      => $registro,
            'erros'         => $erros,
            'convenios'     => $this->model->convenios(),
            'procedimentos' => $this->model->procedimentos(),
            // Documento só existe depois que a autorização existe: é dela que
            // o arquivo depende para ter onde morar.
            'anexos'        => $id > 0 ? $this->marcarPerdidos($this->model->anexos($id)) : [],
            // O limite vai para a tela em vez de estar escrito nela: desde que
            // o documento mora no banco, quem decide o teto é o servidor, e
            // uma tela que promete 10 MB num servidor que aceita 768 KB faz a
            // pessoa descobrir o limite depois de esperar o arquivo subir.
            'limite_anexo'    => $this->limitePorArquivo(),
            'limite_anexo_br' => $this->limiteBr(),
        ]);
    }

    public function salvar(): void
    {
        $id = $this->id();
        Auth::requirePermissao('autorizacoes', $id > 0 ? 'pode_editar' : 'pode_criar');
        $this->exigirPost();

        $dados = $this->doPost();
        $erros = $this->validar($dados);

        // Numa autorização nova, a guia e o pedido podem vir no mesmo envio.
        $enviou = $this->escolheuArquivo($_FILES['doc_guia'] ?? [])
               || $this->escolheuArquivo($_FILES['doc_pedido'] ?? []);

        if ($erros) {
            // O navegador não devolve o que foi escolhido num campo de
            // arquivo. Avisar é o mínimo: sem isso a pessoa corrige o campo,
            // grava e só descobre depois que a guia não subiu.
            if ($enviou) {
                Flash::aviso('Os arquivos precisam ser escolhidos de novo:'
                    . ' o navegador não guarda o que você selecionou quando o'
                    . ' formulário volta com um erro.');
            }

            $this->formulario($dados + ['id' => $id], $erros);
            return;
        }

        // Escreveu o que falta num pedido que ainda espera resposta? Então a
        // situação é essa, não a que o seletor mostrava. Avisa quando muda:
        // a pessoa escolheu uma coisa e vai ver outra na lista, e descobrir
        // isso sozinha é descobrir que o sistema fez algo pelas costas.
        $escolhida       = $dados['status'];
        $dados['status'] = AutorizacaoModel::situacaoComPendencia(
            $escolhida,
            $dados['info_pendente']
        );
        $virouPendente = $dados['status'] !== $escolhida;

        if ($id > 0) {
            $this->model->atualizar($id, $dados);
            $this->auditar(Audit::EDITOU, $this->descricao($dados), $id);
            Flash::sucesso('Autorização atualizada.');
        } else {
            $id = $this->model->criar($dados);
            $this->auditar(Audit::CRIOU, $this->descricao($dados), $id);
            Flash::sucesso('Autorização registrada.');
        }

        // Depois de gravar, porque o arquivo precisa de um id para ter onde
        // morar. Em silêncio quando ninguém escolheu nada.
        if ($enviou) {
            $this->gravarAnexos($id, 'guia',   $_FILES['doc_guia'] ?? [],   false);
            $this->gravarAnexos($id, 'pedido', $_FILES['doc_pedido'] ?? [], false);
        }

        // Pedido depois da solicitação costuma ser ano digitado errado, mas
        // acontece de verdade — o médico reemite o pedido depois que a
        // paciente já pediu. Então avisa e grava; recusar apagaria um caso
        // real que já existia na planilha.
        if ($dados['data_pedido'] && $dados['data_solicitacao']
            && $dados['data_pedido'] > $dados['data_solicitacao']) {
            Flash::aviso('A data do pedido médico (' . data_br($dados['data_pedido'])
                . ') é posterior à da solicitação (' . data_br($dados['data_solicitacao'])
                . '). Gravei assim mesmo — só confira se o ano está certo.');
        }

        if ($virouPendente) {
            Flash::aviso('Como há informações pendentes, a situação ficou como'
                . ' Informações pendentes no lugar de '
                . mb_strtolower(AutorizacaoModel::STATUS[$escolhida])
                . '. Apague o que está pendente para escolher outra.');
        }

        // Uma pendência num pedido que o convênio já respondeu não muda a
        // situação — mas quem escreveu precisa saber disso, senão fica
        // esperando um selo que não vai aparecer.
        if ($dados['info_pendente'] !== ''
            && !in_array($dados['status'], AutorizacaoModel::ANTES_DA_RESPOSTA, true)) {
            Flash::aviso('O convênio já respondeu, então a situação continua '
                . mb_strtolower(AutorizacaoModel::STATUS[$dados['status']])
                . '. O que está pendente aparece na lista, abaixo do selo.');
        }

        if ($dados['status'] === 'agendado' && !$dados['data_agendada']) {
            Flash::aviso('Marcado como Para Autorizar sem a data do exame. Gravei assim'
                . ' mesmo, mas a lista não vai saber dizer para quando.');
        }

        // Autorizado sem a data: não trava, porque quem registra às pressas
        // nem sempre tem o dia em mãos. Só lembra que falta.
        if (in_array($dados['status'], ['autorizado', 'agendado'], true)
            && !$dados['data_autorizacao']) {
            Flash::aviso('Falta a data em que o convênio autorizou. Dá para preencher depois,'
                . ' abrindo a autorização.');
        }

        // Autorizado sem a guia anexada: o convênio responde por telefone e a
        // imagem chega depois, então não trava. Mas a cobrança fica visível,
        // aqui e na lista — papel que ninguém pediu é papel que some.
        if (in_array($dados['status'], ['autorizado', 'agendado'], true)
            && $this->model->contarAnexos($id, 'guia') === 0) {
            Flash::aviso('Falta anexar a imagem da guia autorizada. Dá para anexar depois,'
                . ' abrindo a autorização.');
        }

        // Autorizada antes de ter sido solicitada é quase sempre ano digitado
        // errado — mas a regra é minha, e o dado é de vocês: avisa e grava.
        if ($dados['data_autorizacao'] && $dados['data_solicitacao']
            && $dados['data_autorizacao'] < $dados['data_solicitacao']) {
            Flash::aviso('A data da autorização (' . data_br($dados['data_autorizacao'])
                . ') é anterior à da solicitação (' . data_br($dados['data_solicitacao'])
                . '). Gravei assim mesmo — só confira se o ano está certo.');
        }

        $this->redirect();
    }

    /**
     * Muda a situação direto da lista, sem abrir o formulário — é o gesto
     * mais repetido do dia: o convênio respondeu, marca e segue.
     */
    public function situacao(): void
    {
        Auth::requirePermissao('autorizacoes', 'pode_editar');
        $this->exigirPost();

        $id     = $this->id();
        $antes  = $this->model->buscarPorId($id);
        $status = (string) ($_POST['status'] ?? '');

        if (!$antes) {
            Flash::erro('Autorização não encontrada.');
            $this->redirect();
        }

        if (!isset(AutorizacaoModel::STATUS[$status])) {
            Flash::erro('Situação inválida.');
            $this->redirect();
        }

        $pendencia = trim((string) ($antes['info_pendente'] ?? ''));

        // O atalho não tem onde escrever, e "Informações pendentes" sem dizer
        // o que falta é um registro mudo — a mesma regra do formulário.
        if ($status === 'pendente' && $pendencia === '') {
            Flash::erro('Abra a autorização e escreva o que está pendente.');
            $this->redirect();
        }

        // E a regra inteira, de novo pela mesma função: voltar para
        // "aguardando" um pedido que tem pendência escrita não desfaz a
        // pendência — ela continua lá, e a situação com ela.
        $pedida = $status;
        $status = AutorizacaoModel::situacaoComPendencia($status, $pendencia);

        if ($status !== $pedida) {
            Flash::aviso($antes['paciente'] . ' continua em informações pendentes:'
                . ' ainda há algo escrito como pendente. Abra a autorização para resolver.');
            $this->redirect();
        }

        // Sem data no POST, a que já estava gravada continua valendo: o campo
        // agora é livre e pode ter sido preenchido antes da resposta do
        // convênio. Passar para "Para Autorizar" sem data nenhuma não trava — a
        // lista mostra a falta, e quem está no telefone não perde o atalho.
        $dataAgendada = $this->data((string) ($_POST['data_agendada'] ?? ''));

        if ($status === 'agendado' && !$dataAgendada && empty($antes['data_agendada'])) {
            Flash::aviso('Falta a data do exame. Abra a autorização para informar para quando ficou.');
        }

        $this->model->mudarStatus($id, $status, $dataAgendada, trim((string) ($_POST['motivo_negativa'] ?? '')));

        $this->auditar(Audit::EDITOU, sprintf(
            '%s (%s): %s → %s',
            $antes['paciente'], $antes['procedimento'],
            AutorizacaoModel::STATUS[$antes['status']], AutorizacaoModel::STATUS[$status]
        ), $id);

        // O atalho da lista não pergunta a data da autorização: seriam dois
        // cliques onde o sentido dele é ter um só, e o campo não é
        // obrigatório. A falta fica marcada na própria linha da lista, que
        // leva ao campo sem atrapalhar quem está respondendo a dez convênios
        // seguidos.
        Flash::sucesso($antes['paciente'] . ': ' . mb_strtolower(AutorizacaoModel::STATUS[$status]) . '.'
            . ($status === 'autorizado' && empty($antes['data_autorizacao'])
                ? ' Falta a data em que o convênio autorizou.'
                : ''));

        $this->redirect();
    }

    public function excluir(): void
    {
        Auth::requirePermissao('autorizacoes', 'pode_excluir');
        $this->exigirPost();

        $id       = $this->id();
        $registro = $this->model->buscarPorId($id);

        if (!$registro) {
            Flash::erro('Autorização não encontrada.');
            $this->redirect();
        }

        // O banco apaga as linhas dos anexos sozinho (CASCADE), e com elas os
        // documentos, que agora moram ali. O que o CASCADE não alcança é a
        // pasta antiga: enquanto sobrar arquivo de antes da mudança, ele sai
        // junto — documento de paciente esquecido no servidor é dado guardado
        // sem registro de que existe.
        $this->apagarPasta($id);

        $this->model->excluir($id);
        $this->auditar(Audit::EXCLUIU, $registro['paciente'] . ' — ' . $registro['procedimento'], $id);

        Flash::sucesso('Autorização excluída.');
        $this->redirect();
    }

    // ── Documentos: guia autorizada e pedido médico ───────────────────────

    /**
     * Recebe os arquivos de um tipo só por vez.
     *
     * O formulário da autorização continua sem upload: documento depende de a
     * autorização já existir, e misturar as duas coisas faria perder o que foi
     * digitado quando um arquivo fosse recusado.
     */
    public function anexar(): void
    {
        Auth::requirePermissao('autorizacoes', 'pode_editar');
        $this->exigirPost();

        $id       = (int) ($_POST['autorizacao_id'] ?? 0);
        $tipo     = (string) ($_POST['tipo'] ?? '');
        $registro = $this->model->buscarPorId($id);

        if (!$registro) {
            Flash::erro('Autorização não encontrada.');
            $this->redirect();
        }

        if (!isset(AutorizacaoModel::TIPOS_ANEXO[$tipo])) {
            Flash::erro('Tipo de documento desconhecido.');
            $this->redirect('editar', ['id' => $id]);
        }

        $this->gravarAnexos($id, $tipo, $_FILES['arquivos'] ?? []);
        $this->redirect('editar', ['id' => $id]);
    }

    /** Abre o documento no navegador (ou baixa, com ?baixar=1). */
    public function anexo(): void
    {
        Auth::requirePermissao('autorizacoes', 'pode_ver');

        $anexo = $this->model->anexoPorId($this->id());

        if (!$anexo) {
            http_response_code(404);
            exit('Arquivo não encontrado.');
        }

        // ── Antes de buscar o arquivo: ele mudou? ────────────────────────
        // Um documento anexado nunca é reescrito — para trocar, apaga-se a
        // linha e anexa-se outra. Então id, tamanho e data de envio já
        // identificam esta versão sem precisar ler o conteúdo. Vale a pena:
        // a folha de impressão pede oito imagens de uma vez, e na segunda
        // visita o navegador recebe oito respostas vazias em vez de oito
        // BLOBs vindos do banco.
        $etag = sprintf('"a%d-%d-%s"',
            (int) $anexo['id'],
            (int) $anexo['tamanho'],
            strtotime((string) $anexo['created_at']) ?: 0);

        if (trim((string) ($_SERVER['HTTP_IF_NONE_MATCH'] ?? '')) === $etag) {
            header('ETag: ' . $etag);
            header('Cache-Control: private, max-age=0, must-revalidate');
            http_response_code(304);
            exit;
        }

        // ── O documento ──────────────────────────────────────────────────
        // Mora no banco. A pasta uploads/autorizacoes só é consultada para as
        // linhas antigas, anexadas antes da mudança e ainda não importadas —
        // enquanto o arquivo existir lá, ele continua abrindo normalmente.
        $conteudo = $this->model->conteudoDoAnexo((int) $anexo['id']);

        if ($conteudo === null) {
            $conteudo = $this->conteudoEmDisco($anexo);
        }

        if ($conteudo === null) {
            http_response_code(404);
            exit('Este documento não está mais no servidor. '
                . 'A ficha dele continua aqui, mas o arquivo se perdeu — '
                . 'é preciso pedir outra via e anexar de novo.');
        }

        // Só devolve os tipos que o próprio sistema aceita gravar
        $mime = (string) $anexo['mime'];
        if (!isset(self::EXTENSOES[$mime])) {
            $mime = 'application/octet-stream';
        }

        $baixar  = ($_GET['baixar'] ?? '') === '1' || $mime === 'application/octet-stream';
        $nomeSeg = str_replace(['"', "\r", "\n"], '', (string) $anexo['nome_orig']);

        // Só registra quando alguém abre o documento de propósito. A mesma
        // URL serve as miniaturas da tela e as imagens da folha de impressão,
        // e anotar cada uma encheria a auditoria de "baixou" que ninguém
        // pediu — o que atrapalha justamente quem for procurar lá quem abriu
        // o documento de uma paciente.
        if (($_SERVER['HTTP_SEC_FETCH_DEST'] ?? '') !== 'image') {
            $this->auditar(Audit::BAIXOU, sprintf(
                '%s "%s" da autorização #%d',
                AutorizacaoModel::TIPOS_ANEXO[$anexo['tipo']] ?? 'Documento',
                $anexo['nome_orig'],
                (int) $anexo['autorizacao_id']
            ), (int) $anexo['autorizacao_id']);
        }

        // A página do convênio é documento de fora, e documento de fora não
        // roda nada aqui dentro. O `sandbox` sem nenhuma permissão derruba
        // script — embutido ou de fora —, formulário e acesso à sessão: o
        // arquivo abre numa origem própria, isolada do sistema.
        //
        // Para o HTML, duas frestas, e só estas: estilo embutido, senão a guia
        // chega desmontada, e imagem em data: URI, que é como a página salva
        // carrega o logotipo e o carimbo. Imagem de endereço externo continua
        // barrada de propósito — num documento de paciente, ela avisaria o
        // outro lado toda vez que alguém abrisse o arquivo.
        $csp = $mime === 'text/html'
            ? "default-src 'none'; img-src data:; style-src 'unsafe-inline'; sandbox"
            : "default-src 'none'; sandbox";

        header_remove('Content-Security-Policy');
        header('Content-Type: ' . $mime . ($mime === 'text/html' ? '; charset=UTF-8' : ''));
        header('X-Content-Type-Options: nosniff');
        header('Content-Security-Policy: ' . $csp);
        header('Content-Disposition: ' . ($baixar ? 'attachment' : 'inline')
            . '; filename="' . $nomeSeg . '"; '
            . "filename*=UTF-8''" . rawurlencode((string) $anexo['nome_orig']));
        header('Content-Length: ' . (string) strlen($conteudo));
        header('ETag: ' . $etag);
        header('Cache-Control: private, max-age=0, must-revalidate');

        echo $conteudo;
        exit;
    }

    /**
     * O arquivo de uma linha antiga, se ainda estiver na pasta.
     *
     * Existe para a travessia: enquanto houver anexo não importado, ele
     * continua abrindo. basename() e realpath() seguem barrando qualquer
     * tentativa de subir pastas pelo nome gravado no banco.
     *
     * @param array<string,mixed> $anexo
     */
    private function conteudoEmDisco(array $anexo): ?string
    {
        $path = $this->uploadDir . (int) $anexo['autorizacao_id'] . '/'
              . basename((string) $anexo['nome_arq']);

        $real = realpath($path);
        $raiz = realpath($this->uploadDir);

        if ($real === false || $raiz === false || !str_starts_with($real, $raiz)) {
            return null;
        }

        $conteudo = file_get_contents($real);

        return is_string($conteudo) && $conteudo !== '' ? $conteudo : null;
    }

    /**
     * Marca, em cada anexo, se o documento ainda existe em algum lugar.
     *
     * `no_banco` vem do banco e responde metade da pergunta. A outra metade é
     * a pasta antiga: uma linha de antes da mudança não tem o conteúdo na
     * tabela e mesmo assim abre, enquanto o arquivo estiver lá. Só quando os
     * dois faltam é que o documento se perdeu — e aí a tela precisa dizer
     * isso, em vez de mostrar uma miniatura quebrada e deixar a pessoa
     * achando que é lentidão da internet.
     *
     * Custa um is_file() por anexo sem conteúdo no banco, no máximo oito por
     * tipo, e só na tela de uma autorização — nunca na lista.
     *
     * @param  array<string,array<int,array<string,mixed>>> $porTipo
     * @return array<string,array<int,array<string,mixed>>>
     */
    private function marcarPerdidos(array $porTipo): array
    {
        $raiz = realpath($this->uploadDir);

        foreach ($porTipo as $tipo => $lista) {
            foreach ($lista as $i => $a) {
                if ((int) ($a['no_banco'] ?? 0) === 1) {
                    $porTipo[$tipo][$i]['perdido'] = false;
                    continue;
                }

                $real = realpath(
                    $this->uploadDir . (int) $a['autorizacao_id'] . '/' . basename((string) $a['nome_arq'])
                );

                $porTipo[$tipo][$i]['perdido'] =
                    $real === false || $raiz === false || !str_starts_with($real, $raiz);
            }
        }

        return $porTipo;
    }

    /**
     * Folha para imprimir: os dados do pedido numa folha de rosto e cada
     * imagem na sua, para o papel sair na ordem em que alguém vai arquivar.
     *
     * `tipo` recorta a impressão — só a guia, só o pedido, ou tudo.
     */
    public function imprimir(): void
    {
        Auth::requirePermissao('autorizacoes', 'pode_ver');

        $id       = $this->id();
        $registro = $this->model->buscarPorId($id);

        if (!$registro) {
            Flash::erro('Autorização não encontrada.');
            $this->redirect();
        }

        $tipo   = (string) ($_GET['tipo'] ?? '');
        $anexos = $this->marcarPerdidos($this->model->anexos($id));

        if (isset(AutorizacaoModel::TIPOS_ANEXO[$tipo])) {
            $anexos = [$tipo => $anexos[$tipo]];
        }

        // Documento que se perdeu não vai para o papel: imprimiria uma folha
        // com o retângulo quebrado da imagem, e quem arquivasse depois não
        // saberia se faltou documento ou se a impressora falhou. Some da
        // folha, e a conta de quantos sumiram vai na folha de rosto.
        $perdidos = 0;

        foreach ($anexos as $t => $lista) {
            $anexos[$t] = array_values(array_filter($lista, static function (array $a) use (&$perdidos): bool {
                $perdidos += ($a['perdido'] ?? false) ? 1 : 0;
                return !($a['perdido'] ?? false);
            }));
        }

        $quantos = array_sum(array_map('count', $anexos));

        if ($quantos === 0) {
            Flash::aviso($perdidos > 0
                ? 'Os documentos desta autorização não estão mais no servidor — não há o que imprimir.'
                : 'Esta autorização ainda não tem documento para imprimir.');
            $this->redirect('editar', ['id' => $id]);
        }

        $this->auditar(Audit::IMPRIMIU, sprintf(
            '%s — %s: %s',
            $registro['paciente'],
            $registro['procedimento'],
            isset(AutorizacaoModel::TIPOS_ANEXO[$tipo])
                ? mb_strtolower(AutorizacaoModel::TIPOS_ANEXO[$tipo])
                : 'guia e pedido médico'
        ), $id);

        View::render('autorizacoes', 'imprimir', [
            'registro' => $registro,
            'anexos'   => $anexos,
            'tipo'     => $tipo,
            'perdidos' => $perdidos,
        ], 'limpo');
    }

    public function excluir_anexo(): void
    {
        Auth::requirePermissao('autorizacoes', 'pode_editar');
        $this->exigirPost();

        $anexo = $this->model->anexoPorId($this->id());

        if (!$anexo) {
            Flash::erro('Documento não encontrado.');
            $this->redirect();
        }

        $autorizacaoId = (int) $anexo['autorizacao_id'];

        $this->apagarArquivo($autorizacaoId, (string) $anexo['nome_arq']);
        $this->model->excluirAnexo((int) $anexo['id']);

        $this->auditar(Audit::EXCLUIU, sprintf(
            '%s "%s" da autorização #%d',
            AutorizacaoModel::TIPOS_ANEXO[$anexo['tipo']] ?? 'Documento',
            $anexo['nome_orig'],
            $autorizacaoId
        ), $autorizacaoId);

        Flash::sucesso('Documento removido.');
        $this->redirect('editar', ['id' => $autorizacaoId]);
    }

    /**
     * Grava os arquivos enviados.
     *
     * Cada um precisa ter vindo mesmo por upload, caber no limite, ter o MIME
     * reconhecido pelo conteúdo — não pela extensão do nome — e receber um
     * nome novo, aleatório, com a extensão derivada desse MIME.
     */
    private function gravarAnexos(int $autorizacaoId, string $tipo, array $files, bool $avisarVazio = true): void
    {
        // Vindo junto com o cadastro, campo de arquivo vazio é o normal — não
        // é um aviso. Quando a pessoa aperta "Anexar", aí sim ela esperava
        // mandar alguma coisa.
        if (empty($files['name']) || !is_array($files['name'])
            || count(array_filter($files['name'], static fn ($n) => (string) $n !== '')) === 0) {
            if ($avisarVazio) {
                Flash::aviso('Nenhum arquivo foi escolhido.');
            }
            return;
        }

        $limite    = $this->limitePorArquivo();
        $jaTem     = $this->model->contarAnexos($autorizacaoId, $tipo);
        $recusados = [];
        $aceitos   = 0;

        for ($i = 0, $total = count($files['name']); $i < $total; $i++) {
            $nomeEnviado = (string) $files['name'][$i];

            if ($nomeEnviado === '' || (int) $files['error'][$i] === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            if ($jaTem + $aceitos >= self::MAX_ANEXOS) {
                $recusados[] = $nomeEnviado . ' (limite de ' . self::MAX_ANEXOS . ' por tipo)';
                continue;
            }

            if ((int) $files['error'][$i] !== UPLOAD_ERR_OK) {
                $recusados[] = $nomeEnviado . ((int) $files['error'][$i] === UPLOAD_ERR_INI_SIZE
                    ? ' (maior do que o servidor aceita receber)'
                    : ' (falha no envio)');
                continue;
            }

            $tmp = (string) $files['tmp_name'][$i];

            if (!is_uploaded_file($tmp)) {
                $recusados[] = $nomeEnviado . ' (origem inválida)';
                continue;
            }

            $tamanho = (int) $files['size'][$i];

            if ($tamanho <= 0 || $tamanho > $limite) {
                $recusados[] = $nomeEnviado . ' (acima de ' . $this->limiteBr() . ')';
                continue;
            }

            $mime = $this->detectarMime($tmp);

            if ($mime === null) {
                $recusados[] = $nomeEnviado . ' (aceito só foto JPG, PNG, WEBP, GIF, PDF ou página HTML)';
                continue;
            }

            $conteudo = file_get_contents($tmp);

            if (!is_string($conteudo) || $conteudo === '') {
                $recusados[] = $nomeEnviado . ' (falha ao ler o arquivo enviado)';
                continue;
            }

            // O nome em disco não nomeia mais arquivo nenhum — o documento
            // vai para o banco. Continua sendo gerado porque é por ele que o
            // documento sai daqui no dia em que alguém exportar, e é ele que
            // liga esta linha ao arquivo que ela teve na pasta antiga.
            $nomeArq = bin2hex(random_bytes(16)) . '.' . self::EXTENSOES[$mime];

            try {
                $this->model->inserirAnexo($autorizacaoId, $tipo, [
                    'nome_orig' => mb_substr($this->limparNome($nomeEnviado), 0, 255),
                    'nome_arq'  => $nomeArq,
                    'mime'      => $mime,
                    'tamanho'   => strlen($conteudo),
                    'conteudo'  => $conteudo,
                ]);
            } catch (PDOException $e) {
                // Acontece quando o arquivo passa do que o servidor de banco
                // aceita receber de uma vez. O limite calculado em
                // limitePorArquivo() evita quase sempre; isto é a rede de
                // baixo, para o caso de o servidor ter sido apertado depois.
                error_log('Falha ao gravar anexo no banco: ' . $e->getMessage());
                $recusados[] = $nomeEnviado . ' (o banco recusou um arquivo deste tamanho)';
                continue;
            }

            $aceitos++;
        }

        $rotulo = mb_strtolower(AutorizacaoModel::TIPOS_ANEXO[$tipo]);

        if ($aceitos > 0) {
            $this->auditar(Audit::EDITOU, sprintf(
                '%d arquivo%s de %s na autorização #%d',
                $aceitos, $aceitos === 1 ? '' : 's', $rotulo, $autorizacaoId
            ), $autorizacaoId);

            Flash::sucesso($aceitos === 1
                ? ucfirst($rotulo) . ($tipo === 'guia' ? ' anexada.' : ' anexado.')
                : $aceitos . ' arquivos de ' . $rotulo . ' anexados.');
        }

        if ($recusados) {
            Flash::aviso('Não deu para anexar: ' . implode('; ', $recusados) . '.');
        }
    }

    /**
     * Quanto o sistema aceita por arquivo, de verdade, neste servidor.
     *
     * São 10 MB por regra nossa, mas o arquivo agora viaja dentro do INSERT —
     * e o MySQL recusa pacote maior que `max_allowed_packet`, que em
     * hospedagem compartilhada costuma ser bem menor que isso. Perguntar ao
     * banco custa uma consulta e evita o pior dos avisos: o que só aparece
     * depois que a pessoa esperou o arquivo subir.
     */
    private function limitePorArquivo(): int
    {
        static $limite = null;

        if ($limite !== null) {
            return $limite;
        }

        $limite = self::MAX_TAMANHO;
        $pacote = 0;

        try {
            $linha  = getPDO()->query("SHOW VARIABLES LIKE 'max_allowed_packet'")->fetch();
            $pacote = (int) ($linha['Value'] ?? 0);
        } catch (Throwable $e) {
            // Servidor que não deixa ler a variável: fica o limite nosso, e
            // o erro de gravação em gravarAnexos() vira a rede de baixo.
            error_log('Não consegui ler max_allowed_packet: ' . $e->getMessage());
        }

        // O pacote leva o arquivo, o resto da linha e a sobrecarga do
        // protocolo. 256 KB de folga cobrem isso com sobra sem comer um
        // pedaço que faça falta.
        if ($pacote > 0) {
            $limite = max(0, min($limite, $pacote - 262144));
        }

        return $limite;
    }

    /**
     * O limite por extenso, do jeito que se fala: "10 MB", não "10,0 MB".
     * A tela e o aviso de recusa dizem o mesmo número, vindo daqui.
     */
    private function limiteBr(): string
    {
        return str_replace(',0 ', ' ', formatar_tamanho($this->limitePorArquivo()));
    }

    /** Alguém chegou a escolher algum arquivo neste campo? */
    private function escolheuArquivo(array $files): bool
    {
        if (empty($files['name']) || !is_array($files['name'])) {
            return false;
        }

        foreach ($files['name'] as $nome) {
            if ((string) $nome !== '') {
                return true;
            }
        }

        return false;
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

    /** O nome que a pessoa vê, sem o que atrapalharia o cabeçalho HTTP. */
    private function limparNome(string $nome): string
    {
        $nome = str_replace(["\r", "\n", '"', '\\', '/'], '', basename($nome));
        return trim($nome) !== '' ? trim($nome) : 'documento';
    }

    private function apagarArquivo(int $autorizacaoId, string $nomeArq): void
    {
        $path = $this->uploadDir . $autorizacaoId . '/' . basename($nomeArq);
        $real = realpath($path);
        $raiz = realpath($this->uploadDir);

        if ($real !== false && $raiz !== false && str_starts_with($real, $raiz)) {
            @unlink($real);
        }
    }

    private function apagarPasta(int $autorizacaoId): void
    {
        $dir = realpath($this->uploadDir . $autorizacaoId);
        $raiz = realpath($this->uploadDir);

        if ($dir === false || $raiz === false || !str_starts_with($dir, $raiz) || $dir === $raiz) {
            return;
        }

        foreach (glob($dir . '/*') ?: [] as $arquivo) {
            if (is_file($arquivo)) {
                @unlink($arquivo);
            }
        }

        @rmdir($dir);
    }

    // ── Cadastros de apoio ────────────────────────────────────────────────

    public function convenios(): void
    {
        Auth::requirePermissao('autorizacoes', 'pode_ver');

        View::render('autorizacoes', 'apoio', [
            'cadastro' => 'convenios',
            'titulo'   => 'Convênios',
            'subtitulo'=> 'Os planos que autorizam os exames',
            'itens'    => $this->model->convenios(false),
            'uso'      => $this->model->usoConvenios(),
            'erros'    => $_SESSION['apoio_erros'] ?? [],
            'edicao'   => $this->model->apoioPorId('convenios', (int) ($_GET['id'] ?? 0)) ?: null,
        ]);

        unset($_SESSION['apoio_erros']);
    }

    public function procedimentos(): void
    {
        Auth::requirePermissao('autorizacoes', 'pode_ver');

        View::render('autorizacoes', 'apoio', [
            'cadastro' => 'procedimentos',
            'titulo'   => 'Procedimentos',
            'subtitulo'=> 'Os exames que passam por autorização',
            'itens'    => $this->model->procedimentos(false),
            'uso'      => $this->model->usoProcedimentos(),
            'erros'    => $_SESSION['apoio_erros'] ?? [],
            'edicao'   => $this->model->apoioPorId('procedimentos', (int) ($_GET['id'] ?? 0)) ?: null,
        ]);

        unset($_SESSION['apoio_erros']);
    }

    public function salvarApoio(): void
    {
        Auth::requirePermissao('autorizacoes', 'pode_editar');
        $this->exigirPost();

        $cadastro = (string) ($_POST['cadastro'] ?? '');
        if (!in_array($cadastro, ['convenios', 'procedimentos'], true)) {
            Flash::erro('Cadastro desconhecido.');
            $this->redirect();
        }

        $id = $this->id();
        $d  = [
            'nome'       => trim((string) ($_POST['nome'] ?? '')),
            'telefone'   => trim((string) ($_POST['telefone'] ?? '')),
            'codigo'     => trim((string) ($_POST['codigo'] ?? '')),
            'observacao' => trim((string) ($_POST['observacao'] ?? '')),
            'ativo'      => isset($_POST['ativo']) ? 1 : 0,
        ];

        $erros = [];
        if ($d['nome'] === '') {
            $erros['nome'] = 'O nome é obrigatório.';
        } elseif (mb_strlen($d['nome']) > 120) {
            $erros['nome'] = 'O nome passa de 120 caracteres.';
        } elseif ($this->model->nomeApoioExiste($cadastro, $d['nome'], $id)) {
            $erros['nome'] = 'Já existe um registro com esse nome.';
        }

        if ($erros) {
            $_SESSION['apoio_erros'] = $erros;
            $this->redirect($cadastro, $id > 0 ? ['id' => $id] : []);
        }

        $this->model->salvarApoio($cadastro, $id, $d);
        $this->auditar($id > 0 ? Audit::EDITOU : Audit::CRIOU,
            ucfirst($cadastro) . ': ' . $d['nome'], $id ?: null);

        Flash::sucesso($id > 0 ? 'Registro atualizado.' : 'Registro incluído.');
        $this->redirect($cadastro);
    }

    public function excluirApoio(): void
    {
        Auth::requirePermissao('autorizacoes', 'pode_excluir');
        $this->exigirPost();

        $cadastro = (string) ($_POST['cadastro'] ?? '');
        if (!in_array($cadastro, ['convenios', 'procedimentos'], true)) {
            Flash::erro('Cadastro desconhecido.');
            $this->redirect();
        }

        $id   = $this->id();
        $item = $this->model->apoioPorId($cadastro, $id);

        if (!$item) {
            Flash::erro('Registro não encontrado.');
            $this->redirect($cadastro);
        }

        try {
            $this->model->excluirApoio($cadastro, $id);
        } catch (PDOException $e) {
            // Excluir o convênio de uma autorização antiga apagaria o passado.
            error_log('Excluir apoio: ' . $e->getMessage());
            Flash::aviso('“' . $item['nome'] . '” está em uso por autorizações já registradas. '
                . 'Desmarque “Ativo” para tirá-lo da lista sem apagar o histórico.');
            $this->redirect($cadastro);
        }

        $this->auditar(Audit::EXCLUIU, ucfirst($cadastro) . ': ' . $item['nome'], $id);
        Flash::sucesso('Registro excluído.');
        $this->redirect($cadastro);
    }

    // ── Apoio ─────────────────────────────────────────────────────────────

    private function doPost(): array
    {
        return [
            'data_solicitacao' => $this->data((string) ($_POST['data_solicitacao'] ?? '')),
            'paciente'         => trim((string) ($_POST['paciente'] ?? '')),
            // CPF só com dígitos, como no cadastro de usuários: a pontuação
            // é da tela. Carteirinha vai como veio — cada convênio tem seu
            // formato, e normalizar seria inventar uma regra que eles não seguem.
            'cpf'              => preg_replace('/\D/', '', (string) ($_POST['cpf'] ?? '')) ?? '',
            'carteirinha'      => trim((string) ($_POST['carteirinha'] ?? '')),
            'contato'          => preg_replace('/\D/', '', (string) ($_POST['contato'] ?? '')) ?? '',
            'convenio_id'      => (int) ($_POST['convenio_id'] ?? 0),
            'procedimento_id'  => (int) ($_POST['procedimento_id'] ?? 0),
            'data_pedido'      => $this->data((string) ($_POST['data_pedido'] ?? '')),
            // Digitada à mão depois que o convênio responde: o sistema não
            // supõe "hoje", porque a resposta costuma ser registrada no dia
            // seguinte, e uma data suposta viraria dado errado sem ninguém
            // perceber.
            'data_autorizacao' => $this->data((string) ($_POST['data_autorizacao'] ?? '')),
            'data_agendada'    => $this->data((string) ($_POST['data_agendada'] ?? '')),
            'status'           => (string) ($_POST['status'] ?? 'aguardando'),
            'senha'            => trim((string) ($_POST['senha'] ?? '')),
            'motivo_negativa'  => trim((string) ($_POST['motivo_negativa'] ?? '')),
            'info_pendente'    => trim((string) ($_POST['info_pendente'] ?? '')),
            'observacao'       => trim((string) ($_POST['observacao'] ?? '')),
        ];
    }

    private function validar(array $d): array
    {
        $erros = [];

        if (!$d['data_solicitacao']) {
            $erros['data_solicitacao'] = 'Informe a data da solicitação.';
        }

        if ($d['paciente'] === '') {
            $erros['paciente'] = 'Informe o nome da paciente.';
        } elseif (mb_strlen($d['paciente']) > 150) {
            $erros['paciente'] = 'O nome passa de 150 caracteres.';
        }

        // 10 ou 11 dígitos: fixo com DDD ou celular com o 9.
        if ($d['contato'] !== '' && !in_array(strlen($d['contato']), [10, 11], true)) {
            $erros['contato'] = 'O telefone precisa ter 10 ou 11 dígitos, com o DDD.';
        }

        // CPF não é obrigatório, mas se veio precisa ser um CPF de verdade:
        // guardar um número inválido é pior que não guardar nada — alguém vai
        // usá-lo para achar a paciente no convênio e não vai achar.
        if ($d['cpf'] !== '' && !cpf_valido($d['cpf'])) {
            $erros['cpf'] = 'CPF inválido. Confira os números ou deixe em branco.';
        }

        if ($d['carteirinha'] !== '' && mb_strlen($d['carteirinha']) > 40) {
            $erros['carteirinha'] = 'A carteirinha passa de 40 caracteres.';
        }

        if ($d['convenio_id'] === 0) {
            $erros['convenio_id'] = 'Escolha o convênio.';
        }

        if ($d['procedimento_id'] === 0) {
            $erros['procedimento_id'] = 'Escolha o procedimento.';
        }

        if (!isset(AutorizacaoModel::STATUS[$d['status']])) {
            $erros['status'] = 'Situação inválida.';
        }

        if (mb_strlen($d['info_pendente']) > 400) {
            $erros['info_pendente'] = 'O texto passa de 400 caracteres.';
        }

        // Esta é a outra metade da regra da pendência, e o único lugar em que
        // ela trava: "Informações pendentes" sem dizer o que falta não é
        // situação, é um registro mudo. Quem apaga o que estava pendente está
        // dizendo que resolveu — e então precisa dizer em que pé o pedido
        // ficou, porque só quem apagou sabe.
        if ($d['status'] === 'pendente' && $d['info_pendente'] === '') {
            $erros['info_pendente'] = 'Escreva o que está pendente — ou escolha outra situação,'
                . ' já que não há mais nada faltando.';
        }

        // "Para Autorizar" sem data já foi erro que impedia gravar. Virou aviso: quem
        // registra está com a paciente na linha, e perder o que já digitou
        // por causa de um campo custa mais do que um "marcado" sem dia, que a
        // própria lista denuncia em cinza.

        return $erros;
    }

    /** '' quando vazio ou inválido — nunca uma data inventada. */
    private function data(string $valor): ?string
    {
        $valor = trim($valor);
        if ($valor === '') {
            return null;
        }

        $d = DateTimeImmutable::createFromFormat('Y-m-d', $valor);
        return ($d && $d->format('Y-m-d') === $valor) ? $valor : null;
    }

    private function descricao(array $d): string
    {
        $conv = $this->model->apoioPorId('convenios', $d['convenio_id']);
        $proc = $this->model->apoioPorId('procedimentos', $d['procedimento_id']);

        return sprintf('%s — %s (%s)', $d['paciente'],
            $proc['nome'] ?? '?', $conv['nome'] ?? '?');
    }
}
