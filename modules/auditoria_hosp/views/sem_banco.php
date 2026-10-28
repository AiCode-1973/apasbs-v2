<?php
/**
 * O módulo existe no código, mas o banco do AuditorHosp não está acessível.
 *
 * É uma situação de instalação, não um erro de quem está usando — então a
 * tela diz o que falta fazer, e o resto do sistema continua funcionando.
 *
 * @var string $motivo 'nao_configurado' ou 'sem_acesso'
 */
declare(strict_types=1);
?>

<div class="mb-5">
    <h1 class="titulo-pagina">Auditoria Hospitalar</h1>
    <p class="sub-pagina">Contas de internação e de PA/Ambulatório</p>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">
            <?= $motivo === 'nao_configurado'
                ? 'Falta apontar o banco'
                : 'O banco não aceitou a conexão' ?>
        </h2>
    </div>

    <div class="card-corpo space-y-4">
        <?php if ($motivo === 'nao_configurado'): ?>
            <p class="text-sm text-slate-600 dark:text-slate-400">
                Este módulo trabalha nas tabelas que já existem no banco do
                <strong>AuditorHosp</strong> — nada é copiado. Para ligá-lo, preencha
                em <code class="mono">config/env.php</code>:
            </p>

            <pre class="overflow-auto rounded-lg bg-marine-950 p-3 text-[0.8125rem] leading-relaxed text-slate-100">'aud_db_host' =&gt; 'localhost',
'aud_db_name' =&gt; 'dema5738_auditorhosp',
'aud_db_user' =&gt; 'dema5738_apasbs',
'aud_db_pass' =&gt; '…a senha do banco do APASBS…',</pre>

            <p class="text-sm text-slate-600 dark:text-slate-400">
                E, na hospedagem, dê a esse usuário permissão no banco do AuditorHosp —
                o comando está em <code class="mono">sql/aud_acesso.sql</code>.
            </p>
        <?php else: ?>
            <p class="text-sm text-slate-600 dark:text-slate-400">
                As credenciais estão preenchidas em <code class="mono">config/env.php</code>,
                mas o banco recusou a conexão. Quase sempre é uma destas três:
            </p>

            <ul class="ml-4 list-disc space-y-1.5 text-sm text-slate-600 dark:text-slate-400">
                <li>o usuário do APASBS ainda não tem permissão no banco do AuditorHosp
                    — veja <code class="mono">sql/aud_acesso.sql</code>;</li>
                <li>o host está errado: com os dois bancos no mesmo servidor, é
                    <code class="mono">localhost</code>;</li>
                <li>a senha mudou de um lado e não do outro.</li>
            </ul>

            <p class="text-sm text-slate-600 dark:text-slate-400">
                O motivo exato está no log de erros do servidor — ele não aparece aqui
                de propósito, porque esta tela é pública para quem já entrou no sistema.
            </p>
        <?php endif; ?>
    </div>
</div>
