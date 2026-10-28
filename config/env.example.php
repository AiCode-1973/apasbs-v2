<?php
/**
 * MODELO de configuração de ambiente.
 *
 * Copie este arquivo para config/env.php e preencha com os dados reais:
 *     cp config/env.example.php config/env.php
 *
 * config/env.php NÃO é versionado — ver .gitignore.
 */
declare(strict_types=1);

return [
    'db_host'    => 'localhost',
    'db_name'    => 'apasbs',
    'db_user'    => 'root',
    'db_pass'    => '',
    'db_charset' => 'utf8mb4',

    // true  → mostra erros na tela (use apenas em desenvolvimento)
    // false → erros apenas no log do servidor (obrigatório em produção)
    'debug'      => false,

    // Marca o cookie de sessão como Secure. Ative quando o site rodar em HTTPS.
    // Em 'auto' o sistema detecta HTTPS sozinho.
    'https'      => 'auto',

    // Tentativas de login erradas antes do bloqueio temporário, e duração em segundos.
    'login_max_tentativas' => 5,
    'login_bloqueio_seg'   => 900,

    // ── Auditoria Hospitalar ────────────────────────────────────────────
    // O módulo lê e grava no banco do AuditorHosp, que continua existindo com
    // as tabelas dele. Deixe em branco para desligar o módulo: ele avisa que
    // não foi configurado, e o resto do sistema não sente falta.
    //
    // O usuário abaixo precisa de permissão nesse banco — ver sql/aud_acesso.sql.
    'aud_db_host' => '',
    'aud_db_name' => '',
    'aud_db_user' => '',
    'aud_db_pass' => '',
];
