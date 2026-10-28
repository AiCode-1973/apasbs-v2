<?php
declare(strict_types=1);

/**
 * Fonte única de verdade para todos os módulos do sistema.
 * Adicionar um módulo aqui é tudo que é necessário para ele aparecer
 * na sidebar, no painel, nos títulos e no whitelist do roteador.
 */
class Modules
{
    // slug, nome, icone, action padrão, descrição, grupo da sidebar, título do topbar
    private const REGISTRY = [
        [
            'slug'       => 'usuarios',
            'nome'       => 'Usuários',
            'icone'      => 'usuarios',
            'action'     => 'lista',
            'desc'       => 'Gerencie os profissionais do sistema',
            'grupo'      => 'cadastros',
            'page_title' => 'Usuários',
        ],
        [
            'slug'       => 'setores',
            'nome'       => 'Setores',
            'icone'      => 'setores',
            'action'     => 'lista',
            'desc'       => 'Cadastro de setores da organização',
            'grupo'      => 'cadastros',
            'page_title' => 'Setores',
        ],
        [
            'slug'       => 'tuss',
            'nome'       => 'TUSS',
            'icone'      => 'tuss',
            'action'     => 'lista',
            'desc'       => 'Tabela 22 – Procedimentos e eventos em saúde',
            'grupo'      => 'cadastros',
            'page_title' => 'TUSS – Procedimentos',
        ],
        [
            'slug'       => 'cirurgias',
            'nome'       => 'Cirurgias',
            'icone'      => 'cirurgias',
            'action'     => 'lista',
            'desc'       => 'Registro de procedimentos cirúrgicos',
            'grupo'      => 'cadastros',
            'page_title' => 'Cirurgias',
        ],
        [
            'slug'       => 'especialidades',
            'nome'       => 'Especialidades',
            'icone'      => 'especialidades',
            'action'     => 'lista',
            'desc'       => 'Especialidades médicas reconhecidas pelo CFM',
            'grupo'      => 'cadastros',
            'page_title' => 'Especialidades Médicas',
        ],
        [
            'slug'       => 'cadastro',
            'nome'       => 'Cadastro',
            'icone'      => 'cadastro',
            'action'     => 'lista',
            'desc'       => 'Beneficiários do plano de saúde: titulares, dependentes e mensalidades',
            'grupo'      => 'cadastros',
            'page_title' => 'Cadastro de Beneficiários',
        ],
        [
            'slug'       => 'declaracoes',
            'nome'       => 'Declaração de Saúde',
            'icone'      => 'declaracoes',
            'action'     => 'lista',
            'desc'       => 'Doenças preexistentes declaradas na adesão e a cobertura parcial temporária',
            'grupo'      => 'cadastros',
            'page_title' => 'Declaração de Saúde',
        ],
        [
            'slug'       => 'vendas',
            'nome'       => 'Vendas',
            'icone'      => 'vendas',
            'action'     => 'lista',
            'desc'       => 'Propostas de plano por vendedor: negociação, fechamento que vira contrato e comissão',
            'grupo'      => 'cadastros',
            'page_title' => 'Vendas',
        ],
        [
            'slug'       => 'comercial',
            'nome'       => 'Comercial',
            'icone'      => 'comercial',
            'action'     => 'lista',
            'desc'       => 'Tabelas de valores dos convênios: o que cada procedimento, pacote, OPME ou diária paga',
            'grupo'      => 'cadastros',
            'page_title' => 'Comercial — tabelas de valores',
        ],
        [
            'slug'       => 'ocupacional',
            'nome'       => 'Saúde Ocupacional',
            'icone'      => 'ocupacional',
            'action'     => 'lista',
            'desc'       => 'Prontuários de saúde ocupacional (NR-7 / PCMSO) e entregas de atestado médico',
            'grupo'      => 'cadastros',
            'page_title' => 'Saúde Ocupacional',
        ],
        [
            'slug'       => 'agenda',
            'nome'       => 'Agenda',
            'icone'      => 'agenda',
            'action'     => 'mes',
            'desc'       => 'Escala mensal de Colono, Combo e EDA',
            'grupo'      => 'ambulatorio',
            'page_title' => 'Agenda do Ambulatório',
        ],
        [
            'slug'       => 'medicos',
            'nome'       => 'Médicos',
            'icone'      => 'medicos',
            'action'     => 'lista',
            'desc'       => 'Médicos que atendem no ambulatório',
            'grupo'      => 'ambulatorio',
            'page_title' => 'Médicos do Ambulatório',
        ],
        [
            'slug'       => 'exames',
            'nome'       => 'Exames',
            'icone'      => 'exames',
            'action'     => 'lista',
            'desc'       => 'Tipos de exame agendáveis (Combo, Colono, EDA)',
            'grupo'      => 'ambulatorio',
            'page_title' => 'Tipos de Exame',
        ],
        [
            'slug'       => 'consultorios',
            'nome'       => 'Consultórios',
            'icone'      => 'consultorios',
            'action'     => 'mapa',
            'desc'       => 'Mapa de sala: quem ocupa cada consultório na semana',
            'grupo'      => 'ambulatorio',
            'page_title' => 'Mapa de sala',
        ],
        [
            'slug'       => 'autorizacoes',
            'nome'       => 'Autorizações',
            'icone'      => 'autorizacoes',
            'action'     => 'lista',
            'desc'       => 'Pedidos de exame esperando liberação do convênio',
            'grupo'      => 'ambulatorio',
            'page_title' => 'Autorização de exames',
        ],
        [
            'slug'       => 'codigos',
            'nome'       => 'Códigos de exames',
            'icone'      => 'codigos',
            'action'     => 'lista',
            'desc'       => 'Com que código cada convênio fatura cada exame',
            'grupo'      => 'ambulatorio',
            'page_title' => 'Códigos de exames',
        ],
        [
            'slug'       => 'cancelamentos',
            'nome'       => 'Cancelamentos',
            'icone'      => 'cancelamentos',
            'action'     => 'lista',
            'desc'       => 'Mensagens que a atendente escolhe ao desmarcar um exame',
            'grupo'      => 'ambulatorio',
            'page_title' => 'Motivos de cancelamento',
        ],
        [
            'slug'       => 'pop',
            'nome'       => 'POP',
            'icone'      => 'pop',
            'action'     => 'lista',
            'desc'       => 'Procedimento operacional padrão da central',
            'grupo'      => 'central',
            'page_title' => 'Procedimentos (POP)',
        ],
        [
            'slug'       => 'coletas',
            'nome'       => 'Coletas',
            'icone'      => 'coletas',
            'action'     => 'lista',
            'desc'       => 'O que o robô do ReciclaLead trouxe, esperando conferência',
            'grupo'      => 'central',
            'page_title' => 'Coletas do ReciclaLead',
        ],
        [
            'slug'       => 'fechamentos',
            'nome'       => 'Fechamentos',
            'icone'      => 'fechamentos',
            'action'     => 'lista',
            'desc'       => 'Total de atendimentos fechado mês a mês',
            'grupo'      => 'central',
            'page_title' => 'Fechamentos mensais',
        ],
        [
            'slug'       => 'motivos',
            'nome'       => 'Motivos',
            'icone'      => 'motivos',
            'action'     => 'lista',
            'desc'       => 'Motivos de fechamento de atendimento',
            'grupo'      => 'central',
            'page_title' => 'Motivos de fechamento',
        ],
        [
            'slug'       => 'opme',
            'nome'       => 'OPME',
            'icone'      => 'opme',
            'action'     => 'lista',
            'desc'       => 'Órteses, próteses e materiais especiais: notas e parcelas',
            'grupo'      => 'opme',
            'page_title' => 'OPME',
        ],
        [
            'slug'       => 'auditoria_hosp',
            'nome'       => 'Auditoria Hospitalar',
            'icone'      => 'auditoria_hosp',
            'action'     => 'lista',
            'desc'       => 'Contas de internação e de PA/Ambulatório: auditoria dos valores, glosas e recursos com os convênios',
            'grupo'      => 'faturamento',
            'page_title' => 'Auditoria Hospitalar',
        ],
        [
            'slug'       => 'permissoes',
            'nome'       => 'Permissões',
            'icone'      => 'permissoes',
            'action'     => 'gerenciar',
            'desc'       => 'Controle de acesso por módulo',
            'grupo'      => 'configuracoes',
            'page_title' => 'Permissões de Módulos',
        ],
        [
            'slug'       => 'ajuda',
            'nome'       => 'Manuais',
            'icone'      => 'ajuda',
            'action'     => 'lista',
            'desc'       => 'Como usar cada parte do sistema',
            'grupo'      => 'ajuda',
            'page_title' => 'Manuais do sistema',
        ],
        [
            'slug'       => 'auditoria',
            'nome'       => 'Auditoria',
            'icone'      => 'auditoria',
            'action'     => 'lista',
            'desc'       => 'Histórico de tudo que foi criado, editado e excluído',
            'grupo'      => 'configuracoes',
            'page_title' => 'Log de Auditoria',
        ],
    ];

