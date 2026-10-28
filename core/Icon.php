<?php
declare(strict_types=1);

/**
 * Ícones SVG inline (traçado, estilo Lucide).
 *
 * A v1 dependia da fonte Bootstrap Icons vinda de CDN. Aqui o desenho vai
 * embutido no HTML: nada de requisição externa, nada de FOUT, e o ícone
 * herda a cor do texto (currentColor).
 *
 * Uso: <?= Icon::svg('usuarios', 'w-5 h-5') ?>
 */
class Icon
{
    /** Conteúdo interno de cada <svg> (viewBox 0 0 24 24) */
    private const PATHS = [
        // Navegação / módulos
        'painel'         => '<rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/>',
        'usuarios'       => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'setores'        => '<path d="M3 21h18"/><path d="M5 21V7l8-4v18"/><path d="M19 21V11l-6-4"/><path d="M9 9v.01"/><path d="M9 12v.01"/><path d="M9 15v.01"/><path d="M9 18v.01"/>',
        'tuss'           => '<rect x="8" y="2" width="8" height="4" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M8 14h2l1.5-3 2 5 1.5-2h2"/>',
        'cirurgias'      => '<circle cx="6" cy="6" r="3"/><path d="M8.12 8.12 12 12"/><path d="M20 4 8.12 15.88"/><circle cx="6" cy="18" r="3"/><path d="M14.8 14.8 20 20"/>',
        'especialidades' => '<path d="M4.8 2.3A.3.3 0 1 0 5 2H4a2 2 0 0 0-2 2v5a6 6 0 0 0 6 6v0a6 6 0 0 0 6-6V4a2 2 0 0 0-2-2h-1a.2.2 0 1 0 .3.3"/><path d="M8 15v1a6 6 0 0 0 6 6v0a6 6 0 0 0 6-6v-4"/><circle cx="20" cy="10" r="2"/>',
        'permissoes'     => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/><circle cx="12" cy="11" r="2"/><path d="M12 13v3"/>',
        'auditoria'      => '<path d="M3 3v5h5"/><path d="M3.05 13A9 9 0 1 0 6 5.3L3 8"/><path d="M12 7v5l4 2"/>',
        'vendas'         => '<path d="M3 4h2l2.4 11.2a2 2 0 0 0 2 1.6h7.7a2 2 0 0 0 2-1.5L21 8H6"/><circle cx="10" cy="20" r="1.3"/><circle cx="17" cy="20" r="1.3"/><path d="M13.4 10.4h-1.6a1 1 0 0 0 0 2h1.2a1 1 0 0 1 0 2h-1.7"/><path d="M12.6 9.6v.8"/><path d="M12.6 14.4v.8"/>',
        'auditoria_hosp' => '<path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"/><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/><path d="M12 17.5v-11"/>',
        'comercial'      => '<path d="M3 7a2 2 0 0 1 2-2h5.2a2 2 0 0 1 1.4.6l8 8a2 2 0 0 1 0 2.8l-5.2 5.2a2 2 0 0 1-2.8 0l-8-8A2 2 0 0 1 3 12.2z"/><circle cx="7.5" cy="9.5" r="1.3"/><path d="M13 12.5h2.2a1.1 1.1 0 0 1 0 2.2h-1.4a1.1 1.1 0 0 0 0 2.2H16"/><path d="M14.6 11.6v1"/><path d="M14.6 16.9v1"/>',
        'coletas'        => '<path d="M12 3v12"/><path d="m8 11 4 4 4-4"/><path d="M3 15v4a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-4"/><path d="M7 7h.01"/><path d="M17 7h.01"/>',
        'fechamentos'    => '<path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/><path d="m9 16 2 2 4-4"/>',
        'motivos'        => '<path d="M9 6h12"/><path d="M9 12h12"/><path d="M9 18h12"/><path d="M4 6h.01"/><path d="M4 12h.01"/><path d="M4 18h.01"/>',
        'agenda'         => '<path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/><path d="M8 14h.01"/><path d="M12 14h.01"/><path d="M16 14h.01"/><path d="M8 18h.01"/><path d="M12 18h.01"/>',
        'medicos'        => '<path d="M4.8 2.3A.3.3 0 1 0 5 2H4a2 2 0 0 0-2 2v5a6 6 0 0 0 12 0V4a2 2 0 0 0-2-2h-1a.2.2 0 1 0 .3.3"/><path d="M8 15v1a6 6 0 0 0 12 0v-4"/><circle cx="20" cy="10" r="2"/>',
        'exames'         => '<path d="M9 2v6.6a2 2 0 0 1-.3 1L4 18a2 2 0 0 0 1.7 3h12.6a2 2 0 0 0 1.7-3l-4.7-8.4a2 2 0 0 1-.3-1V2"/><path d="M7.5 2h9"/><path d="M6.2 15h11.6"/>',
        'opme'           => '<path d="M12 3v18"/><path d="M7 7c-2 1.5-3 3.5-3 5.5C4 16 6.5 18 9 18"/><path d="M17 7c2 1.5 3 3.5 3 5.5C20 16 17.5 18 15 18"/><circle cx="12" cy="3" r="1.4"/><path d="M9 11h6"/>',
        'consultorios'   => '<path d="M3 21h18"/><rect x="4" y="5" width="16" height="16" rx="2"/><path d="M4 10h16"/><path d="M12 10v11"/><path d="M9 3h6"/><path d="M8.5 15.5h3"/><path d="M10 14v3"/>',
        // Prancheta com a cruz da saúde: a ficha do trabalhador
        'ocupacional'    => '<rect x="8" y="2" width="8" height="4" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M12 11v6"/><path d="M9 14h6"/>',
        // Carteirinha do plano: a pessoa e o cartão
        'cadastro'       => '<rect x="2" y="5" width="20" height="14" rx="2"/><circle cx="8" cy="11" r="2"/><path d="M5 16c.6-1.5 1.7-2 3-2s2.4.5 3 2"/><path d="M14 10h5"/><path d="M14 14h3"/>',
        // Folha com pulso: o questionário de saúde
        'declaracoes'    => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 15h1.5l1-2 1.5 4 1-2H16"/>',
        'subiu'          =>'<path d="m7 17 10-10"/><path d="M8 7h9v9"/>',
        'desceu'         => '<path d="m7 7 10 10"/><path d="M17 8v9H8"/>',
        'igual'          => '<path d="M5 9h14"/><path d="M5 15h14"/>',

        // Marca
        'logo'           => '<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8l1 1.1L12 21.2l7.8-7.7 1-1.1a5.5 5.5 0 0 0 0-7.8"/><path d="M3.5 12.5h4l1.5-3 2.5 6 2-4.5 1 1.5h5"/>',

        // Ações
        'novo'           => '<path d="M5 12h14"/><path d="M12 5v14"/>',
        'editar'         => '<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4Z"/>',
        'excluir'        => '<path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M10 11v6"/><path d="M14 11v6"/>',
        'ver'            => '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7"/><circle cx="12" cy="12" r="3"/>',
        'download'       => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5"/><path d="M12 15V3"/>',
        'upload'         => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m17 8-5-5-5 5"/><path d="M12 3v12"/>',
        'salvar'         => '<path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><path d="M17 21v-8H7v8"/><path d="M7 3v5h8"/>',
        'buscar'         => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
        'imprimir'       => '<path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14" rx="1"/>',
        'marcacoes'      => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/><path d="M22 19h-4"/>',
        'cancelar'       => '<circle cx="12" cy="12" r="10"/><path d="m4.9 4.9 14.2 14.2"/>',
        'pop'            => '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7z"/><path d="M15 2v5h5"/><path d="m8 13 1.5 1.5L12 12"/><path d="M14 17h3"/><path d="M8 17h2"/>',
        'ajuda'          => '<circle cx="12" cy="12" r="10"/><path d="M9.1 9a3 3 0 0 1 5.8 1c0 2-3 3-3 3"/><path d="M12 17h.01"/>',
        'cancelamentos'  => '<path d="M9 6h11"/><path d="M9 12h11"/><path d="M9 18h11"/><path d="m3 5 2 2 2-2.5"/><path d="m3 11 2 2 2-2.5"/><path d="m3 17 2 2 2-2.5"/>',
        'bloqueio'       => '<rect width="18" height="11" x="3" y="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
        'liberar'        => '<rect width="18" height="11" x="3" y="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 9.9-1"/>',
        'voltar'         => '<path d="m12 19-7-7 7-7"/><path d="M19 12H5"/>',
        'sair'           => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/>',
        'fechar'         => '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
        'check'          => '<path d="M20 6 9 17l-5-5"/>',
        'anterior'       => '<path d="m15 18-6-6 6-6"/>',
        'proximo'        => '<path d="m9 18 6-6-6-6"/>',
        'baixo'          => '<path d="m6 9 6 6 6-6"/>',

        // Estado / feedback
        'sucesso'        => '<circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/>',
        'erro'           => '<circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/>',
        'aviso'          => '<path d="m21.7 18-8-14a2 2 0 0 0-3.4 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.7-3"/><path d="M12 9v4"/><path d="M12 17h.01"/>',
        'info'           => '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/>',
        'vazio'          => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M9 15h6"/>',

        // Diversos
        'usuario'        => '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
        'codigos'        => '<path d="M12.6 2.6a2 2 0 0 0-1.4-.6H4a2 2 0 0 0-2 2v7.2a2 2 0 0 0 .6 1.4l8.2 8.2a2 2 0 0 0 2.8 0l7.2-7.2a2 2 0 0 0 0-2.8z"/><circle cx="6.5" cy="6.5" r="1.5"/>',
        'autorizacoes'   => '<path d="M20 13c0 5-3.5 7.5-7.7 8.9a1 1 0 0 1-.6 0C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.2-2.7a1 1 0 0 1 1.5 0C14.5 3.8 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/>',
        'senha'          => '<rect width="18" height="11" x="3" y="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
        'cpf'            => '<rect width="20" height="14" x="2" y="5" rx="2"/><path d="M14 10h4"/><path d="M14 14h3"/><circle cx="8" cy="11" r="2"/><path d="M5.5 16a3 3 0 0 1 5 0"/>',
        'medico'         => '<path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M12 10v6"/><path d="M9 13h6"/>',
        'calendario'     => '<path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/>',
        'anexo'          => '<path d="m21.4 11.1-9.2 9.2a6 6 0 0 1-8.5-8.5l9.2-9.2a4 4 0 0 1 5.7 5.7l-9.2 9.2a2 2 0 0 1-2.9-2.9l8.5-8.5"/>',
        'arquivo'        => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/>',
        'imagem'         => '<rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-4.6-4.6a2 2 0 0 0-2.8 0L3 21"/>',
        'menu'           => '<rect width="18" height="18" x="3" y="3" rx="2"/><path d="M9 3v18"/>',
        'claro'          => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.9 4.9 1.4 1.4"/><path d="m17.7 17.7 1.4 1.4"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.3 17.7-1.4 1.4"/><path d="m19.1 4.9-1.4 1.4"/>',
        'escuro'         => '<path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9"/>',
        'protocolo'      => '<path d="M4 22h14a2 2 0 0 0 2-2V7l-5-5H6a2 2 0 0 0-2 2v4"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M2 13h4"/><path d="M2 17h4"/><path d="M2 9h4"/>',
        'admin'          => '<path d="M12 2 4 5v6c0 5.5 3.4 10.7 8 12 4.6-1.3 8-6.5 8-12V5z"/><path d="m9 12 2 2 4-4"/>',
    ];

    public static function svg(string $nome, string $classe = 'w-5 h-5', float $traco = 1.75): string
    {
        $conteudo = self::PATHS[$nome] ?? self::PATHS['info'];

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" '
            . 'stroke="currentColor" stroke-width="' . $traco . '" stroke-linecap="round" '
            . 'stroke-linejoin="round" class="' . htmlspecialchars($classe, ENT_QUOTES) . '" aria-hidden="true">'
            . $conteudo . '</svg>';
    }

    public static function existe(string $nome): bool
    {
        return isset(self::PATHS[$nome]);
    }
}
