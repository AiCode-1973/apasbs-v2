<?php
declare(strict_types=1);

class AutorizacaoModel
{
    /**
     * As situações, na ordem em que acontecem.
     *
     * A chave `agendado` chamava-se "Agendado" na tela e hoje se chama
     * "Para Autorizar". Só o rótulo mudou — a chave continua a mesma no banco,
     * nos filtros e nos links, para não reescrever o histórico nem quebrar
     * endereço que alguém tenha guardado.
     */
    public const STATUS = [
        'aguardando' => 'Aguardando convênio',
        'pendente'   => 'Informações pendentes',
        'autorizado' => 'Autorizado',
        'negado'     => 'Negado',
        'agendado'   => 'Para Autorizar',
    ];

    /**
     * As situações em que o pedido ainda não teve resposta do convênio.
     *
     * É o alcance da pendência: escrever o que falta num pedido que ainda
     * espera resposta muda a situação dele; escrever num pedido já
     * autorizado ou negado não muda nada — o convênio já respondeu, e
     * apagar essa resposta da tela por causa de um bilhete seria perder a
     * informação mais importante da linha.
     */
    public const ANTES_DA_RESPOSTA = ['aguardando', 'agendado', 'pendente'];

    /**
     * A situação que de fato vale, dada a que foi escolhida e o que foi
     * escrito como pendente.
     *
     * Está aqui, e não espalhada pelo Controller, porque o formulário, o
     * atalho da lista e qualquer tela futura precisam chegar ao mesmo
     * resultado. A regra tem duas metades e as duas importam:
     *
     * - com pendência escrita, antes da resposta → "Informações pendentes";
     * - sem pendência escrita, a situação é a que a pessoa escolheu.
     *
     * O caso "pendente sem texto" não é resolvido aqui de propósito: ele é
     * um erro de preenchimento, e quem diz isso é a validação — devolver
     * "aguardando" em silêncio escolheria a situação no lugar da pessoa.
     */
    public static function situacaoComPendencia(string $status, string $pendencia): string
    {
        return $pendencia !== '' && in_array($status, self::ANTES_DA_RESPOSTA, true)
            ? 'pendente'
            : $status;
    }

    /** Dias sem data marcada a partir dos quais a autorização conta como parada. */
    public const DIAS_PARADA = 7;

    /**
     * O que conta como parada, escrito uma vez só: a contagem do aviso e o
     * filtro que ele abre precisam dizer a mesma coisa, ou o número da faixa
     * não bate com a lista que ela mostra.
     *
     * Autorizado e "Para Autorizar" correm o mesmo risco enquanto não houver dia
     * marcado. Negado e aguardando não entram — um não tem o que marcar, o
     * outro ainda espera resposta. A conta parte da data em que o convênio
     * autorizou; sem ela, de quando o registro foi mexido pela última vez.
     */
    private const ONDE_PARADA = "a.status IN ('autorizado','agendado')
                AND a.data_agendada IS NULL
                AND COALESCE(a.data_autorizacao, DATE(a.updated_at)) < DATE_SUB(CURDATE(), INTERVAL ? DAY)";

    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = getPDO();