    // A ordem aqui é a ordem das seções na sidebar.
    private const GRUPOS = [
        'cadastros'     => 'Cadastros',
        'ambulatorio'   => 'Ambulatório',
        'central'       => 'Central de Agendamento',
        'opme'          => 'OPME',
        'faturamento'   => 'Faturamento',
        'configuracoes' => 'Configurações',
        'ajuda'         => 'Ajuda',
    ];

    private const SESSION_KEY = 'modules_synced_v2';

    // ── Helpers ───────────────────────────────────────────────────────────

    /** Lista de slugs válidos (whitelist do router) */
    public static function slugs(): array
    {
        return array_column(self::REGISTRY, 'slug');
    }

    /** Registry completo */
    public static function todos(): array
    {
        return self::REGISTRY;
    }

    /** Registry indexado por slug */
    public static function bySlugs(): array
    {
        $result = [];
        foreach (self::REGISTRY as $m) {
            $result[$m['slug']] = $m;
        }
        return $result;
    }

    /** Um módulo pelo slug */
    public static function get(string $slug): ?array
    {
        return self::bySlugs()[$slug] ?? null;
    }

    /** Módulos filtrados por grupo da sidebar */
    public static function byGroup(string $grupo): array
    {
        return array_values(
            array_filter(self::REGISTRY, fn($m) => ($m['grupo'] ?? '') === $grupo)
        );
    }

    /** Mapa slug → page_title para o topbar */
    public static function pageTitles(): array
    {
        $titles = ['painel' => 'Painel'];
        foreach (self::REGISTRY as $m) {
            $titles[$m['slug']] = $m['page_title'] ?? $m['nome'];
        }
        return $titles;
    }

    /** Labels dos grupos da sidebar */
    public static function grupos(): array
    {
        return self::GRUPOS;
    }

    // ── Sync DB ───────────────────────────────────────────────────────────

    /** Garante que todos os módulos existam na tabela `modulos` */
    public static function sync(): void
    {
        if (!empty($_SESSION[self::SESSION_KEY])) {
            return;
        }

        try {
            $stmt = getPDO()->prepare(
                'INSERT INTO modulos (slug, nome, icone) VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE nome = VALUES(nome), icone = VALUES(icone)'
            );
            foreach (self::REGISTRY as $m) {
                $stmt->execute([$m['slug'], $m['nome'], $m['icone']]);
            }
            $_SESSION[self::SESSION_KEY] = true;
        } catch (PDOException $e) {
            // Silencia: a tabela pode ainda não existir antes da instalação
            error_log('Modules::sync — ' . $e->getMessage());
        }
    }
}
