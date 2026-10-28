<?php
declare(strict_types=1);

require_once BASE_PATH . '/modules/cadastro/Model.php';

class DeclaracaoModel
{
    /** Meses de Cobertura Parcial Temporária, contados da contratação. */
    public const MESES_CPT = 24;

    public const STATUS = [
        'em_preenchimento' => 'Em preenchimento',
        'concluida'        => 'Concluída',
    ];

    /** A escolha do cabeçalho do formulário. */
    public const ORIENTACOES = [
        'apas'     => 'Orientado por médico designado pela APAS Baixada Santista',
        'proprio'  => 'Orientado por médico de escolha do proponente',
        'dispensa' => 'Dispensou o médico orientador',
    ];

    /**
     * Os 22 itens do questionário, transcritos do formulário em papel.
     *
     * Ficam no código, e não numa tabela mantida pela tela, porque são o
     * texto de um documento regulado pela ANS: mudar uma palavra aqui muda
     * o que a pessoa assinou, e isso não é manutenção de cadastro.
     *
     * 'tipo' diz como o item é respondido:
     *   'sn'    — sim ou não, com descrição obrigatória no sim
     *   'valor' — um número ou texto curto (grau, peso, altura, idade)
     */
    public const ITENS = [
        1 => ['tipo' => 'sn', 'titulo' => 'Sistema respiratório',
              'texto' => 'Doenças do Sistema Respiratório (asma, bronquite, enfisema, desvio de septo, adenoide, sinusite, outras).'],
        2 => ['tipo' => 'sn', 'titulo' => 'Sistema cardiovascular',
              'texto' => 'Doenças do Sistema Cardiovascular (hipertensão arterial, doença coronariana, varizes, valvuloplastias, insuficiência cardíaca, arritmias, outras).'],
        3 => ['tipo' => 'sn', 'titulo' => 'Sistema osteomuscular',
              'texto' => 'Doenças do Sistema Osteomuscular (hérnia de disco, osteoporose, lesão de menisco e/ou ligamentos, bursite, artrite, artrose, gota, lúpus, tenossinovites, escoliose, lordose e outros).'],
        4 => ['tipo' => 'sn', 'titulo' => 'Sistema digestivo',
              'texto' => 'Doenças do Sistema Digestivo (gastrite, úlcera, colite, cirrose hepática, hepatites, pancreatite, hérnia de hiato, hérnia umbilical, hérnia inguinal, pedra na vesícula, hemorroidas, diverticulites, má oclusão, micrognatismo, prognatismo e outras).'],
        5 => ['tipo' => 'sn', 'titulo' => 'Sistema geniturinário',
              'texto' => 'Doenças do Sistema Geniturinário (cólica renal, insuficiência renal, doenças da próstata, infertilidade, endometriose, mioma, cisto de ovário, infecção urinária, doenças da mama e outras).'],
        6 => ['tipo' => 'sn', 'titulo' => 'Sistema neurológico',
              'texto' => 'Doenças do Sistema Neurológico (mal de Parkinson, doença de Alzheimer, paralisia, sequela de derrame, apneia do sono e outras).'],
        7 => ['tipo' => 'sn', 'titulo' => 'Sistema endócrino',
              'texto' => 'Doenças do Sistema Endócrino (diabetes, hipertireoidismo, hipotireoidismo, obesidade, desnutrição e outras).'],
        8 => ['tipo' => 'sn', 'titulo' => 'Infectocontagiosas',
              'texto' => 'Doenças infectocontagiosas (AIDS/HIV, malária, tuberculose, doenças sexualmente transmissíveis e outras).'],
        9 => ['tipo' => 'sn', 'titulo' => 'Psiquiátricas',
              'texto' => 'Doenças psiquiátricas (psicose, depressão e outras).'],
        10 => ['tipo' => 'sn', 'titulo' => 'Cancerosas',
               'texto' => 'Doenças cancerosas (câncer de mama, próstata, pulmão, fígado, leucemia e outras).'],
        11 => ['tipo' => 'sn', 'titulo' => 'Hematológicas',
               'texto' => 'Doenças hematológicas (anemia, hemofilia, trombocitopenia, trombofilias e outras).'],
        12 => ['tipo' => 'sn', 'titulo' => 'Pele e alérgicas',
               'texto' => 'Doenças de pele e alérgicas (nevus, hemangiomas, sinais, rinite, urticária, hiperidrose — excesso de suor nas mãos, axilas, rosto — e outras).'],
        13 => ['tipo' => 'sn', 'titulo' => 'Congênitas',
               'texto' => 'Doenças congênitas (síndrome de Down, más-formações). Especifique os problemas relacionados: cardíaco, respiratório, visão, urinário e outros.'],
        14 => ['tipo' => 'sn', 'titulo' => 'Ouvido',
               'texto' => 'Doenças do ouvido (surdez, otites, labirintite e outras).'],
        15 => ['tipo' => 'sn', 'titulo' => 'Olhos',
               'texto' => 'Doenças dos olhos (hipermetropia, miopia e astigmatismo, catarata, estrabismo, glaucoma, ceratocone, retinopatia e cegueira).'],
        // 'depende' => 15: no papel esta linha começa com "Nos casos de…".
        // Ela só existe quando o item 15 foi respondido com sim; cobrá-la de
        // quem não tem doença nos olhos deixaria a declaração eternamente
        // incompleta por um campo que não se aplica.
        16 => ['tipo' => 'valor', 'titulo' => 'Grau — OD e OE', 'depende' => 15,
               'texto' => 'Nos casos de hipermetropia, miopia e astigmatismo, especificar o grau e qual doença, para o olho direito e o olho esquerdo.',
               'dica'  => 'Ex.: OD -2,50 miopia · OE -3,00 miopia'],
        17 => ['tipo' => 'sn', 'titulo' => 'Prótese ou órtese',
               'texto' => 'Faz uso de prótese ou órtese (pinos, parafusos, placas, fios, silicone e outros)? A indicação foi estética? Especificar o motivo (a doença), a localização, há quanto tempo e se está fazendo algum tratamento.'],
        18 => ['tipo' => 'sn', 'titulo' => 'Cirurgias',
               'texto' => 'Realizou alguma cirurgia? Especificar qual, há quanto tempo, se tem sequela decorrente dela e qual a sequela.'],
        19 => ['tipo' => 'valor', 'titulo' => 'Peso',
               'texto' => 'Qual o peso, em quilos?', 'dica' => 'Ex.: 72,5'],
        20 => ['tipo' => 'valor', 'titulo' => 'Altura',
               'texto' => 'Qual a altura, em metros?', 'dica' => 'Ex.: 1,72'],
        21 => ['tipo' => 'valor', 'titulo' => 'Idade',
               'texto' => 'Qual a idade, em anos?', 'dica' => 'Vem da data de nascimento do cadastro'],
        22 => ['tipo' => 'sn', 'titulo' => 'Internação',
               'texto' => 'Paciente internado?'],
    ];

    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = getPDO();
    }

    /** Os itens que aceitam sim ou não. */
    public static function itensSN(): array
    {
        return array_keys(array_filter(self::ITENS, fn($i) => $i['tipo'] === 'sn'));
    }

    // ── Declarações ──────────────────────────────────────────────────────

    private function onde(array $f, array &$params): string
    {
        $w = [];

        if (($f['busca'] ?? '') !== '') {
            $like    = '%' . like_escape($f['busca']) . '%';
            $digitos = preg_replace('/\D/', '', $f['busca']) ?? '';
            $likeCpf = $digitos !== '' ? '%' . like_escape($digitos) . '%' : null;

            $w[] = "(c.numero_proposta LIKE ? ESCAPE '!'
                     OR EXISTS (SELECT 1 FROM pl_beneficiarios b
                                 WHERE b.contrato_id = c.id
                                   AND (b.nome LIKE ? ESCAPE '!'"
                 . ($likeCpf !== null ? " OR b.cpf LIKE ? ESCAPE '!'" : '') . ')))';

            array_push($params, $like, $like);
            if ($likeCpf !== null) {
                $params[] = $likeCpf;
            }
        }

        if (($f['status'] ?? '') !== '' && isset(self::STATUS[$f['status']])) {
            $w[] = 'd.status = ?';
            $params[] = $f['status'];
        }

        // "Só as que têm DLP declarada": é a fila que interessa ao setor,
        // porque é ela que gera Cobertura Parcial Temporária.
        if (!empty($f['com_dlp'])) {
            $w[] = "EXISTS (SELECT 1 FROM ds_respostas r
                             WHERE r.declaracao_id = d.id AND r.resposta = 'S')";
        }

        return $w ? ' WHERE ' . implode(' AND ', $w) : '';
    }

    public function listar(array $f, int $limite, int $offset): array
    {
        $params = [];
        $sql = "SELECT d.*, c.numero_proposta, c.tipo AS tipo_contrato,
                       t.nome AS titular,
                       (SELECT COUNT(DISTINCT r.beneficiario_id) FROM ds_respostas r
                         WHERE r.declaracao_id = d.id AND r.resposta = 'S') AS com_dlp,
                       (SELECT COUNT(DISTINCT r.beneficiario_id) FROM ds_respostas r
                         WHERE r.declaracao_id = d.id) AS respondidos
                  FROM ds_declaracoes d
                  JOIN pl_contratos c ON c.id = d.contrato_id
                  LEFT JOIN pl_beneficiarios t
                         ON t.contrato_id = c.id AND t.tipo = 'titular'"
             . $this->onde($f, $params)
             . ' ORDER BY d.data_preenchimento DESC, d.id DESC
                 LIMIT ? OFFSET ?';

        $stmt = $this->pdo->prepare($sql);

        $i = 1;
        foreach ($params as $v) {
            $stmt->bindValue($i++, $v);
        }
        $stmt->bindValue($i++, $limite, PDO::PARAM_INT);
        $stmt->bindValue($i,   $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function contar(array $f): int
    {
        $params = [];
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM ds_declaracoes d
               JOIN pl_contratos c ON c.id = d.contrato_id' . $this->onde($f, $params)
        );
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function panorama(): array
    {
        return (array) $this->pdo->query(
            "SELECT COUNT(*) AS total,
                    SUM(status = 'em_preenchimento') AS abertas,
                    SUM(status = 'concluida')        AS concluidas,
                    (SELECT COUNT(DISTINCT r.beneficiario_id) FROM ds_respostas r
                      WHERE r.resposta = 'S')        AS pessoas_dlp
               FROM ds_declaracoes"
        )->fetch();
    }

    public function porId(int $id): array|false
    {
        $stmt = $this->pdo->prepare(
            'SELECT d.*, c.numero_proposta, c.tipo AS tipo_contrato, c.data_adesao
               FROM ds_declaracoes d
               JOIN pl_contratos c ON c.id = d.contrato_id
              WHERE d.id = ?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /** Já existe declaração para este contrato? */
    public function doContrato(int $contratoId): array|false
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM ds_declaracoes WHERE contrato_id = ?
              ORDER BY data_preenchimento DESC, id DESC LIMIT 1'
        );
        $stmt->execute([$contratoId]);
        return $stmt->fetch();
    }

    public function salvar(int $id, array $d): int
    {
        $vals = [
            'contrato_id'        => $d['contrato_id'],
            'data_preenchimento' => $d['data_preenchimento'],
            'data_contratacao'   => $d['data_contratacao'] ?: null,
            'orientacao'         => $d['orientacao'],
            // O médico orientador só existe quando houve orientação. Guardar
            // o nome numa declaração que dispensou o médico contaria uma
            // coisa que não aconteceu.
            'medico_nome'        => $d['orientacao'] !== 'dispensa' && $d['medico_nome'] !== ''
                                    ? $d['medico_nome'] : null,
            'medico_crm'         => $d['orientacao'] !== 'dispensa' && $d['medico_crm'] !== ''
                                    ? $d['medico_crm'] : null,
            'assinante_nome'     => $d['assinante_nome'] !== '' ? $d['assinante_nome'] : null,
            'assinante_cpf'      => $d['assinante_cpf'] !== '' ? $d['assinante_cpf'] : null,
            'data_assinatura'    => $d['data_assinatura'] ?: null,
            'status'             => $d['status'],
            'observacao'         => $d['observacao'] !== '' ? $d['observacao'] : null,
            'usuario_id'         => Auth::id(),
            'usuario_nome'       => Auth::nome(),
        ];

        if ($id > 0) {
            $campos = implode(' = ?, ', array_keys($vals)) . ' = ?';
            $this->pdo->prepare("UPDATE ds_declaracoes SET {$campos} WHERE id = ?")
                      ->execute([...array_values($vals), $id]);
            return $id;
        }

        $colunas = implode(', ', array_keys($vals));
        $marcas  = implode(', ', array_fill(0, count($vals), '?'));
        $this->pdo->prepare("INSERT INTO ds_declaracoes ({$colunas}) VALUES ({$marcas})")
                  ->execute(array_values($vals));

        return (int) $this->pdo->lastInsertId();
    }

    public function excluir(int $id): bool
    {
        // As respostas vão junto pelo ON DELETE CASCADE: elas são o corpo
        // da declaração, não documento separado.
        return $this->pdo->prepare('DELETE FROM ds_declaracoes WHERE id = ?')->execute([$id]);
    }

    // ── Respostas ────────────────────────────────────────────────────────

    /**
     * As respostas de uma declaração.
     *
     * @return array<int, array<int, array>>  [beneficiario_id][item]
     */
    public function respostas(int $declaracaoId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM ds_respostas WHERE declaracao_id = ? ORDER BY beneficiario_id, item'
        );
        $stmt->execute([$declaracaoId]);

        $mapa = [];
        foreach ($stmt->fetchAll() as $r) {
            $mapa[(int) $r['beneficiario_id']][(int) $r['item']] = $r;
        }
        return $mapa;
    }

    /** Grava as 22 respostas de uma pessoa de uma vez. */
    public function salvarRespostas(int $declaracaoId, int $beneficiarioId, array $itens): void
    {
        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO ds_respostas
                    (declaracao_id, beneficiario_id, item, resposta, valor, descricao)
                 VALUES (?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE resposta = VALUES(resposta),
                                         valor = VALUES(valor),
                                         descricao = VALUES(descricao)'
            );

            foreach (self::ITENS as $n => $def) {
                $r = $itens[$n] ?? [];

                $resposta  = ($r['resposta'] ?? '') === 'S' ? 'S'
                           : ((($r['resposta'] ?? '') === 'N') ? 'N' : null);
                $valor     = trim((string) ($r['valor'] ?? ''));
                $descricao = trim((string) ($r['descricao'] ?? ''));

                // Descrição só faz sentido junto de um "sim": guardada num
                // "não", ela apareceria na folha impressa descrevendo uma
                // doença que a pessoa disse não ter.
                if ($def['tipo'] === 'sn' && $resposta !== 'S') {
                    $descricao = '';
                }

                $stmt->execute([
                    $declaracaoId, $beneficiarioId, $n,
                    $def['tipo'] === 'sn' ? $resposta : null,
                    $valor !== '' ? $valor : null,
                    $descricao !== '' ? $descricao : null,
                ]);
            }

            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    // ── Cobertura Parcial Temporária ─────────────────────────────────────

    /**
     * O resumo de cada pessoa na declaração.
     *
     * Devolve, por beneficiário: quantos itens foram respondidos, quais
     * doenças foram declaradas, e até quando corre a CPT.
     */
    public function resumo(array $declaracao, array $pessoas, array $respostas): array
    {
        $fimCpt = $this->fimCpt($declaracao['data_contratacao'] ?? null);
        $hoje   = date('Y-m-d');
        $linhas = [];

        foreach ($pessoas as $p) {
            $id   = (int) $p['id'];
            $suas = $respostas[$id] ?? [];

            $declarados = [];
            $respondidos = 0;
            $aplicaveis  = 0;

            foreach (self::ITENS as $n => $def) {
                $r = $suas[$n] ?? null;

                // Item condicional que não se aplica sai da conta inteira:
                // não é pendência nem resposta.
                if (isset($def['depende'])) {
                    $pai = $suas[$def['depende']] ?? null;
                    if (($pai['resposta'] ?? null) !== 'S') {
                        continue;
                    }
                }

                $aplicaveis++;

                if ($def['tipo'] === 'sn') {
                    if ($r && $r['resposta'] !== null) {
                        $respondidos++;
                    }
                    if ($r && $r['resposta'] === 'S') {
                        $declarados[] = [
                            'item'      => $n,
                            'titulo'    => $def['titulo'],
                            'descricao' => $r['descricao'],
                        ];
                    }
                } elseif ($r && ($r['valor'] ?? '') !== '') {
                    $respondidos++;
                }
            }

            $temDlp = $declarados !== [];

            $linhas[] = [
                'id'          => $id,
                'nome'        => $p['nome'],
                'tipo'        => $p['tipo'],
                'respondidos' => $respondidos,
                'aplicaveis'  => $aplicaveis,
                'faltam'      => $aplicaveis - $respondidos,
                'declarados'  => $declarados,
                'tem_dlp'     => $temDlp,
                // CPT só existe para quem declarou alguma coisa. Marcar todo
                // mundo com data de CPT diria que a família inteira está com
                // cobertura suspensa, e não é isso que acontece.
                'cpt_ate'     => $temDlp ? $fimCpt : null,
                'cpt_vigente' => $temDlp && $fimCpt !== null && $fimCpt >= $hoje,
            ];
        }

        return $linhas;
    }

    /** Os 24 meses a partir da contratação. */
    public function fimCpt(?string $contratacao): ?string
    {
        if (!$contratacao) {
            return null;
        }

        try {
            return (new DateTimeImmutable($contratacao))
                ->modify('+' . self::MESES_CPT . ' months')
                ->format('Y-m-d');
        } catch (Exception) {
            return null;
        }
    }

    // ── Apoio ────────────────────────────────────────────────────────────

    /** Contratos que ainda não têm declaração, para o formulário novo. */
    public function contratosSemDeclaracao(): array
    {
        return $this->pdo->query(
            "SELECT c.id, c.numero_proposta, c.tipo, t.nome AS titular,
                    (SELECT COUNT(*) FROM pl_beneficiarios b
                      WHERE b.contrato_id = c.id AND b.status = 'ativo') AS pessoas
               FROM pl_contratos c
               LEFT JOIN pl_beneficiarios t
                      ON t.contrato_id = c.id AND t.tipo = 'titular'
              WHERE NOT EXISTS (SELECT 1 FROM ds_declaracoes d WHERE d.contrato_id = c.id)
                AND EXISTS (SELECT 1 FROM pl_beneficiarios b WHERE b.contrato_id = c.id)
              ORDER BY t.nome"
        )->fetchAll();
    }

    /** As pessoas ativas do contrato, titular primeiro — as colunas do papel. */
    public function pessoasDe(int $contratoId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT b.*, p.nome AS plano
               FROM pl_beneficiarios b
               LEFT JOIN pl_planos p ON p.id = b.plano_id
              WHERE b.contrato_id = ? AND b.status = 'ativo'
              ORDER BY b.tipo = 'titular' DESC, b.nome"
        );
        $stmt->execute([$contratoId]);
        return $stmt->fetchAll();
    }

    public function beneficiarioPorId(int $id): array|false
    {
        $stmt = $this->pdo->prepare('SELECT * FROM pl_beneficiarios WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
}
