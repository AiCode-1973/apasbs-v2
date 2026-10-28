-- APASBS v2 — Auditoria Hospitalar: acesso ao banco do AuditorHosp
--
-- ESTE SCRIPT NÃO CRIA TABELA NENHUMA.
--
-- O módulo Auditoria Hospitalar trabalha nas tabelas que já existem no banco do
-- AuditorHosp (`internacoes`, `pa_ambulatorio`, `convenios`, `motivos_glosa`…).
-- Elas continuam de pé, servindo o sistema antigo, enquanto o módulo novo lê e
-- grava nas mesmas linhas. Duas cópias da mesma conta divergiriam na primeira
-- correção; por isso é uma segunda conexão, e não uma importação.
--
-- O que falta, então, é permissão: o usuário do banco do APASBS precisa poder
-- entrar no banco do AuditorHosp. Rode isto como root (ou pelo painel da
-- hospedagem, em "Adicionar usuário ao banco de dados"), trocando os nomes:
--
--   banco do AuditorHosp : dema5738_auditorhosp
--   usuário do APASBS    : dema5738_apasbs
--
-- Se os dois bancos estiverem no mesmo servidor MySQL — que é o caso quando os
-- nomes compartilham o prefixo da conta —, 'localhost' é o host certo.

GRANT SELECT, INSERT, UPDATE, DELETE
    ON `dema5738_auditorhosp`.*
    TO 'dema5738_apasbs'@'localhost';

FLUSH PRIVILEGES;

-- Depois disto, preencha em config/env.php:
--
--   'aud_db_host' => 'localhost',
--   'aud_db_name' => 'dema5738_auditorhosp',
--   'aud_db_user' => 'dema5738_apasbs',
--   'aud_db_pass' => '…a mesma senha do banco do APASBS…',
--
-- Para conferir se passou, rode logado no usuário do APASBS:
--
--   SELECT COUNT(*) FROM `dema5738_auditorhosp`.`pa_ambulatorio`;
