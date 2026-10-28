-- APASBS v2 — atualiza os nomes de ícone na tabela `modulos`.
--
-- A v1 gravava nomes do Bootstrap Icons ("bi-people"). A v2 usa SVG próprio,
-- com nomes simples ("usuarios"). O sistema já corrige isso sozinho no
-- primeiro acesso (Modules::sync), mas o script deixa explícito.
--
-- Como o clone usa o MESMO banco da v1, rodar isto não quebra o sistema
-- antigo: a coluna `icone` só é lida para desenhar o menu, e a v1 continua
-- funcionando (os ícones dela vêm do próprio código, não do banco).

INSERT INTO modulos (nome, slug, icone) VALUES
    ('Usuários',       'usuarios',       'usuarios'),
    ('Setores',        'setores',        'setores'),
    ('TUSS',           'tuss',           'tuss'),
    ('Cirurgias',      'cirurgias',      'cirurgias'),
    ('Especialidades', 'especialidades', 'especialidades'),
    ('Permissões',     'permissoes',     'permissoes'),
    ('Auditoria',      'auditoria',      'auditoria')
ON DUPLICATE KEY UPDATE nome = VALUES(nome), icone = VALUES(icone);
