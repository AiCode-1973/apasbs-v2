<?php
/**
 * Configuração de ambiente do APASBS — apasbs.app.br
 *
 * Copie este arquivo para config/env.php na pasta do sistema, no provedor.
 *
 * FALTA PREENCHER: as duas senhas marcadas com <<< ABAIXO.
 * Pegue-as no painel do provedor, em MySQL › Usuários. Se não souber a
 * senha, redefina por lá e escreva a nova aqui.
 *
 * Confira também os nomes do banco e do usuário: estão preenchidos com os
 * que vínhamos usando, mas quem manda é o que o painel mostrar.
 *
 * Este arquivo nunca entra em zip nem em repositório — é o único lugar do
 * sistema onde há senha escrita. Guarde uma cópia no seu gerenciador de
 * senhas: são quatro linhas que valem uma tarde de trabalho quando faltam.
 */
declare(strict_types=1);

return [
    // ── Banco do APASBS ─────────────────────────────────────────────────
    'db_host'    => '186.209.113.107',
    'db_name'    => 'dema5738_apasbs',
    'db_user'    => 'dema5738_apasbs',
    'db_pass'    => 'Dema@1973',                  // <<< a senha do banco do APASBS
    'db_charset' => 'utf8mb4',

    // false em produção: com true, um erro mostra caminho de arquivo e
    // detalhe de banco para quem estiver acessando o site.
    'debug'      => false,

    // 'auto' detecta HTTPS sozinho e marca o cookie de sessão como Secure.
    'https'      => 'auto',

    // Tentativas de login erradas antes do bloqueio, e a duração dele.
    'login_max_tentativas' => 5,
    'login_bloqueio_seg'   => 900,

    // ── Banco do AuditorHosp (módulo Auditoria Hospitalar) ──────────────
    // O módulo lê e grava nas tabelas que já existem nesse banco. Deixando
    // as quatro chaves em branco, ele avisa que não foi configurado e o
    // resto do sistema não sente falta.
    //
    // O usuário abaixo precisa de permissão nesse banco — ver sql/aud_acesso.sql.
    'aud_db_host' => 'localhost',
    'aud_db_name' => 'dema5738_auditorhosp',
    'aud_db_user' => 'dema5738_apasbs',
    'aud_db_pass' => 'Dema@1973',                 // <<< a senha do banco do AuditorHosp
];