        self::instalarAnexos($this->pdo);
    }

    // ── A estrutura dos documentos, criada pelo próprio sistema ──────────

    /**
     * A tabela onde os documentos moram, escrita aqui e em nenhum outro
     * lugar.
     *
     * Subir o código e descobrir só depois que falta rodar um script é um
     * passo a mais entre a pessoa e o sistema funcionando — e um passo que
     * só aparece no pior momento, quando alguém está tentando anexar a guia
     * de uma paciente. Então o módulo cria o que precisa, na primeira vez
     * que é aberto.
     *
     * O arquivo em si vai na coluna `conteudo`: MEDIUMBLOB vai até 16 MB e o
     * sistema não aceita anexo acima de 10 MB, então sobra folga. Ela nasce
     * NULL e assim fica nas linhas antigas, cujo arquivo morava na pasta
     * uploads — é por esse NULL que a tela sabe que aquele documento se
     * perdeu.
     */
    private const DDL_ANEXOS = "CREATE TABLE IF NOT EXISTS amb_autorizacao_anexos (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    autorizacao_id INT UNSIGNED NOT NULL,
    tipo ENUM('guia','pedido') NOT NULL,
    nome_orig VARCHAR(255) NOT NULL,
    nome_arq  VARCHAR(80)  NOT NULL,
    mime      VARCHAR(100) NOT NULL,
    tamanho   INT UNSIGNED NOT NULL DEFAULT 0,
    conteudo  MEDIUMBLOB   NULL,
    usuario_id   INT          NULL,
    usuario_nome VARCHAR(150) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_aut_anexo (autorizacao_id, tipo, created_at),
    CONSTRAINT fk_aut_anexo_autorizacao FOREIGN KEY (autorizacao_id)
        REFERENCES amb_autorizacoes(id) ON DELETE CASCADE,
    CONSTRAINT fk_aut_anexo_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    /** O que falta numa instalação que veio de antes do documento ir para o banco. */
    private const DDL_COLUNA_CONTEUDO =
        'ALTER TABLE amb_autorizacao_anexos ADD COLUMN conteudo MEDIUMBLOB NULL AFTER tamanho';

    /**
     * Cria a tabela e a coluna se faltarem. Devolve se a estrutura está de pé.
     *
     * Roda uma vez por sessão, não a cada tela: as duas conferências custam
     * pouco, mas pagá-las em toda requisição seria cobrar de todo dia um
     * problema que acontece uma vez.
     *
     * Falhar aqui não derruba nada. Se o usuário do banco não tiver permissão
     * para criar tabela — acontece em hospedagem compartilhada —, a consulta
     * seguinte encontra a tabela faltando e o sistema mostra a página que diz
     * qual script rodar à mão. É a mesma rede que já existia, intacta.
     */
    public static function instalarAnexos(PDO $pdo): bool
    {
        static $pronto = false;

        if ($pronto || !empty($_SESSION['apasbs_anexos_instalados'])) {
            return true;
        }

        try {
            $pdo->exec(self::DDL_ANEXOS);

            $temColuna = (bool) $pdo->query(
                "SELECT COUNT(*) FROM information_schema.COLUMNS
                  WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME   = 'amb_autorizacao_anexos'
                    AND COLUMN_NAME  = 'conteudo'"
            )->fetchColumn();

            if (!$temColuna) {
                $pdo->exec(self::DDL_COLUNA_CONTEUDO);
                error_log('Autorizações: coluna `conteudo` criada — os documentos passam a ser guardados no banco.');
            }
        } catch (PDOException $e) {
            error_log('Autorizações: não consegui preparar a tabela de documentos — ' . $e->getMessage());
            return false;
        }

        $pronto = true;

        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['apasbs_anexos_instalados'] = true;
        }

        return true;
    }

    // ── Autorizações ─────────────────────────────────────────────────────

    /**
     * O WHERE dos filtros, montado uma vez e reaproveitado pela listagem e
     * pela contagem — assim o total do rodapé nunca discorda da tela.
     */
    private function onde(array $f, array &$params): string
    {
        $w = [];

        if (($f['busca'] ?? '') !== '') {
            $like = '%' . like_escape($f['busca']) . '%';
            // O CPF é guardado só com dígitos: quem digita "123.456" na busca
            // não acharia nada sem esta segunda forma do termo.
            $digitos = preg_replace('/\D/', '', $f['busca']) ?? '';
            $likeCpf = $digitos !== '' ? '%' . like_escape($digitos) . '%' : null;

            $w[] = "(a.paciente LIKE ? ESCAPE '!' OR a.contato LIKE ? ESCAPE '!'
                     OR a.senha LIKE ? ESCAPE '!' OR a.carteirinha LIKE ? ESCAPE '!'"
                 . ($likeCpf !== null ? " OR a.cpf LIKE ? ESCAPE '!'" : '') . ')';

            array_push($params, $like, $like, $like, $like);
            if ($likeCpf !== null) {
                $params[] = $likeCpf;
            }
        }

        if (($f['status'] ?? '') !== '' && isset(self::STATUS[$f['status']])) {
            $w[] = 'a.status = ?';
            $params[] = $f['status'];
        }

        if (!empty($f['convenio_id'])) {
            $w[] = 'a.convenio_id = ?';
            $params[] = (int) $f['convenio_id'];
        }

        if (!empty($f['procedimento_id'])) {
            $w[] = 'a.procedimento_id = ?';
            $params[] = (int) $f['procedimento_id'];
        }

        if (!empty($f['de'])) {
            $w[] = 'a.data_solicitacao >= ?';
            $params[] = $f['de'];
        }

        if (!empty($f['ate'])) {
            $w[] = 'a.data_solicitacao <= ?';
            $params[] = $f['ate'];
        }

        // "Só o que está em aberto": o que ainda depende de alguém. O que está
        // "Para Autorizar" só sai daqui quando ganha o dia — sem data, ele ainda
        // é trabalho de alguém.
        if (!empty($f['em_aberto'])) {
            $w[] = "(a.status IN ('aguardando','pendente','autorizado')
                     OR (a.status = 'agendado' AND a.data_agendada IS NULL))";
        }

        // O recorte exato da faixa âmbar: liberado, sem dia marcado e parado há
        // mais de uma semana. Está aqui, e não só na contagem, porque clicar no
        // aviso tem de abrir exatamente o que ele contou.
        if (!empty($f['parado'])) {
            $w[] = self::ONDE_PARADA;
            $params[] = self::DIAS_PARADA;
        }

        return $w ? ' WHERE ' . implode(' AND ', $w) : '';
    }

    public function listar(array $f, int $limite, int $offset): array
    {
        $params = [];
        $sql = 'SELECT a.*, c.nome AS convenio, p.nome AS procedimento
                  FROM amb_autorizacoes a
                  JOIN amb_convenios c     ON c.id = a.convenio_id
                  JOIN amb_procedimentos p ON p.id = a.procedimento_id'
             . $this->onde($f, $params)
             . ' ORDER BY a.data_solicitacao DESC, a.id DESC
                 LIMIT ? OFFSET ?';

        $stmt = $this->pdo->prepare($sql);

        $i = 1;
        foreach ($params as $v) {
            $stmt->bindValue($i++, $v);
        }

        // Tudo posicional: o PDO recusa misturar `?` com `:nome` na mesma
        // instrução, e os filtros já entram como `?`. LIMIT/OFFSET vão como
        // inteiro porque, com prepares nativos, string quebra no MySQL.
        $stmt->bindValue($i++, $limite, PDO::PARAM_INT);
        $stmt->bindValue($i,   $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function contar(array $f): int
    {
        $params = [];
        $sql = 'SELECT COUNT(*) FROM amb_autorizacoes a' . $this->onde($f, $params);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /** Quantas há em cada situação, para os cartões e os filtros. */
    public function porStatus(): array
    {
        $linhas = $this->pdo->query(
            'SELECT status, COUNT(*) AS total FROM amb_autorizacoes GROUP BY status'
        )->fetchAll();

        $contagem = array_fill_keys(array_keys(self::STATUS), 0);
        foreach ($linhas as $l) {
            $contagem[$l['status']] = (int) $l['total'];
        }
        return $contagem;
    }

    /**
     * Autorizado há mais de N dias e ainda sem data agendada.
     *
     * É o número que a planilha não conseguia dar: autorização tem prazo de
     * validade, e a que ninguém marcou vira retrabalho quando vence.
     */
    public function paradas(int $dias = self::DIAS_PARADA): int
    {
        // Conta a partir da data em que o convênio autorizou, quando ela foi
        // informada. Antes só havia updated_at, que se move a cada edição —
        // corrigir um telefone rejuvenescia uma autorização parada há um mês.
        // Sem a data preenchida, updated_at continua sendo a melhor pista.
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM amb_autorizacoes a WHERE ' . self::ONDE_PARADA
        );
        $stmt->execute([$dias]);
        return (int) $stmt->fetchColumn();
    }

    public function buscarPorId(int $id): array|false
    {
        $stmt = $this->pdo->prepare(
            'SELECT a.*, c.nome AS convenio, p.nome AS procedimento
               FROM amb_autorizacoes a
               JOIN amb_convenios c     ON c.id = a.convenio_id
               JOIN amb_procedimentos p ON p.id = a.procedimento_id
              WHERE a.id = ?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function criar(array $d): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO amb_autorizacoes
                (data_solicitacao, paciente, cpf, carteirinha, contato, convenio_id,
                 procedimento_id, data_pedido, data_autorizacao, data_agendada,
                 status, senha, motivo_negativa, info_pendente, observacao,
                 usuario_id, usuario_nome)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute($this->valores($d));
        return (int) $this->pdo->lastInsertId();
    }

    public function atualizar(int $id, array $d): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE amb_autorizacoes
                SET data_solicitacao = ?, paciente = ?, cpf = ?, carteirinha = ?,
                    contato = ?, convenio_id = ?, procedimento_id = ?,
                    data_pedido = ?, data_autorizacao = ?, data_agendada = ?,
                    status = ?, senha = ?, motivo_negativa = ?, info_pendente = ?,
                    observacao = ?, usuario_id = ?, usuario_nome = ?
              WHERE id = ?'
        );
        return $stmt->execute([...$this->valores($d), $id]);
    }

    /** Os campos na ordem do INSERT/UPDATE, em um lugar só. */
    private function valores(array $d): array
    {
        return [
            $d['data_solicitacao'],
            $d['paciente'],
            // Vazio vira NULL: a ausência do dado é o que de fato acontece
            // quando a atendente não tem o documento em mãos.
            $d['cpf'] !== '' ? $d['cpf'] : null,
            $d['carteirinha'] !== '' ? $d['carteirinha'] : null,
            $d['contato'] !== '' ? $d['contato'] : null,
            $d['convenio_id'],
            $d['procedimento_id'],
            $d['data_pedido'] ?: null,
            // A data da autorização só faz sentido enquanto o pedido está
            // autorizado (ou já agendado, que vem depois). Se o registro
            // voltou para "aguardando" ou virou "negado", ela sai junto —
            // guardar a data de uma autorização que não existe mais faria
            // o relatório de prazo do convênio contar o que não houve.
            in_array($d['status'], ['autorizado', 'agendado'], true)
                ? ($d['data_autorizacao'] ?: null)
                : null,
            $d['data_agendada'] ?: null,
            $d['status'],
            $d['senha'] !== '' ? $d['senha'] : null,
            $d['status'] === 'negado' && $d['motivo_negativa'] !== '' ? $d['motivo_negativa'] : null,
            // O que falta para o pedido andar. Diferente do motivo da
            // negativa, ele sobrevive à mudança de situação: uma pendência
            // resolvida se apaga à mão, porque quem apaga é quem sabe que
            // ela foi resolvida.
            ($d['info_pendente'] ?? '') !== '' ? $d['info_pendente'] : null,
            $d['observacao'] !== '' ? $d['observacao'] : null,
            Auth::id(),
            Auth::nome(),
        ];
    }

    /**
     * Muda só a situação, direto da lista.
     *
     * Agendar exige data: sem ela o status diria que está marcado e a tela
     * não saberia dizer para quando.
     */
    public function mudarStatus(int $id, string $status, ?string $dataAgendada, string $motivo = ''): bool
    {
        if (!isset(self::STATUS[$status])) {
            return false;
        }

        // A data agendada nunca é apagada por mudança de situação: ela é um
        // campo livre, que a atendente pode ter preenchido antes mesmo de o
        // convênio responder. Sem data nova no formulário, a que está gravada
        // permanece.
        //
        // A data da autorização, essa sim, só vale enquanto o pedido está
        // autorizado ou agendado — foi a regra combinada quando o campo nasceu.
        $stmt = $this->pdo->prepare(
            "UPDATE amb_autorizacoes
                SET status = ?,
                    data_agendada   = COALESCE(?, data_agendada),
                    motivo_negativa = ?,
                    data_autorizacao = CASE WHEN ? IN ('autorizado','agendado')
                                            THEN data_autorizacao ELSE NULL END,
                    usuario_id = ?, usuario_nome = ?
              WHERE id = ?"
        );

        return $stmt->execute([
            $status,
            $dataAgendada ?: null,
            $status === 'negado' && $motivo !== '' ? $motivo : null,
            $status,
            Auth::id(),
            Auth::nome(),
            $id,
        ]);
    }

    public function excluir(int $id): bool
    {
        return $this->pdo->prepare('DELETE FROM amb_autorizacoes WHERE id = ?')->execute([$id]);
    }

    // ── Documentos (guia autorizada e pedido médico) ─────────────────────

    /** Os dois papéis que um arquivo pode ter, com o nome que a tela usa. */
    public const TIPOS_ANEXO = [
        'guia'   => 'Guia autorizada',
        'pedido' => 'Pedido médico',
    ];

    /**
     * O que a tela escreve no lugar da miniatura, para o que não é foto.
     *
     * Vive aqui, e não no Controller, porque quem lê é a tela — e porque uma
     * lista de rótulos espalhada em dois lugares vira duas listas diferentes
     * no dia em que entrar o terceiro tipo de arquivo.
     */
    public const ROTULOS_MIME = [
        'application/pdf' => 'PDF',
        'text/html'       => 'HTML',
    ];

    /**
     * As colunas da ficha do documento — todas menos o documento.
     *
     * O arquivo mora na coluna `conteudo`, e ela fica DE FORA de propósito:
     * um `SELECT *` numa autorização com oito anexos arrastaria dezenas de
     * megabytes do banco para a memória do PHP só para desenhar a lista.
     * O conteúdo sai do banco uma vez só, em conteudoDoAnexo(), quando
     * alguém abre aquele documento.
     *
     * `no_banco` responde, sem trazer o arquivo, à única pergunta que a tela
     * faz a respeito dele: ainda existe? As linhas antigas, cujo arquivo
     * estava na pasta que se perdeu, respondem não.
     */
    private const CAMPOS_ANEXO =
        'id, autorizacao_id, tipo, nome_orig, nome_arq, mime, tamanho,
         usuario_id, usuario_nome, created_at,
         (conteudo IS NOT NULL) AS no_banco';

    /** @return array<string,array<int,array<string,mixed>>> separados por tipo */
    public function anexos(int $autorizacaoId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT ' . self::CAMPOS_ANEXO . '
               FROM amb_autorizacao_anexos
              WHERE autorizacao_id = ?
              ORDER BY created_at, id'
        );
        $stmt->execute([$autorizacaoId]);

        $por = array_fill_keys(array_keys(self::TIPOS_ANEXO), []);

        foreach ($stmt->fetchAll() as $a) {
            $por[$a['tipo']][] = $a;
        }

        return $por;
    }

    public function anexoPorId(int $id): array|false
    {
        $stmt = $this->pdo->prepare(
            'SELECT ' . self::CAMPOS_ANEXO . ' FROM amb_autorizacao_anexos WHERE id = ?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * O documento em si, lido do banco na hora de servir.
     *
     * Devolve null quando a linha é antiga e o arquivo nunca foi importado —
     * quem chamou decide se procura em disco ou se avisa que se perdeu.
     */
    public function conteudoDoAnexo(int $id): ?string
    {
        $stmt = $this->pdo->prepare('SELECT conteudo FROM amb_autorizacao_anexos WHERE id = ?');
        $stmt->execute([$id]);

        $conteudo = $stmt->fetchColumn();

        return is_string($conteudo) && $conteudo !== '' ? $conteudo : null;
    }

    /**
     * @param array{nome_orig:string,nome_arq:string,mime:string,tamanho:int,conteudo:string} $a
     */
    public function inserirAnexo(int $autorizacaoId, string $tipo, array $a): bool
    {
        if (!isset(self::TIPOS_ANEXO[$tipo])) {
            return false;
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO amb_autorizacao_anexos
                (autorizacao_id, tipo, nome_orig, nome_arq, mime, tamanho,
                 conteudo, usuario_id, usuario_nome)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );

        // O conteúdo vai como PARAM_LOB: é a forma de dizer ao PDO que aquilo
        // são bytes, e não texto a ser interpretado em algum conjunto de
        // caracteres. Um JPEG tem byte zero no meio; tratado como texto, ele
        // chegaria cortado ao banco.
        $stmt->bindValue(1, $autorizacaoId, PDO::PARAM_INT);
        $stmt->bindValue(2, $tipo);
        $stmt->bindValue(3, $a['nome_orig']);
        $stmt->bindValue(4, $a['nome_arq']);
        $stmt->bindValue(5, $a['mime']);
        $stmt->bindValue(6, $a['tamanho'], PDO::PARAM_INT);
        $stmt->bindValue(7, $a['conteudo'], PDO::PARAM_LOB);
        $stmt->bindValue(8, Auth::id(), Auth::id() === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(9, Auth::nome());

        return $stmt->execute();
    }

    /**
     * Guarda em `conteudo` o arquivo de uma linha que ainda não tem.
     *
     * Só a importação usa isto, e de propósito só preenche o que está vazio:
     * rodar o importador duas vezes não reescreve documento nenhum.
     */
    public function guardarConteudo(int $id, string $conteudo): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE amb_autorizacao_anexos
                SET conteudo = ?
              WHERE id = ? AND conteudo IS NULL'
        );

        $stmt->bindValue(1, $conteudo, PDO::PARAM_LOB);
        $stmt->bindValue(2, $id, PDO::PARAM_INT);

        return $stmt->execute() && $stmt->rowCount() === 1;
    }

    public function excluirAnexo(int $id): bool
    {
        return $this->pdo->prepare('DELETE FROM amb_autorizacao_anexos WHERE id = ?')->execute([$id]);
    }

    public function contarAnexos(int $autorizacaoId, string $tipo): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM amb_autorizacao_anexos WHERE autorizacao_id = ? AND tipo = ?'
        );
        $stmt->execute([$autorizacaoId, $tipo]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Quem já tem guia e quem já tem pedido, para a lista marcar sem precisar
     * de uma consulta por linha.
     *
     * @param  array<int,int> $ids
     * @return array<int,array{guia:bool,pedido:bool}>
     */
    public function anexosDasAutorizacoes(array $ids): array
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));

        if (!$ids) {
            return [];
        }

        $marcas = implode(',', array_fill(0, count($ids), '?'));
        $stmt   = $this->pdo->prepare(
            "SELECT autorizacao_id, tipo, COUNT(*) AS total
               FROM amb_autorizacao_anexos
              WHERE autorizacao_id IN ({$marcas})
              GROUP BY autorizacao_id, tipo"
        );
        $stmt->execute($ids);

        $mapa = [];

        foreach ($stmt->fetchAll() as $l) {
            $id = (int) $l['autorizacao_id'];
            $mapa[$id] ??= ['guia' => false, 'pedido' => false];
            $mapa[$id][$l['tipo']] = true;
        }

        return $mapa;
    }

    // ── Cadastros de apoio ───────────────────────────────────────────────

    public function convenios(bool $soAtivos = true): array
    {
        return $this->pdo->query(
            'SELECT * FROM amb_convenios' . ($soAtivos ? ' WHERE ativo = 1' : '') . ' ORDER BY nome'
        )->fetchAll();
    }

    public function procedimentos(bool $soAtivos = true): array
    {
        return $this->pdo->query(
            'SELECT * FROM amb_procedimentos' . ($soAtivos ? ' WHERE ativo = 1' : '') . ' ORDER BY nome'
        )->fetchAll();
    }

    /** Quantas autorizações usam cada convênio / procedimento. */
    public function usoConvenios(): array
    {
        return $this->contagemPor('convenio_id');
    }

    public function usoProcedimentos(): array
    {
        return $this->contagemPor('procedimento_id');
    }

    private function contagemPor(string $coluna): array
    {
        // A coluna vem de dentro da classe, nunca da requisição.
        $linhas = $this->pdo->query(
            "SELECT {$coluna} AS id, COUNT(*) AS total FROM amb_autorizacoes GROUP BY {$coluna}"
        )->fetchAll();

        $mapa = [];
        foreach ($linhas as $l) {
            $mapa[(int) $l['id']] = (int) $l['total'];
        }
        return $mapa;
    }

    public function salvarApoio(string $tabela, int $id, array $d): bool
    {
        $t = $this->tabelaApoio($tabela);

        if ($t === 'amb_convenios') {
            $campos = 'nome = ?, telefone = ?, observacao = ?, ativo = ?';
            $vals   = [$d['nome'], $d['telefone'] ?: null, $d['observacao'] ?: null, $d['ativo']];
        } else {
            $campos = 'nome = ?, codigo = ?, observacao = ?, ativo = ?';
            $vals   = [$d['nome'], $d['codigo'] ?: null, $d['observacao'] ?: null, $d['ativo']];
        }

        if ($id > 0) {
            $vals[] = $id;
            return $this->pdo->prepare("UPDATE {$t} SET {$campos} WHERE id = ?")->execute($vals);
        }

        $colunas = str_replace(' = ?', '', $campos);
        $marcas  = implode(', ', array_fill(0, count($vals), '?'));
        return $this->pdo->prepare("INSERT INTO {$t} ({$colunas}) VALUES ({$marcas})")->execute($vals);
    }

    public function apoioPorId(string $tabela, int $id): array|false
    {
        $t = $this->tabelaApoio($tabela);
        $stmt = $this->pdo->prepare("SELECT * FROM {$t} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function excluirApoio(string $tabela, int $id): bool
    {
        $t = $this->tabelaApoio($tabela);
        return $this->pdo->prepare("DELETE FROM {$t} WHERE id = ?")->execute([$id]);
    }

    public function nomeApoioExiste(string $tabela, string $nome, int $excetoId = 0): bool
    {
        $t = $this->tabelaApoio($tabela);
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM {$t} WHERE nome = ? AND id <> ?");
        $stmt->execute([$nome, $excetoId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Traduz o apelido do cadastro para o nome real da tabela.
     *
     * As consultas de apoio interpolam o nome da tabela, então ele nunca pode
     * vir da URL: só estes dois valores passam, e qualquer outro estoura.
     */
    private function tabelaApoio(string $tabela): string
    {
        return match ($tabela) {
            'convenios'     => 'amb_convenios',
            'procedimentos' => 'amb_procedimentos',
            default         => throw new InvalidArgumentException('Cadastro desconhecido: ' . $tabela),
        };
    }
}
