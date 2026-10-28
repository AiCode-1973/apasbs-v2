# APASBS v2 — Sistema de Gestão em Saúde

Clone funcional do sistema APASBS: mesmas funcionalidades, mesmo banco de dados,
com as pendências de segurança corrigidas e a interface reconstruída em
**Tailwind CSS 4 + Alpine.js**.

Continua sendo **PHP 8 puro**, sem framework, rodando no XAMPP/Apache — a
arquitetura MVC própria da v1 foi preservada linha a linha.

---

## O que mudou em relação à v1

### Segurança (as pendências listadas no README original)

| Pendência da v1 | Como ficou na v2 |
| --- | --- |
| Nenhum formulário tinha token CSRF | `core/Csrf.php`. Todo formulário traz `Csrf::campo()` e toda action de escrita chama `Csrf::exigir()`. Token inválido → HTTP 419 |
| Exclusões por link `GET` | Todas as exclusões viraram `POST` com token, disparadas por um modal de confirmação |
| Não havia tela para definir `is_admin` | Caixa "Administrador" no formulário de usuários, visível apenas para quem já é admin |
| `setup.php` exposto com senha padrão | Removido. No lugar, `tools/criar_admin.php`, que **só roda pela linha de comando** e exige que você escolha a senha |
| `Auth::temPermissao()` consultava o banco a cada chamada | Permissões carregadas uma vez e guardadas na sessão, com TTL de 60 s |

### Correções adicionais

- **Upload de anexos:** a extensão gravada em disco passa a ser derivada do
  MIME detectado, não do nome enviado. Na v1 um arquivo `shell.php` com
  conteúdo de GIF era gravado como `.php`; agora vira `.gif`.
- **Sessão endurecida:** cookie `HttpOnly` + `SameSite=Lax` + `Secure`
  automático em HTTPS, `use_strict_mode`, expiração por ociosidade (2 h),
  `session_regenerate_id` no login e limpeza completa no logout.
- **Cabeçalhos de segurança:** CSP, `X-Content-Type-Options`, `X-Frame-Options`,
  `Referrer-Policy` e `Permissions-Policy` em toda resposta.
- **Força bruta no login:** bloqueio temporário após 5 tentativas erradas
  (configurável), mensagem genérica e verificação de hash descartável para não
  revelar quais CPFs existem.
- **Busca:** curingas `%` e `_` escapados; `LIMIT`/`OFFSET` enviados como
  inteiro (com prepares nativos, passar string quebra no MySQL).
- **Roteador:** só métodos públicos declarados no próprio controller viram rota —
  helpers herdados não podem ser chamados pela URL.
- **Exclusões com vínculo:** setor com usuários e especialidade com cirurgias
  são bloqueados com mensagem clara, em vez de estourar erro de chave estrangeira.
- **Último administrador:** o sistema impede excluir, inativar ou rebaixar o
  único admin ativo.
- **Senha:** mínimo de 8 caracteres, com ao menos uma letra e um número
  (a v1 exigia 6 caracteres quaisquer). Vale só para senhas novas — ninguém é
  forçado a trocar.
- **Download de anexos:** validação de caminho com `realpath`, `nosniff`,
  CSP `sandbox` e nome de arquivo corretamente codificado.

### Novidades

- **Módulo Auditoria** — registra quem criou, editou, excluiu, entrou, saiu e
  baixou o quê, com IP e data. Tela com filtros por texto, módulo, ação e período.
- **Modo escuro** com preferência salva no navegador.
- **Interface nova**: Tailwind CSS compilado localmente, ícones SVG embutidos
  (nada de CDN), sidebar recolhível, gaveta no celular, modais de confirmação,
  máscara de CPF, autocomplete TUSS com teclado, pré-visualização de anexos,
  estados vazios e busca nas listagens.
- **Zero dependência externa em tempo de execução**: CSS, JS e ícones são
  servidos pelo próprio servidor. O sistema funciona sem internet.

### O que **não** mudou

Regras de negócio, nomes de tabelas e colunas, validação de CPF por dígito
verificador, estrutura de rotas (`/?mod=…&action=…`), o registry de módulos
como fonte única de verdade, e o modelo de permissões `pode_ver` · `pode_criar` ·
`pode_editar` · `pode_excluir`.

---

## Instalação

### 1. Copiar para o servidor

Copie a pasta `apasbs-v2` para dentro do `htdocs` do XAMPP, por exemplo
`C:\xampp1\htdocs\apasbs-v2`, e acesse `http://localhost/apasbs-v2/`.

### 2. Banco de dados

O clone aponta para o **mesmo banco da v1** (`apasbs`). Ajuste
`config/env.php` se as credenciais forem diferentes:

```php
return [
    'db_host'    => 'localhost',
    'db_name'    => 'apasbs',
    'db_user'    => 'root',
    'db_pass'    => '',
    'db_charset' => 'utf8mb4',
    'debug'      => false,
    'https'      => 'auto',
    'login_max_tentativas' => 5,
    'login_bloqueio_seg'   => 900,
];
```

Rode os dois scripts aditivos (não alteram nenhuma tabela existente):

```bash
mysql -u root -p apasbs < sql/auditoria.sql
mysql -u root -p apasbs < sql/icones_v2.sql
mysql -u root -p apasbs < sql/central_agendamento.sql
mysql -u root -p apasbs < sql/central_agenda.sql
mysql -u root -p apasbs < sql/central_agenda_marcacoes.sql
mysql -u root -p apasbs < sql/central_cancelamento_motivos.sql
mysql -u root -p apasbs < sql/central_agenda_techsallus.sql
mysql -u root -p apasbs < sql/central_agenda_cpf.sql
mysql -u root -p apasbs < sql/ajuda.sql
mysql -u root -p apasbs < sql/central_pop.sql
mysql -u root -p apasbs < sql/central_pop_conteudo.sql
mysql -u root -p apasbs < sql/amb_consultorios.sql
mysql -u root -p apasbs < sql/amb_consultorios_mapa.sql
mysql -u root -p apasbs < sql/opme.sql
mysql -u root -p apasbs < sql/opme_dados.sql
mysql -u root -p apasbs < sql/central_coletas.sql
mysql -u root -p apasbs < sql/usuarios_senha.sql
mysql -u root -p apasbs < sql/amb_autorizacoes.sql
mysql -u root -p apasbs < sql/amb_autorizacoes_paciente.sql
mysql -u root -p apasbs < sql/amb_autorizacoes_data_autorizacao.sql
mysql -u root -p apasbs < sql/amb_autorizacoes_anexos.sql
mysql -u root -p apasbs < sql/amb_exame_codigos.sql
mysql -u root -p apasbs < sql/so_ocupacional.sql
mysql -u root -p apasbs < sql/pl_cadastro.sql
mysql -u root -p apasbs < sql/ds_declaracoes.sql
mysql -u root -p apasbs < sql/com_comercial.sql
mysql -u root -p apasbs < sql/com_capep_2026.sql
mysql -u root -p apasbs < sql/vd_vendas.sql
mysql -u root -p apasbs < sql/vd_pessoas.sql
```

> O módulo **Auditoria Hospitalar** não cria tabela: ele trabalha no banco do
> AuditorHosp. O que precisa ser feito é dar acesso ao usuário do APASBS —
> `sql/aud_acesso.sql` — e preencher `aud_db_*` em `config/env.php`.

> O módulo **Ajuda** grava arquivos em `uploads/ajuda/`. O PHP do XAMPP vem
> com `upload_max_filesize = 2M`, apertado para manual em PDF. Para aceitar
> arquivos maiores, ajuste no `php.ini` e reinicie o Apache:
>
> ```ini
> upload_max_filesize = 25M
> post_max_size       = 26M
> ```
>
> O sistema não promete o que o servidor não cumpre: a tela mostra o limite
> real e a mensagem de erro diz exatamente o que ajustar.

**Esqueceu de rodar um script?** O código de um módulo novo pode subir antes do
script do banco — e aí a tabela não existe. Em vez do `Fatal error` do PHP, com o
caminho dos arquivos na tela e o rastro de pilha, `core/Instalacao.php` devolve uma
página dizendo **qual tabela falta e qual script a cria**, e o resto do sistema
continua funcionando. O script é descoberto procurando o `CREATE TABLE` daquela
tabela dentro de `sql/`, não por uma lista mantida à mão: script novo na pasta já
passa a ser encontrado.

Opcional — deixa setembro de 2026 já lançado, para conferir contra a planilha
original em vez de digitar tudo de novo:

```bash
mysql -u root -p apasbs < sql/central_agenda_setembro_2026.sql
```

> As duas versões podem conviver no mesmo banco. A v1 continua funcionando.

### 3. Permissões de escrita

A pasta de anexos precisa ser gravável pelo servidor web:

```bash
chmod -R 755 uploads/cirurgias
```

No Windows/XAMPP normalmente já está.

### 4. Usuário administrador

Use o admin que já existe no banco. Se precisar criar ou redefinir um:

```bash
php tools/criar_admin.php "Nome Completo" 00000000191 "SenhaForte123"
```

O script recusa execução pelo navegador.

---

## Arquitetura

```
.htaccess              Reescreve tudo para index.php; bloqueia .sql/.md/.json e pastas de código
index.php              Front controller: sessão, cabeçalhos, rota, whitelist, dispatch
config/
  env.php              Credenciais reais (NÃO versionado)
  env.example.php      Modelo de configuração
  database.php         Carrega o env e expõe getPDO() (conexão única por requisição)
core/
  Security.php         Sessão endurecida + cabeçalhos de segurança
  Csrf.php             Token CSRF: campo(), exigir(), rotacionar()
  Auth.php             Login, permissões em cache na sessão, controle de força bruta
  Flash.php            Mensagens de uma requisição só
  Audit.php            Gravação do log de auditoria
  Controller.php       Base dos controllers: exigirPost(), redirect(), json(), auditar()
  Modules.php          Registry central dos módulos (fonte única de verdade)
  Paginacao.php        Rodapé de paginação das listagens (números + quantas por página)
  Navegacao.php        Lembra a lista de cada módulo como o usuário a deixou
modules/cadastro/
  Elegibilidade.php    As regras dos planos STANDARD — a única cópia delas
  View.php             View::render($modulo, $view, $dados, $layout)
  Icon.php             Ícones SVG inline
  helpers.php          e(), url(), like_escape(), formatar_cpf(), data_br()…
layouts/
  main.php             Sidebar, topbar, tema, flash, diálogo de confirmação
  auth.php             Casca da tela de login
modules/<nome>/
  Controller.php       Actions: lista, novo, salvar, editar, atualizar, excluir
  Model.php            Acesso ao banco via PDO com prepared statements
  views/*.php          lista.php, form.php, detalhe.php
assets/
  css/app.css          Tailwind já compilado (não editar à mão)
  js/tema.js           Tema e sidebar aplicados antes da primeira pintura
  js/app.js            Componentes Alpine
  js/alpine.min.js     Alpine.js local
  fonts/*.woff2        Fonte Inter (variável), servida localmente
image/                 Logo APAS: original, variantes claras, símbolo e favicon
src/input.css          Fonte do Tailwind (tokens, componentes)
sql/                   Scripts aditivos
tools/criar_admin.php  Criação do admin pela linha de comando
uploads/cirurgias/     Anexos por cirurgia (não versionado)
uploads/autorizacoes/  Guia e pedido médico por autorização (não versionado)
```

### Adicionar um módulo novo

1. Acrescente uma entrada no array `REGISTRY` de `core/Modules.php`
   (`slug`, `nome`, `icone`, `action`, `desc`, `grupo`, `page_title`).
2. Crie `modules/<slug>/Controller.php` (estendendo `Controller`), `Model.php`
   e as views.
3. Se usar um ícone novo, acrescente o traçado em `core/Icon.php`.

O registry alimenta a whitelist do roteador, a sidebar, os cards do painel, o
título do topbar e a sincronização da tabela `modulos`.

---

## Módulos

| Módulo | Descrição |
| --- | --- |
| **usuarios** | Login por CPF + senha (bcrypt), painel e CRUD de usuários, incluindo `is_admin` |
| **setores** | CRUD de setores; bloqueia exclusão com usuários vinculados |
| **especialidades** | CRUD das especialidades do CFM; bloqueia exclusão com cirurgias vinculadas |
| **tuss** | Consulta paginada à Tabela 22 da ANS (somente leitura), com filtro de vigência |
| **cirurgias** | Registro de cirurgias com autocomplete TUSS, filtros e anexos |
| **permissoes** | Matriz de permissões por usuário e módulo |
| **auditoria** | Histórico de ações com filtros e limpeza de registros antigos |
| **fechamentos** | Central de Agendamento: total de atendimentos fechado mês a mês |
| **motivos** | Central de Agendamento: motivos de fechamento de atendimento |
| **agenda** | Ambulatório: escala mensal de Colono/Combo/EDA, em calendário, lista, marcações e folha de impressão |
| **medicos** | Ambulatório: médicos que atendem, cada um com cor fixa na agenda |
| **exames** | Ambulatório: tipos de exame agendáveis, com contagem de vagas independente |
| **cancelamentos** | Ambulatório: mensagens que a atendente escolhe ao desmarcar um exame |
| **ajuda** | Biblioteca de manuais em PDF, aberta a quem tiver permissão de ver |
| **pop** | Central de Agendamento: procedimento operacional padrão, com mensagens prontas para copiar |
| **coletas** | Central de Agendamento: o que o robô do ReciclaLead trouxe, esperando conferência antes de virar fechamento |
| **autorizacoes** | Ambulatório: pedidos de exame que dependem de liberação do convênio, com convênios e procedimentos próprios |
| **codigos** | Ambulatório: com que código cada convênio fatura cada exame, com 1..N códigos por exame |
| **consultorios** | Ambulatório: mapa de sala semanal, com detecção de choque de horário e cadastros de profissionais e especialidades |
| **opme** | Órteses, próteses e materiais especiais: notas, parcelas, baixas e vencimentos do mês |

---

## Alterando o visual

O CSS é compilado — editar `assets/css/app.css` na mão é perda de tempo, o
próximo build sobrescreve. Mexa em `src/input.css` e recompile:

```bash
npm install          # só na primeira vez
npm run build        # gera assets/css/app.css minificado
npm run dev          # recompila a cada alteração enquanto você trabalha
```

As cores institucionais estão no bloco `@theme` de `src/input.css`
(`--color-marine-50` … `--color-marine-950`). Trocar essa escala muda o sistema
inteiro, claro e escuro.

Se você não for mexer no visual, **não precisa de Node instalado**: o
`assets/css/app.css` já vem pronto no pacote.

### Cache do navegador

CSS e JS saem dos layouts pelo helper `asset()`, que carimba a URL com a data
de modificação do arquivo:

```php
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
<!-- vira /assets/css/app.css?v=1787590785 -->
```

Sem esse carimbo o navegador continua usando o CSS e o JS que já tem guardados
depois de uma atualização, e a tela quebra de um jeito difícil de diagnosticar:
estilo faltando, componente do Alpine que não existe no arquivo antigo, botão
que some. Mudou o arquivo, muda a URL, o navegador baixa de novo — e enquanto
nada muda, o cache continua valendo normalmente.

Ao subir uma versão nova basta copiar os arquivos: **não é preciso pedir para
ninguém limpar o cache**.

---

## Requisitos

| Item | Versão |
| --- | --- |
| PHP | 8.1 ou superior (`pdo_mysql`, `mbstring`, `fileinfo`) |
| MySQL | 5.7+ / MariaDB 10.4+ |
| Servidor | Apache com `mod_rewrite` e `mod_headers` |
| Node.js | Opcional — só para recompilar o CSS |

---

## Refinamento visual (v2.1)

Ajustes feitos depois da primeira entrega, sem mexer em regra de negócio:

- **Tipografia embarcada** — a fonte Inter (variável, `.woff2`) é servida pelo
  próprio servidor, em `assets/fonts/`. Antes o CSS pedia Inter mas nenhum
  arquivo era entregue, então o navegador caía no Segoe UI.
- **Identidade única** — o logo real da APAS aparece na sidebar, no login e no
  favicon. Como o arquivo original é azul-escuro e some sobre o fundo navy,
  foram geradas variantes claras a partir dele:
  `logo_apas_claro.png`, `marca_apas_claro.png` (símbolo, para a sidebar
  recolhida), `marca_apas.png` e `favicon.png`. O original segue intacto.
- **Trilha de navegação real** — `Cadastros › Cirurgias › Novo registro`, com
  acentuação e níveis clicáveis, no lugar do slug cru (`CIRURGIAS / Cirurgias`).
- **Densidade de sistema, não de site** — escala tipográfica e espaçamentos
  reduzidos em torno de 15%: cabe mais informação na tela sem apertar.
- **Ações de linha neutras** — ver, editar e excluir usam o mesmo cinza; a cor
  só aparece no hover. Antes a lixeira vermelha era o elemento mais chamativo
  de cada linha da tabela.
- **Controles próprios** — checkbox e seta do select desenhados no CSS, com
  área de clique de 40 px na matriz de permissões (os nativos do Windows
  destoavam, principalmente no modo escuro).
- **Uma superfície por tela** — a barra de busca e filtros passou para dentro
  do card da tabela, em vez de flutuar como um segundo cartão acima dele.
- **Topbar limpo** — saíram a data decorativa e o rodapé; ficou a trilha,
  o botão de recolher e o alternador de tema.
- **Estilos de impressão** — sidebar, topbar e botões somem ao imprimir
  (útil na tela de detalhe da cirurgia). Use a classe `nao-imprimir` para
  esconder qualquer outro elemento no papel.

Tudo isso vive em `src/input.css`, `layouts/` e `core/Icon.php`. Para recompilar
o CSS depois de qualquer ajuste: `npm run build`.

---

## Refinamento visual — parte 2 (v2.2)

- **Painel com dados reais** — no lugar dos cartões vazios: faixa de indicadores
  (com o total de cirurgias do mês), tabela das últimas cirurgias, distribuição
  por especialidade e atividade recente vinda da auditoria. Cada bloco só
  aparece se o usuário tiver permissão no módulo correspondente; as consultas
  ficam em `modules/usuarios/PainelModel.php` e cada uma falha em silêncio se a
  tabela não existir.
- **Gráfico de barras de série única** — um só matiz da paleta institucional
  (nada de arco-íris), barra fina com ponta arredondada, valor rotulado ao lado
  e `aria-label` por linha. O contraste da barra contra a superfície foi
  validado nos dois modos.
- **Formulários em duas colunas** — título e explicação da seção à esquerda,
  campos à direita, no padrão de tela de configuração. As classes são
  `.form-secao`, `.form-campos` e `.form-largo`; a divisão entre seções é uma
  linha, não um card novo.
- **Matriz de permissões** — a coluna do módulo fica fixa na rolagem horizontal
  (`.tabela-fixa`) e cada linha ganhou um resumo ao vivo ("3 de 4" / "sem
  acesso"), recalculado a cada clique. Mantivemos caixas de seleção em vez de
  interruptores: o formulário só grava ao salvar, e interruptor sugere efeito
  imediato.
- **Ficha de impressão da cirurgia** — botão *Imprimir* e folha de estilo de
  papel: cabeçalho com o logo, protocolo, data de emissão e quem emitiu; dados
  e anexos; campos de assinatura no rodapé. Sidebar, topbar e botões não vão
  para o papel. Use `nao-imprimir` para esconder e `so-impressao` para mostrar
  só no papel.
- **O rodapé das folhas de impressão** — o `@media print` escondia `footer`, e
  com ele sumia justamente o "impresso em … por …" das folhas (POP, agenda,
  mapa de sala, OPME, documentos da autorização). Nenhum layout tem footer
  próprio; o que precisa sumir no papel usa `.nao-imprimir`.
- **Botões com estado de envio** — o componente Alpine `envio` desabilita o
  botão e mostra um indicador enquanto o navegador processa, evitando registro
  duplicado por duplo clique.
- **Diálogo de confirmação** — o formulário fica no HTML (com o token CSRF) e o
  botão só abre o diálogo, que lê do próprio formulário: `data-titulo`,
  `data-texto`, `data-acao` (o nome do botão que confirma) e `data-tom`. Sem
  `data-acao` ele continua sendo o diálogo vermelho de exclusão, que é o caso
  comum; `data-tom="normal"` serve para o que não destrói nada — fechar uma
  venda, aplicar uma comissão — e então o botão fica azul e com o nome da ação.
  Confirmar uma coisa num botão escrito "Excluir" é pedir para a pessoa clicar
  sem ler.

---

## Central de Agendamento

Grupo próprio na barra lateral, com dois módulos independentes — cada um tem a
sua linha na matriz de permissões, então dá para deixar alguém lançando número
sem poder mexer no cadastro de motivos.

### Fechamentos mensais (`central_fechamentos`)

Uma linha por competência: ano, mês, total de atendimentos e uma observação
opcional. Regras aplicadas no servidor:

- a competência é **única** — o mesmo mês não pode ser lançado duas vezes;
- **mês futuro é recusado**: só se fecha competência já encerrada ou a corrente;
- o total aceita texto colado do relatório (`1.248` vira `1248`);
- fica registrado quem fez o último lançamento e quando.

A listagem calcula sozinha a **variação percentual sobre o mês anterior** (numa
subconsulta só, sem uma ida ao banco por linha) e mostra total do período, média
mensal e quantidade de meses lançados. O filtro por ano só oferece anos que
realmente têm lançamento.

### Atendimentos por motivo (`central_fechamento_motivos`)

Dentro do próprio formulário do fechamento há uma tabela com os motivos ativos e
um campo de quantidade por linha. Só vira registro no banco o motivo com
quantidade maior que zero.

A soma é calculada **ao vivo** enquanto se digita e comparada com o total do mês:
o rodapé mostra "confere" ou a diferença exata. É conferência, não trava — o
fechamento salva mesmo divergente, porque nem sempre o detalhamento fecha na
mesma hora. A listagem repete o indicador, então dá para ver de relance quais
meses estão detalhados e quais estão inconsistentes.

Motivo inativo que já tenha lançamento continua aparecendo no formulário daquele
fechamento (marcado como inativo), para o número não sumir da tela e ser apagado
sem ninguém perceber. E motivo já usado em algum fechamento não pode ser
excluído — só inativado.

### Motivos de fechamento (`central_motivos`)

Cadastro de apoio com código interno, nome, descrição e situação. O código é
normalizado para caixa alta sem espaço e é único, assim como o nome. Motivos
inativos somem das listas de escolha mas seguem no histórico.

> Depois de rodar `sql/central_agendamento.sql`, libere os dois módulos para
> quem vai usar em **Permissões**. Administradores já enxergam tudo.

---

## Ambulatório — agenda de Colono / Combo / EDA

Os três módulos (**Agenda**, **Médicos** e **Exames**) ficam no grupo
**Ambulatório** da sidebar. A escala é do ambulatório; quem marca os exames é
a Central de Agendamento, que tem grupo próprio (Fechamentos e Motivos).

Grupos são só um rótulo de seção definido em `Modules::GRUPOS` — mudar um
módulo de grupo é trocar uma linha no registry, sem migração de banco: a
tabela `modulos` guarda slug, nome e ícone, não o grupo.

A escala chega todo mês em texto corrido ("Dr. Ettore — terças à tarde: dias
01, 15, 22, 29 — até 5 combos por dia"). O módulo guarda exatamente isso, sem
inventar regra semanal automática: a escala muda de mês para mês, então cada
dia é lançado como está no papel.

### Bloco (`central_agenda_blocos`)

Um **bloco** é *um médico, num dia, num turno* (manhã ou tarde). A chave única
`(medico_id, data, turno)` impede lançar o mesmo médico duas vezes no mesmo
período — se faltar vaga, aumenta-se a capacidade do bloco que já existe.

O bloco tem ainda a marca **`techsallus`**: um sim/não que a central usa para
registrar que a agenda daquele bloco já foi criada no TechSallus. Quando
marcada, aparece um “✓ TechSallus” dentro do bloco no calendário, na lista e na
folha impressa. Bloco sem a marca simplesmente não mostra nada — a ausência é
que significa pendente.

### Vagas por tipo de exame (`central_agenda_vagas`)

Dentro do bloco, cada tipo de exame tem contagem própria: o mesmo período pode
ter 2 Combos e 4 Colonos ao mesmo tempo. A tabela guarda só a `capacidade` —
quanto cabe. Quanto já foi marcado é contado das marcações (abaixo), nunca
digitado.

### Marcações (`central_agenda_marcacoes`)

Uma linha por exame marcado: bloco, tipo de exame, **CPF** e nome da paciente,
e o **atendente**, que é o usuário logado — o sistema pega sozinho, ninguém digita
nem escolhe de uma lista. `atendente_nome` guarda o nome por escrito além do
id, então o registro continua dizendo quem marcou mesmo que o usuário seja
removido do sistema um dia.

O número de "marcadas" de cada vaga é `COUNT(*)` destas linhas com
`status = 'marcada'`. Como não existe um segundo lugar guardando esse número,
não há como os dois discordarem.

O CPF é guardado só com os 11 dígitos — a formatação é coisa de tela — e passa
pelo mesmo `cpf_valido()` que o cadastro de usuários usa (dígitos verificadores
e recusa de sequências repetidas). Marcações anteriores à mudança ficam com o
campo vazio: o sistema não teria como adivinhar.

A mesma paciente não pode ser marcada duas vezes para o **mesmo tipo de exame**
no mesmo bloco. Tipos diferentes no mesmo bloco continuam permitidos.

A gravação confere a vaga dentro da mesma transação, com `SELECT … FOR UPDATE`
na linha da vaga. Duas atendentes clicando ao mesmo tempo na última vaga: uma
marca, a outra recebe *"a última vaga acabou de ser preenchida"*.

Um bloco com paciente marcada não pode ser excluído, e a capacidade não pode
ser reduzida abaixo do que já foi marcado.

### Cancelamento (`central_cancelamento_motivos`)

Cancelar não apaga: a linha continua, com `status = 'cancelada'`, quem cancelou,
quando e por quê. A vaga volta a ficar livre na hora.

O motivo vem de um **seletor** com as mensagens cadastradas em
**Ambulatório › Cancelamentos**, na ordem definida lá. A última opção é
*"Outro motivo…"*, que revela um campo de texto — e o que for escrito entra no
cadastro para as próximas vezes, no fim da lista.

Espaços repetidos são normalizados e a colação é insensível a maiúsculas, então
"Paciente desistiu" e "paciente  DESISTIU" caem no mesmo registro em vez de
virar dois.

O cadastro tem `ordem` (menor aparece primeiro; use 10, 20, 30… para encaixar
mensagens novas no meio depois) e `ativo`. Inativar tira a mensagem da lista da
atendente **sem** apagar o histórico: os cancelamentos que já a usaram continuam
mostrando o texto. Mensagem já usada não pode ser excluída — só inativada.

O servidor não confia na tela: `motivo_id` é conferido contra o banco, e um
cancelamento sem motivo é recusado.

> Esta lista **não** tem relação com os *Motivos de fechamento de atendimento*
> (`central_motivos`), que pertencem a Fechamentos. São coisas diferentes e
> vivem em tabelas separadas de propósito.

Apagar de vez também existe (permissão *Excluir*), mas serve para erro de
digitação — o texto do aviso deixa isso claro. Para desmarcar sem perder o
histórico, o caminho é Cancelar.

### Dias bloqueados (`central_agenda_bloqueios`)

Feriado, recesso, manutenção. O dia continua visível no calendário, hachurado,
**com o motivo escrito** — quem olha entende por que não abriu.

### Médicos (`central_medicos`)

Cadastro próprio do ambulatório; não mexe no campo de médico usado em Cirurgias.
Cada médico tem uma `cor` de 1 a 6, índice de uma paleta categórica validada
para daltonismo (protanopia, deuteranopia e tritanopia) nos modos claro e
escuro. **A cor nunca identifica sozinha:** o nome do médico aparece escrito em
todo bloco, toda linha e toda legenda. Quem não distingue as cores não perde
nenhuma informação — a cor só acelera a leitura de quem distingue.

As cores vivem em `src/input.css` como `.medico-1` … `.medico-6`, cada uma
definindo a variável `--medico`. Os componentes (`.med-ponto`, `.cal-bloco`,
`.barra`) leem essa variável, então trocar a paleta é mexer em um lugar só.

### Três visões da mesma competência

| Visão | Ação | Para quê |
| --- | --- | --- |
| Calendário | `?mod=agenda&action=mes` | Bater o olho no mês e achar buraco de agenda |
| Lista | `?mod=agenda&action=lista` | Conferir linha a linha contra a planilha recebida |
| Impressão | `?mod=agenda&action=imprimir` | Folha para pendurar na central, em dois formatos |
| Marcações | `?mod=agenda&action=marcacoes&bloco=<id>` | Marcar e cancelar exames de um bloco |

Médico e tipo de exame filtram as três primeiras, e o filtro acompanha a
navegação entre meses.

A impressão tem dois formatos, escolhidos por `?formato=`:

- **`calendario`** (padrão) — o mês inteiro numa página, em **paisagem**. Sete
  colunas de dia não cabem em retrato sem virar palavra cortada, então a folha
  emite `@page { size: A4 landscape }` só nesse formato. É uma `<table>`, não
  grid: a quebra de página de tabela é previsível.
- **`lista`** — agrupado por médico e depois dia a dia, em retrato. É o formato
  que mais se parece com a escala escrita que a central recebe, e por isso o
  melhor para conferir.

O botão *Imprimir* abre o formato correspondente à visão em que a pessoa está,
e a própria folha tem um seletor para trocar sem voltar.

Na folha, a cor do médico é um traço fino na margem do bloco — o nome vai
sempre escrito, porque a maioria das impressoras da casa é preto e branco. As
regras de impressão usam `print-color-adjust: exact` para que fundo, borda e a
hachura dos dias bloqueados não sejam descartados pelo navegador. Clicar num bloco do calendário abre as marcações dele —
é a operação do dia a dia; editar o bloco é uma ação secundária de dentro
dessa tela.

---

## Mapa de sala (`consultorios`)

Substitui a planilha "MAPA DE SALA": quem ocupa cada consultório, em cada dia da
semana e turno. Duas tabelas:

| Tabela | Guarda |
| --- | --- |
| `amb_salas` | as salas — nome, observação, ordem no mapa, ativa ou não |
| `amb_especialidades` | as etiquetas curtas do ambulatório (Cardio, Ortop. Joelho) |
| `amb_profissionais` | quem ocupa sala, com a especialidade habitual |
| `amb_ocupacoes` | sala × dia da semana × turno × horário × profissional |

A grade é **semanal e fixa**, não uma agenda de datas: não há coluna de data, e o
mapa vale enquanto ninguém mudar. **Sala livre não é registro** — livre é a
ausência de ocupação, e é o buraco na grade que a central procura quando chega um
médico novo.

Três campos merecem explicação:

- `periodicidade` — `semanal` ou `quinzenal` (o "15/15 DIAS" da planilha).
- `amb_profissionais.atividade` — acupuntura, anestesista e prova ventilatória
  ocupam sala como qualquer médico, mas não são uma pessoa só: podem estar em duas
  salas ao mesmo tempo. Marcadas assim, ficam fora do aviso de "mesma pessoa em
  dois lugares".
- `observacao` — o que a planilha escrevia entre parênteses ("a partir de outubro").

### A especialidade em dois lugares

`amb_profissionais.especialidade_id` é a habitual; `amb_ocupacoes.especialidade_id`
é a exceção daquele horário, e o mapa lê
`COALESCE(ocupacao.especialidade, profissional.especialidade)`. Quem atende gastro
em três dias e clínica no quarto fica com um cadastro só — a exceção mora na
ocupação, não numa segunda pessoa com o mesmo nome.

Antes esses dois campos eram texto digitado dentro da ocupação. `sql/amb_consultorios.sql`
migra: cria os cadastros a partir dos nomes distintos, liga cada ocupação, troca a
chave única de `profissional` para `profissional_id` e só então derruba as colunas
de texto. Cada passo confere o estado do banco antes de agir, então rodar de novo
(ou pela primeira vez, num banco limpo) não faz nada de errado.

### Cor por turno

`.mapa-item-manha` (âmbar) e `.mapa-item-tarde` (índigo) separam as duas grades na
tela e no papel. A cor nunca identifica sozinha: o título da grade continua escrito,
com uma bolinha da cor ao lado, e o horário está dentro de cada ocupação. O choque
de horário ficou em vermelho — âmbar virou "manhã", e problema precisa continuar
sendo a cor mais forte da tela.

### Conflitos

`ConsultorioModel::conflitos()` varre a grade inteira a cada abertura do mapa e
aponta dois casos: duas ocupações **na mesma sala** com horários que se cruzam, e
o **mesmo profissional** em duas salas ao mesmo tempo. Quinzenal com quinzenal é
ignorado — podem ser semanas alternadas.

O sistema **avisa, não impede**. Um choque pode ser conhecido e já resolvido no
combinado da casa; o que não pode é passar despercebido.

### Carga inicial

`sql/amb_consultorios_mapa.sql` é gerado por `tools/gerar_mapa_salas.py`, que
guarda o conteúdo da planilha em estrutura. Corrigir um horário é mudar uma linha
em Python e rodar:

```bash
python3 tools/gerar_mapa_salas.py > sql/amb_consultorios_mapa.sql
```

O script usa `ON DUPLICATE KEY UPDATE` sobre a chave
`(sala_id, dia_semana, turno, hora_inicio, profissional)`: reexecutar restaura as
ocupações que vieram da planilha e não toca no que foi cadastrado pela tela.

---

## OPME — órteses, próteses e materiais especiais

Substitui a planilha "Opme 2026 Atual", que tinha uma aba por mês e repetia a nota
inteira em cada mês onde caía uma parcela: **630 linhas para 265 notas**.

| Tabela | Guarda |
| --- | --- |
| `opme_fornecedores` | quem fornece o material |
| `opme_notas` | a nota fiscal: paciente, cirurgia, médico, fornecedor, valor |
| `opme_parcelas` | os vencimentos daquela nota |

A aba mensal não sumiu — virou consulta. `OpmeModel::vencimentos($ano, $mes)`
monta a mesma tela a partir das parcelas, e por isso ela sempre bate com as notas.

### O vencimento é a chave da parcela

`UNIQUE KEY uk_opme_parcela (nota_id, vencimento)`, e não `(nota_id, numero)`. No
arquivo original o rótulo "3/4" às vezes vinha repetido de um mês para o outro,
porque a linha era copiada e o número esquecido; a data sempre estava certa, já
que era ela que definia em qual aba a linha entrava. `renumerar()` conserta o
rótulo pela ordem de vencimento, e `numeracaoConfusa()` é o que acende o aviso na
tela da nota.

### Situação da nota

`normal`, `cancelada` ou `doacao`. As duas últimas ficam registradas para o
histórico e saem de todas as somas a pagar.

### Somas sem multiplicação

Na lista, as somas de parcela saem em **subconsulta**, não em JOIN: com JOIN, uma
nota de quatro parcelas multiplicaria o `valor_total` por quatro. Pela mesma razão
`totaisNotas()` são duas consultas — o mesmo `WHERE` precisaria aparecer duas
vezes na mesma instrução, e com prepares nativos um placeholder nomeado não pode
se repetir.

### Carga inicial

`sql/opme_dados.sql` é gerado por `tools/gerar_opme_dados.py`, que guarda as 265
notas em estrutura:

```bash
python3 tools/gerar_opme_dados.py > sql/opme_dados.sql
```

Três vencimentos foram corrigidos na importação, todos com o mês certo e o ano
errado — a regra aplicada foi "cada aba é um mês; se o mês bate e o ano não, vale
o ano da aba". Está comentado no gerador.

---

### Os campos de dinheiro

Valor total e valor de parcela usam `.campo-moeda`: um `<span class="moeda-simbolo">R$</span>`
antes do input, que ganha `pl-9` e `text-right`. O símbolo é decoração
(`aria-hidden`), nunca conteúdo do campo — o que trafega é só o número.

A formatação acontece **no blur**, não a cada tecla:

| Digitado | Ao sair do campo |
| --- | --- |
| `17800` | `17.800,00` |
| `17800,5` | `17.800,50` |
| `17.800,00` | `17.800,00` |
| `abc12x3` | `123,00` (o filtro do `@input` já derruba as letras) |

Máscara de centavos-da-direita foi descartada de propósito: ela transforma
`48900` em `489,00` sem aviso, e num módulo de notas de dezenas de milhares de
reais isso é um erro de cem vezes. Formatar só na saída deixa a pessoa digitar do
jeito que o papel está na mão.

`@focus="$event.target.select()"` e o campo vazio (em vez de `0,00`) na nota nova
resolvem o resto: clicar e digitar substitui, sem apagar antes.

No servidor nada disso é confiado ao navegador — `valor_br()` reinterpreta a
string recebida, e é ela que decide o que vai para o banco.

---

## POP — procedimento operacional padrão

Cada POP tem uma ficha (`central_pops`: código, título, categoria, versão, data de
revisão) e um corpo montado em **blocos** (`central_pop_blocos`), de quatro tipos:

| Tipo | Para quê |
| --- | --- |
| `texto` | Parágrafo corrido. |
| `passos` | Lista numerada — cada linha do conteúdo vira um passo. |
| `alerta` | Caixa destacada, para o que não pode ser esquecido. |
| `mensagem` | Texto pronto, com botão **Copiar** na tela. |

O tipo `mensagem` é o que resolve o problema real da central: a atendente copia o
texto inteiro com um clique em vez de reescrever no WhatsApp. O botão tenta
`navigator.clipboard` e cai para um `<textarea>` escondido quando o sistema roda em
HTTP na rede interna, onde a API do navegador não está disponível.

A folha impressa (`views/imprimir.php`) usa o mesmo cabeçalho das outras do sistema:
marca do Hospital Santo Expedito à esquerda, código/título/categoria no meio e a da
APAS à direita, com `print-color-adjust: exact` para o Chrome não imprimir as marcas
lavadas.

A busca da lista entra **dentro do conteúdo dos blocos** — é assim que alguém acha
"Mounjaro" ou "CAPEP" sem saber em qual procedimento a regra está escrita.

### Conteúdo inicial

`sql/central_pop_conteudo.sql` é **gerado** por `tools/gerar_pop_conteudo.py` a partir
das *Orientações EDA e Colono 2.0*: 12 roteiros (endoscopia e colonoscopia × com e sem
internação × manhã e tarde × com e sem anestesista) e 1 POP com as 9 regras gerais.

Para corrigir um texto, edite o script e gere de novo:

```bash
python3 tools/gerar_pop_conteudo.py > sql/central_pop_conteudo.sql
```

> Rodar o script de conteúdo de novo **reescreve** esses 13 POPs por completo, apagando
> e recriando os blocos. Edições feitas pela tela naqueles códigos são perdidas. POPs
> criados por você, com outro código, não são tocados.

`sql/central_pop_treinamento.sql` traz o **POP-CA-014 — Conferência dos dados do
paciente no agendamento** (nome, nascimento, convênio e telefone confirmados em voz
alta antes de concluir). Escrito à mão, fora do gerador, porque não sai das
*Orientações EDA e Colono*; segue a mesma forma — `ON DUPLICATE KEY` na ficha,
`DELETE` + `INSERT` nos blocos —, então é re-executável e não encosta nos outros
códigos.

Nas mensagens, o que a atendente precisa substituir vai entre colchetes —
`[DATA DO EXAME]`, `[HORÁRIO]`, `[NOME DO MÉDICO]`, `[DATA DA VÉSPERA]`. O documento
original trazia `20/07` e `08:00` como exemplo, o que se confunde com valor real.

---

## Ajuda — biblioteca de manuais

Os manuais ficam em `ajuda_manuais`, agrupados por `categoria` (texto livre,
sugerido por `<datalist>`) e ordenados por `ordem`. O arquivo em si vai para
`uploads/ajuda/`.

O upload usa o mesmo endurecimento dos anexos de Cirurgias: o MIME é detectado
**pelo conteúdo**, o arquivo recebe nome aleatório e a extensão é derivada do
MIME — nunca do nome enviado. Um `manual.php` com bytes de GIF vira um `.gif`
inofensivo, e um `.docx` é recusado (chega como `application/zip`, e não há
como distingui-lo de um zip com qualquer coisa dentro).

A entrega do arquivo passa por `?mod=ajuda&action=arquivo&id=N`, que confere a
permissão, valida o caminho com `realpath` e responde com
`Content-Security-Policy: sandbox` — o PDF abre no visualizador do navegador
sem virar uma porta de entrada. Com `&baixar=1` força download.

Permissões, como em todo módulo:

| Permissão | Quem precisa |
| --- | --- |
| **Ver** | Todo mundo. É o que abre a biblioteca e permite ler os manuais. |
| **Criar** · **Editar** | Quem publica e mantém os manuais. |
| **Excluir** | Apaga a ficha e o arquivo. Para tirar da vista sem apagar, use *despublicar*. |

> Módulo novo nasce sem permissão para ninguém. Depois de rodar o script,
> libere **Ver** em Permissões para todos os usuários — senão a Ajuda não
> aparece na sidebar de quem mais precisa dela.

---

## Coletas do ReciclaLead

Os números da central nascem no ReciclaLead, sistema de outra empresa. Um coletor
(`tools/coletar_reciclalead.php`) entra lá uma vez por semana, lê os relatórios da
competência e guarda o resultado no APASBS. Ele **não** grava fechamento.

Essa fronteira é a decisão de arquitetura do módulo. Fornecedor renomeia motivo
sem avisar; se o robô gravasse direto, o fechamento receberia o número torto e
ninguém saberia. Então o robô só enfileira, e uma pessoa confirma na tela
(`Central de Agendamento › Coletas`).

### As três tabelas

| Tabela | Guarda |
| --- | --- |
| `central_coletas` | Uma linha por tentativa: totais, tempos, contatos, `status` e a `resposta_bruta` da API. |
| `central_coleta_itens` | O detalhamento, com `tipo` em `motivo`, `fila` ou `atendente`. |
| `central_coleta_mapa` | O de/para: qual rótulo do ReciclaLead entra em qual motivo do APASBS. |

`status` é `pendente`, `confirmada`, `descartada` ou `erro`. A tentativa que falhou
entra como `erro` com a mensagem — falha silenciosa seria pior que falha visível.

### O de/para não adivinha

Na conferência, o `<select>` de cada rótulo só vem marcado quando a escolha já foi
feita antes e está em `central_coleta_mapa`. A semelhança de nome (`similar_text`,
corte em 82%) vira apenas um aviso na tela.

Foi assim depois de um teste: *"Agendamento não Realizado"* e *"Atendimento
realizado"* dão 84% de semelhança textual e querem dizer o contrário. O modelo hoje
descarta a sugestão quando um dos nomes nega (`não`, `sem`, `fora`) e o outro não,
mas mesmo passando por esse filtro a sugestão nunca marca sozinha.

### Idempotência

Rodar de novo a mesma competência não duplica: a coleta `pendente` daquele mês é
substituída, e a `confirmada` só gera uma nova quando o total mudou — aí a nova
carrega a mensagem avisando da diferença.

### Configuração e agendamento

`config/reciclalead.php` (a partir de `config/reciclalead.example.php`) guarda url,
e-mail e senha do usuário-robô. **Não vai para o Git nem para pacote de entrega**,
igual a `config/env.php`.

O robô precisa de um login só dele, sem verificação em duas etapas. O passo a passo
de agendar — Windows e Linux — está em `tools/AGENDAR.md`; o `.bat` pronto está em
`tools/coletar_reciclalead.bat`.

Para testar sem rede e sem credencial, há uma amostra real capturada:

```bash
php tools/coletar_reciclalead.php --mes=2026-06 --simular=tools/exemplo_reciclalead.json --verboso
```

---

## Planilhas para baixar

`core/Planilha.php` monta um `.xlsx` de verdade — sem biblioteca, sem Composer.

```php
$p = new Planilha('Internações');
$p->cabecalho(['Guia', 'Competência', 'Valor inicial']);
$p->linha(['1234567', '2026-08-01', 12480.00], ['texto', 'mes', 'moeda']);
$p->baixar('contas-2026-08.xlsx');
```

Os tipos de coluna são `texto`, `numero`, `moeda`, `inteiro`, `pct`, `data` e
`mes`. Quem passa menos tipos do que colunas ganha `texto` no resto — é o que
acontece quando alguém acrescenta uma coluna e esquece.

- **Por que não CSV, que seria três linhas.** No CSV todo valor chega como
  texto, e quem recebe a planilha quer somar a coluna de glosa no mesmo minuto
  em que abre o arquivo. Com texto, isso vira uma conversa sobre separador
  decimal.
- **Um `.xlsx` é um zip com alguns XMLs dentro**, e é só isso que a classe
  monta: nada para instalar, nada para atualizar, nada que quebre num
  `composer update` que este projeto não tem.
- **Sem a extensão zip, cai para CSV** com BOM e ponto e vírgula, que é o que o
  Excel em português abre com dois cliques. Ficar sem download nenhum porque a
  hospedagem não tem uma extensão seria pior.
- **Texto vai inline**, sem tabela de strings compartilhadas: aquilo é
  otimização de tamanho e aqui só traria mais um XML para manter.
- **A data vira o número de dias desde 31/12/1899.** A contagem parte de
  1899-12-30 porque o 1900 do Excel tem um 29 de fevereiro que nunca existiu —
  o erro dele já vem embutido aí.
- **Cabeçalho congelado e autofiltro** na primeira linha: quem abre a planilha
  rola mil linhas e continua sabendo em que coluna está.

---

## Paginação das listagens

`Paginacao::barra()` desenha o rodapé de OPME, TUSS e Auditoria. Uma
implementação só: paginação diferente por tela obriga quem usa a reaprender o
mesmo gesto.

```php
echo Paginacao::barra([
    'modulo'    => 'opme',
    'pagina'    => $pagina,
    'total'     => $total,
    'porPagina' => $porPagina,
    'padrao'    => 50,               // o tamanho que não vai para a URL
    'param'     => 'pagina',         // o TUSS e a Auditoria usam 'p'
    'rotulo'    => ['nota', 'notas'],
    'base'      => $filtrosDaUrl,    // o que precisa sobreviver à troca de página
]);
```

Decisões que estão no código e valem explicar:

- **A janela de números tem largura fixa.** Sem isso a barra encolhe e cresce a
  cada clique, e o número que você ia clicar foge de baixo do cursor.
- **Página 1 e tamanho padrão não entram na URL.** O endereço da lista é sempre
  o mesmo, não uma variação com `?pagina=1&por=50`.
- **Trocar o tamanho volta para a primeira página.** Manter o número daria
  "página 12 de 5" e uma tela vazia.
- **O controller precisa limitar `$pagina` ao total de páginas.** Um filtro que
  entra encolhe a lista, e `?pagina=14` com 3 páginas devolveria nada.
- **`Paginacao::porPagina()` valida contra `TAMANHOS`.** Sem isso um
  `?por=999999` vira um SELECT sem limite prático.

O seletor de quantidade é um `<select data-navegar>` cujas opções são URLs, com
um ouvinte delegado no `app.js`. Não é `onchange` no HTML porque a CSP do sistema
bloqueia script embutido, atributo de evento incluído.

---

## A lista como o usuário a deixou

Filtrar por "Aguardando convênio", abrir um pedido, gravar e cair numa lista sem
filtro nenhum é perder o lugar na página. Quem trabalha uma fila inteira refazia o
filtro a cada registro — dezenas de vezes por dia.

`core/Navegacao.php` guarda na sessão, por módulo, os parâmetros da última lista
vista: filtros, busca, página e quantos por página.

```php
Navegacao::lembrar('autorizacoes');   // chamado pelo index.php a cada GET de lista
Navegacao::lista('autorizacoes');     // a URL de volta
url_lista('autorizacoes');            // o mesmo, para usar nas views
```

Como as peças se encaixam:

- **O `index.php` lembra sozinho**, em todo GET de `action=lista`. Nenhum
  controller precisa chamar nada, e módulo novo já nasce com o comportamento.
- **`Controller::redirect()` sem argumentos volta para a lista lembrada.** Como
  quase toda gravação termina em `$this->redirect()`, gravar passou a devolver a
  mesma tela sem tocar em controller nenhum.
- **As views usam `url_lista($mod)`** no "Cancelar" e na seta de voltar. Só em
  links do próprio módulo: `url('medicos', 'lista')` dentro do formulário da
  agenda é "ir para aquele cadastro", e não "voltar".
- **Em `lista.php` o link continua sendo `url($mod, 'lista')`** — lá ele é
  "limpar filtros", e trocá-lo faria o botão devolver o filtro que acabou de
  limpar. É a única exceção, e é proposital.
- **A lista limpa zera a memória**, porque ela também passa pelo `lembrar()`, só
  que sem parâmetro nenhum. O menu lateral é a saída para quem quer recomeçar.
- **Some no login e no logout.** No balcão o computador é o mesmo, e a tela
  filtrada pela atendente anterior não é a tela com que a próxima começa o dia.

O que vem da URL é filtrado antes de entrar na sessão: só nome de campo válido,
só valor escalar, com teto de tamanho e de quantidade. O que sai daqui volta a
virar URL — guardar sem olhar seria deixar a requisição seguinte escolher os
parâmetros.

---

## Cadastro de beneficiários do plano de saúde

O plano da APAS atende Policiais Militares, pensionistas e familiares. A unidade
é o CONTRATO — a família — e não a pessoa: `pl_contratos` → `pl_beneficiarios`,
com `pl_planos` e `pl_precos` ao lado.

### A regra que o regulamento não fechava

O documento de elegibilidade pede classificação automática e, no item 9.1, admite
que a lista de parentesco do STANDARD 2 e do STANDARD 4 é quase idêntica — só o
grau de parentesco não decide. A resposta, confirmada pela APAS: **quem decide é a
escolha da família entre contrato com ou sem coparticipação.**

- `sem_copart` → toda a família no STANDARD 4.
- `com_copart` → titular e núcleo próximo no STANDARD 1; demais parentes no
  STANDARD 2.

Isso explica a matriz da página 6 do documento: toda linha marcada no STANDARD 1
ou no 2 também está marcada no 4, porque o STANDARD 4 é o mesmo produto sem
coparticipação. A única exceção é **menor sob guarda/tutela**, marcado só no
STANDARD 4 — e implementado assim.

### As decisões que estão no código

- **`modules/cadastro/Elegibilidade.php` é a única cópia das regras.** A tela
  consulta o motor por JSON (`action=avaliar`) em vez de repetir a matriz em
  JavaScript: duas versões do regulamento divergiriam na primeira correção, e a
  errada seria justamente a que o usuário vê.
- **O motor sugere, não decide.** Devolve plano, motivo por escrito e pendências;
  quem confirma é quem cadastra. O plano escolhido muda a mensalidade, e o
  regulamento tem zonas cinzentas — decisão silenciosa aqui vira cobrança errada.
  O que a regra indicou fica gravado em `plano_sugerido`, para a conferência
  posterior mostrar de que a escolha divergiu.
- **A faixa etária não é coluna.** É calculada do nascimento na hora
  (`Elegibilidade::faixaDe`). A faixa muda no aniversário: guardada, ela
  envelheceria errada até alguém recadastrar todo mundo.
- **`pl_precos` é versionada por vigência.** Reajuste é um conjunto novo de dez
  linhas, não edição das antigas. `precosVigentes()` pega, por plano, a vigência
  mais recente que já começou — então dá para lançar o reajuste com antecedência,
  e a mensalidade de março continua explicável depois do reajuste de novembro.
- **Pendência documental não trava.** Falta de comprovação vira aviso e fica
  visível na tela do contrato. Travar por papel faria anotar o cadastro num
  caderno para digitar quando a pasta chegasse.
- **A reavaliação acontece ao abrir o contrato**, não só ao gravar: um filho que
  passou dos 18 desde o cadastro mudou de plano sem ninguém mexer em nada, e a
  tela precisa dizer isso.
- **O plano é do contrato, não da pessoa.** Confirmado pela APAS: o plano do
  titular vale para a família inteira, **sem exceção** — inclusive para quem a
  elegibilidade indicaria noutro plano (menor sob guarda, que a matriz só marca
  no STANDARD 4). Dependente novo já nasce no plano do titular; o motor
  continua rodando e o que ele indicaria fica em `plano_sugerido`, que é o que
  deixa a divergência auditável depois. Mudar o plano do titular não mexe em
  ninguém sozinho: `salvar_pessoa` manda para a tela `alinhar`, que mostra quem
  muda, de qual plano para qual, e a mensalidade da família antes e depois —
  `alinharPlanos()` só roda com o "sim". Recusando, o contrato fica com aviso
  (`desalinhados()`) até alguém aplicar. Dependente inativo também é movido: ele
  volta, e voltaria no plano velho. O ajuste dos contratos que já existiam está
  em `sql/pl_alinhar_planos.sql`, que lista antes, aplica e mostra o que sobrou
  de fora (titular sem plano).
- **A conta acompanha sozinha.** Nada de valor é copiado: `mensalidade()` lê
  plano + faixa etária na tabela vigente, então trocar o plano já muda o que a
  família paga.
- **Excluir o contrato leva as pessoas junto** (lixeira na lista e botão dentro
  do contrato; a confirmação diz quantas). `excluirContrato()` apaga
  beneficiários e contrato numa transação. A exceção é contrato com declaração
  de saúde: documento assinado, recusado no controller e travado também pela
  chave estrangeira de `ds_declaracoes`. Pelo mesmo motivo, beneficiário com
  linha em `ds_respostas` não pode ser excluído — vira inativo. As consultas a
  `ds_*` checam antes se as tabelas existem, para o cadastro funcionar sem o
  módulo de declarações instalado.

### Os valores de 2026

Carregados de "VALORES DE MENSALIDADES – 2026" com as vigências da observação da
planilha: STANDARD 1 e 2 em **01/03/2026** (+7,93%), STANDARD 4 em **01/11/2025**
(+8,45%). As células de data da planilha foram lidas pelo Excel como mês/dia (3
de janeiro e 11 de janeiro); vale o que está escrito na observação.

### O que o regulamento ainda não respondeu

Os itens 9.2 e 9.4 do documento seguem em aberto e estão implementados da forma
mais conservadora possível: enteado no STANDARD 4 resolve-se pelo tipo de
contrato, e dependência econômica é exigida como pendência só para netos, que é
onde o texto a menciona expressamente. Se a APAS fechar outra regra, o lugar de
mexer é `Elegibilidade.php` e mais nada.

---

## Declaração de saúde

O documento que registra doenças e lesões preexistentes (DLP) na adesão e decide
quem entra com Cobertura Parcial Temporária. `ds_declaracoes` (uma por contrato)
→ `ds_respostas` (uma linha por pessoa × item).

### As decisões que estão no código

- **Uma declaração por contrato, não por pessoa.** O papel tem uma coluna para o
  titular e seis para dependentes, e é assinado uma vez pelo contratante. Aqui é
  igual, sem o limite de seis.
- **Os 22 itens vivem no PHP** (`DeclaracaoModel::ITENS`), não numa tabela mantida
  pela tela: são o texto de um formulário regulado pela ANS. Mudar uma palavra
  muda o que a pessoa assinou — isso não é manutenção de cadastro.
- **Preenchimento por pessoa; grade só no papel.** A tela mostra os 22 itens de um
  beneficiário por vez, porque quem preenche está perguntando a alguém sobre a
  saúde dela e a grade obriga a acertar a coluna a cada resposta. `imprimir.php`
  monta a grade de 22 linhas × N colunas, que é onde ela faz sentido.
- **`resposta` NULL ≠ 'N'.** Em branco é "ainda não respondido", e a diferença
  importa: declaração com item vazio não está pronta para assinatura, e o sistema
  precisa saber disso para avisar ao concluir.
- **Item condicional sai da contagem.** O item 16 (grau de miopia) tem
  `'depende' => 15`: no papel a linha começa com "nos casos de…". Quem respondeu
  "não" em doenças dos olhos não tem grau a informar, e cobrá-lo deixaria a
  declaração eternamente incompleta por um campo que não se aplica.
- **Descrição só existe embaixo de um "sim".** Trocar a resposta para "não" apaga
  a descrição — ela descreveria uma doença que a pessoa acabou de dizer não ter.
  "Sim" sem descrição gera aviso e grava: não protege ninguém, mas travar faria
  perder o que já foi digitado com a família no balcão.
- **CPT é por pessoa, não por contrato.** Só quem declarou alguma coisa entra em
  cobertura parcial; marcar a família inteira diria que todos estão com cobertura
  suspensa. `data_contratacao` fica gravada na declaração, e não lida do contrato
  na hora: se a adesão for corrigida depois, a contagem que já estava valendo não
  muda sozinha.
- **Concluída não se exclui.** É o documento que protege as duas partes sobre o
  que foi declarado. Para corrigir, volta para "em preenchimento".
- **Imprimir é auditado** (`Audit::IMPRIMIU`), como o prontuário ocupacional e
  pela mesma razão: é quando dado de saúde sai do sistema em papel.

### O que ainda não existe

Agravo como alternativa à CPT não está implementado — o próprio formulário diz
que a operadora se reserva o direito de não oferecê-lo (RN 162/07, art. 6º, §1º).
Se um dia a APAS passar a oferecer, entra como uma escolha por pessoa ao lado da
CPT.

---

## Saúde Ocupacional (NR-7 / PCMSO)

Prontuário de exame ocupacional dos trabalhadores. Três tabelas encaixadas:
`so_empresas` → `so_trabalhadores` → `so_prontuarios`.

O que está no desenho e vale explicar:

- **O trabalhador é o vínculo, não a pessoa.** A chave única é
  `(empresa_id, cpf)`, e não o CPF sozinho: função, setor e admissão pertencem ao
  emprego. A mesma pessoa contratada por duas empresas são dois cadastros, com
  dois históricos ocupacionais — que é como a NR-7 os trata.
- **`cpf` aceita NULL.** O admissional às vezes acontece antes de a papelada
  chegar. Em MySQL o índice único convive com vários NULL, então isso não
  enfraquece a trava de duplicidade. O CPF que vier é validado.
- **Nada apaga em cascata.** As FKs de `so_prontuarios` e `so_trabalhadores` são
  RESTRICT. Prontuário tem prazo legal de 20 anos: apagar um cadastro não pode
  levar junto documento que a lei manda guardar. O controller recusa e diz
  quantas fichas existem; a saída é desativar.
- **O IMC não é coluna.** Peso e altura são o dado; o IMC é conta, feita no
  `helpers.php` (`imc()` / `imc_faixa()`) e no Alpine (`fichaOcupacional`).
  Guardar o resultado criaria duas verdades que divergem na primeira correção de
  digitação do peso.
- **`epi` é TINYINT NULL — três estados.** NULL é "não perguntado", que não é a
  mesma coisa que 0, "não usa". Em fiscalização essa diferença importa.
- **`conclusao` nasce `pendente`.** A enfermagem faz a triagem, o médico do
  trabalho conclui depois. É o estado real de quase toda ficha recém-preenchida,
  e é o que alimenta o cartão "Aguardando médico".
- **`validade` é o que faz o módulo valer.** É dela que saem os cartões de
  vencidas e a vencer, e o filtro "só o que está vencendo".

A **impressão** (`views/imprimir.php`) é um documento HTML inteiro e
autossuficiente, renderizado com `View::render(..., false)` — sem layout, com o
CSS embutido e nenhuma requisição externa. A folha precisa sair igual num
computador qualquer do ambulatório, inclusive sem rede. O que está preenchido sai
escrito; o que está vazio vira linha para completar à mão, então a mesma tela
serve para imprimir ficha em branco.

Imprimir é auditado (`Audit::IMPRIMIU`): é o momento em que dado de saúde sai do
sistema em papel, e a LGPD pede saber quem o tirou. É a única ação de leitura que
vira log no sistema — as outras seriam ruído.

> O botão de imprimir é `<button data-imprimir>` com ouvinte delegado no
> `app.js`, e não `onclick="window.print()"`: a CSP bloqueia atributo de evento.

### Entrega de atestado médico (`so_atestados`)

O protocolo de balcão: o colaborador traz o atestado, o setor registra e devolve
a 2ª via. O que o documento prova é a entrega e o recebimento — não a validação
do afastamento, e a frase está no papel e na tela para não virar discussão.

- **O CID não é guardado.** Para comprovar a entrega ele não acrescenta nada, e
  diagnóstico é o dado mais sensível da LGPD. O que não se guarda não vaza.
- **O protocolo é `ano/sequência`**, com `UNIQUE (ano, sequencia)` além do
  `UNIQUE (protocolo)`. A sequência sai de `MAX(sequencia)+1` do ano, não de uma
  contagem: ano com exclusão no meio continua avançando em vez de repetir um
  número que já foi impresso e entregue. Duas atendentes simultâneas pegam o
  mesmo MAX — quem perde a corrida esbarra na chave única, e `salvarAtestado()`
  tenta o número seguinte (até cinco vezes).
- **A matrícula mora em `so_trabalhadores`**, como função e setor: é do vínculo.
  Digitada uma vez, sai sozinha em toda entrega.
- **O retorno é calculado, não imposto.** `inicio + dias` no Alpine
  (`entregaAtestado`) e também no controller quando o campo chega vazio; retorno
  digitado à mão não é recalculado por cima.
- **`forma_outro` só é gravado com `forma = 'outro'`**, senão a descrição
  reapareceria numa entrega presencial.
- **`recebido_por` pode ficar em branco** — o comprovante sai com a linha para
  assinar à mão, e o cartão "Sem recebimento" transforma isso numa fila de
  pendências em vez de num campo obrigatório que trava o balcão.
- **FK RESTRICT para o trabalhador**, como no resto do módulo: quem tem entrega
  registrada não se apaga, desativa-se.

As duas impressões do módulo (ficha e comprovante) trazem no cabeçalho a marca
do **Hospital Santo Expedito** à esquerda e a da **APAS** à direita, em `flex`
com altura fixa e largura automática — logo trocado por outro de proporção
diferente continua alinhado. As imagens levam `print-color-adjust: exact`, senão
o Chrome as imprime lavadas por economia de tinta. São as únicas requisições que
a folha faz, e saem do próprio servidor.

A impressão (`views/imprimir_atestado.php`) é uma folha só com três partes
separadas por linha de corte — entrega, recebimento e 2ª via do colaborador —,
autossuficiente e auditada como a da ficha. O cabeçalho (`HOSPITAL` e
`HOSPITAL_CNPJ` em `OcupacionalModel`) é a identificação da casa: é constante
porque não muda de uma entrega para outra; CNPJ em branco vira linha para
completar à mão.

---

## Vendas — propostas de plano por vendedor

A venda é a promessa; o contrato é o fato. `vd_propostas` guarda a negociação, e
quando ela fecha o contrato nasce no Cadastro: `contrato_id` aponta para
`pl_contratos` e, daí em diante, quem manda no cadastro da família é o módulo
Cadastro — **a proposta deixa de ser editável no mesmo instante**, justamente para
não haver dois lugares guardando o mesmo titular.

Quatro tabelas: `vd_empresas` (a corretora parceira) → `vd_vendedores` →
`vd_propostas` → `vd_proposta_pessoas` (as vidas da família, enquanto a venda ainda
é promessa).

O que está no desenho e vale explicar:

- **Vendedor é cadastro próprio, não usuário.** Quem vende é gente de fora, que
  normalmente não tem login. `usuario_id` existe para quem tiver: é por ele que
  `travaVendedor()` restringe a lista, o painel e o `ver` às vendas daquele
  vendedor. A trava fica no controller, não na tela — esconder o botão não protege
  nada, e um POST forjado com outro `vendedor_id` é reescrito para o do próprio.
  Admin enxerga tudo, porque é quem coordena.
- **As vidas da família vivem na proposta até ela fechar.** `vd_proposta_pessoas`
  guarda nome, parentesco, nascimento, CPF (quando já existe) e as seis condições
  de `Elegibilidade::CONDICOES`, com `ON DELETE CASCADE`: a vida só existe dentro
  da venda. Gravar essa gente em `pl_beneficiarios` encheria o Cadastro de
  dependentes de contratos que talvez nunca existam. No fechamento cada linha é
  copiada para o contrato como dependente, no **plano do titular** — a regra da
  casa — com `plano_sugerido` gravando o que a elegibilidade indicaria pela idade,
  que é o que alimenta a tela de alinhamento do Cadastro. `ajustarVidas()` mantém
  `vidas` em pelo menos `1 + pessoas cadastradas`, para o número prometido nunca
  ficar menor que a família já listada.
- **O percentual da comissão é copiado para a proposta no fechamento.** Fica no
  vendedor porque é o combinado com ele; congela na proposta porque renegociar não
  pode mexer no que já foi vendido. `comissaoParaTodos()` aplica o mesmo percentual
  a todo mundo de uma vez (opcionalmente também aos inativos) e, pelo mesmo motivo,
  **não toca nas propostas fechadas**.
- **Vendedor não mexe em vendedor.** `exigirGestor()` barra o cadastro de
  vendedores, o de empresas e a comissão em massa para quem está logado como
  vendedor — senão bastaria um POST para alguém dobrar a própria comissão.
- **O valor da comissão não é guardado.** É o percentual congelado sobre a
  mensalidade de hoje do contrato (`mensalidadeDoContrato()`), calculada contrato a
  contrato no painel. A família cresce e a faixa etária muda no aniversário:
  gravar o valor do dia do fechamento criaria uma segunda verdade que envelhece.
- **`fechar()` roda em transação.** Contrato, titular e mudança de status são uma
  coisa só. Contrato sem titular, ou proposta apontando para um contrato que não
  nasceu, seriam dois jeitos de ficar pela metade. O plano do titular sai do motor
  de elegibilidade do Cadastro, e o número da proposta vira o `numero_proposta` do
  contrato — se aquele número já existir lá, o contrato nasce sem número em vez de
  a venda não fechar.
- **Numeração por ano, como nos atestados.** `MAX(sequencia)+1` do ano com
  `UNIQUE (ano, sequencia)`: proposta excluída não devolve o número, que o vendedor
  já pode ter falado ao telefone.
- **Fechada não se edita nem se exclui.** Ela é a origem do contrato e o histórico
  da comissão; o cadastro da família se corrige no Cadastro, e venda que caiu se
  encerra cancelando o contrato. Perdida guarda o motivo — é o que ensina a corrigir
  o discurso de venda.
- **FK RESTRICT no vendedor, SET NULL no contrato.** Vendedor com proposta não se
  apaga (desative-o); contrato excluído no Cadastro deixa a venda viva, só sem o
  vínculo.

---

## Auditoria Hospitalar — contas, glosas e recursos

Traz para dentro do APASBS o que vivia no **AuditorHosp**: as contas de
internação e de PA/Ambulatório, com os sete valores da auditoria, os quatro
percentuais, os motivos de cada glosa e o consolidado do mês.

### O banco é o do AuditorHosp — e continua sendo

O módulo **não tem tabelas próprias**. Ele abre uma segunda conexão
(`getPDOAuditoria()`, em `config/database.php`) e trabalha nas tabelas que já
existem: `internacoes`, `pa_ambulatorio`, `internacao_motivo_glosa`,
`pa_ambulatorio_motivo_glosa`, `convenios` e `motivos_glosa`.

- **Por que não importar.** O AuditorHosp continua no ar durante a migração. Dois
  bancos guardando a mesma conta divergem na primeira correção — e aí ninguém
  sabe qual dos dois está certo.
- **Configuração:** `aud_db_*` em `config/env.php`, e o `GRANT` em
  `sql/aud_acesso.sql`. Em branco, o módulo mostra uma tela explicando o que
  falta; o resto do sistema não sente nada. A conexão só é aberta quando alguém
  entra no módulo.
- **Nome da tabela nunca vem da URL.** O tipo de conta viaja como `internacao`
  ou `pa` e é traduzido por `AuditoriaHospModel::tipo()`, que estoura em
  qualquer outro valor. É a mesma regra do `tabelaApoio()` nas Autorizações.

### O que o módulo acrescenta sem mexer no banco

Login único por CPF, permissão por módulo, token CSRF em todo formulário e a
trilha de auditoria do APASBS dizendo quem criou, editou e excluiu cada conta —
nada disso existia no sistema de origem.

### Decisões

- **O acrescentado não se digita.** Sai de `final + retirado − inicial`
  (`valorAcrescentado()`), no servidor, ao gravar. A tela mostra o resultado
  enquanto se digita (o componente Alpine `contaAuditoria`), mas quem decide é
  o PHP: tela é conferência, não fonte.

  É a mesma identidade de sempre — `final = inicial − retirado + acrescentado` —
  resolvida para o outro lado. Quem digita passou a ser o **valor final**,
  porque é ele que chega da auditoria; o acrescentado virou a conta que fecha a
  diferença. Duas consequências que isso traz:

  - **Acrescentado negativo deixou de ser suspeito e passou a ser resultado.**
    Quando a conta final ficou abaixo de `inicial − retirado`, houve mais
    retirada do que o campo retirado conta. Por isso ele saiu da lista de
    campos que a validação recusa quando negativos — recusá-lo seria recusar
    uma combinação legítima dos três digitados.
  - **Final zerado numa conta que chegou com valor** faz o acrescentado fechar
    em menos a conta inteira. Não trava, porque conta esperando auditoria é
    situação normal, mas avisa com o número que ficou escrito: esse valor vai
    para o consolidado.
- **Cada percentual sobre a sua base** — retirado e acrescentado sobre o
  inicial, glosado sobre o final, aceito sobre o glosado. Base zero devolve
  `null`, não zero: "não dá para dizer" é diferente de "é zero".
- **Os totais acompanham o filtro.** O painel soma o recorte que está na tela,
  não o banco inteiro — total que ignora o filtro responde a uma pergunta que
  ninguém fez.
- **Glosa sem motivo avisa, não trava.** Quando o valor glosado da conta não bate
  com a soma dos motivos, a gravação acontece e o aviso aparece: o motivo
  costuma chegar depois da glosa.
- **Conta não auditada mostra travessão, não zero.** O AuditorHosp deixa
  `valor_total` em zero até a auditoria acontecer; "0,00" ao lado de um inicial
  cheio parece defeito.
- **Valor inicial obrigatório é propriedade da conta, não do formulário.**
  `exige_inicial` em `AuditoriaHospModel::TIPOS` diz se o tipo exige (hoje os
  dois exigem), e `SETORES_SEM_INICIAL` tira dessa exigência os setores de não
  conformidade — **PA/NC e AMB/NC**, onde a conta chega zerada e ganha valor na
  auditoria. Quem junta as duas pontas é `exigeInicial($tipo, $setor)`, e é
  dela que saem a validação do servidor, o asterisco do formulário e o aviso ao
  vivo: três lugares, uma resposta. Um asterisco que promete uma coisa e uma
  gravação que cobra outra é pior do que não marcar nada.
- **O asterisco acompanha o setor na hora**, pelo componente `contaAuditoria`,
  que recebe a regra pronta do PHP em vez de repeti-la em JavaScript. E é
  desenhado pelo servidor já no estado certo, sem `x-cloak`: asterisco que
  depende de script para aparecer é asterisco que some.
- **Auditada é o fim da fila, e só se chega nela com o valor final.**
  `SITUACAO_AUDITADO` tem nome próprio no Model porque uma regra depende dessa
  palavra: marcar auditada sem o final seria dizer que a auditoria terminou sem
  dizer em quanto deu — e esse número vai para o consolidado, para o percentual
  de glosa e para a reunião com o convênio. A trava é na **passagem** para
  auditada, não no estado: a conta que já veio auditada e zerada do AuditorHosp
  continua gravável, senão ninguém corrigiria nem o nome do paciente nela. O
  mesmo recorte vale no aviso ao vivo da tela (`auditadaSemFinal`).
- **A trava é para o zero novo, não para o zero que já estava lá.** Ela pega a
  conta nova e quem zera uma conta que tinha valor; a que **já chegou zerada**
  do AuditorHosp continua gravável, senão ninguém conseguiria trocar a situação
  dela sem digitar um valor que talvez não tenha em mãos — nesse caso grava e
  avisa que o valor continua faltando. É `$antes` em `validar()` que separa um
  zero do outro, e é o que torna a exigência segura no PA, onde os setores de
  não conformidade deixaram muita conta zerada na base importada.
- **A lista mostra os sete valores, na ordem da conta** — inicial, retirado,
  acrescido, final, glosado, aceito. É a ordem da fórmula
  (`inicial − retirado + acrescentado = final`), que é como quem audita lê a
  linha; retirado e acrescido fora dela obrigariam a somar de cabeça para
  conferir o final. O retirado leva o percentual sobre o inicial embaixo, e o
  acrescido mostra o sinal quando é negativo — os dois do mesmo jeito que no
  consolidado, para a conta e o grupo de contas se lerem igual.
- **Situação é texto livre no banco de origem.** O formulário oferece as três do
  AuditorHosp, e uma conta antiga com outra coisa gravada continua mostrando o
  que tem — trocar em silêncio seria reescrever o passado.
- **Excluir a conta leva os motivos junto**, pela mão do Model: as tabelas de
  motivo do AuditorHosp não têm `ON DELETE CASCADE`.

### A planilha da competência

O botão **Excel** na lista baixa um `.xlsx` de verdade com as contas do recorte
que está na tela — o mesmo `recorteLista()` que a listagem usa, para o arquivo
nunca trazer linhas diferentes das que estavam à vista.

- **Sem paginação.** `todas()` é o `listar()` sem `LIMIT`: quem pede a planilha
  do mês quer o mês inteiro, e uma planilha que para na página 1 é pior do que
  não existir.
- **Vinte e quatro colunas**, inclusive o que a tela não mostra por falta de
  largura: recebimento, entrada, saída, faturado, nota fiscal, observações e os
  motivos de glosa por extenso.
- **Os motivos vêm em uma consulta só** (`motivosDeVarias()`), não uma por
  linha — uma competência de PA tem milhares de contas. E o texto é montado em
  PHP, não com `GROUP_CONCAT`: o `FORMAT()` do MySQL escreve o número no padrão
  dele, e dinheiro com ponto decimal numa planilha brasileira é o tipo de
  detalhe que ninguém percebe até alguém somar errado.
- **Percentual vai como fração.** No `.xlsx` 10% é `0,1`, porque quem
  multiplica por cem é o formato da célula; no CSV de emergência, não — lá o
  número tem de sair já em por cento, porque não há formato nenhum.

### O consolidado da competência

A action `consolidado` fecha o mês por convênio (e por setor, no PA), e a action
`imprimir` leva a mesma tabela para a folha da reunião.

- **Somado na hora, não consolidado em tabela.** O AuditorHosp guarda
  `relatorio_mensal_consolidado` e `relatorio_mensal_pa_consolidado`, atualizadas
  por um botão — e elas já divergem das contas em mais de uma competência (na
  base real, convênio 3, AMB, 04/2026: R$ 5.831,67 guardados × R$ 9.658,81
  somados). Aqui a soma acontece quando a tela abre: não existe o estado
  "esqueci de consolidar". As tabelas antigas continuam intocadas.
- **Uma constante de somas, os mesmos nomes de coluna.** `AuditoriaHospModel::SOMAS`
  devolve os sete `SUM()` com os nomes que uma conta tem, e por isso
  `percentuais()` serve para um grupo de contas sem uma segunda conta de
  percentual por aí. Percentual do total, nunca média de percentuais.
- **O rodapé soma as linhas da tela** (`AuditoriaHospModel::somar()`), não o banco
  de novo: a tabela fecha com ela mesma.
- **`sem_final` é contado e dito.** Conta que chegou com valor e está com final
  zerado entra no inicial e não entra no final, inflando o percentual de glosa —
  a tela avisa quantas são em vez de deixar o número mentir sozinho.
- **Acréscimo negativo existe na base** (1.221 linhas no PA): é como o sistema
  antigo registra uma segunda retirada. A coluna mostra o sinal em vez de
  esconder a linha no travessão e deixar o total negativo sem explicação.
- **A evolução ignora a competência do filtro** e só ela: série de um mês não é
  evolução, mas os outros recortes continuam valendo, senão o gráfico fala de um
  universo diferente do da tabela ao lado.
- **Gráfico é barra de CSS**, não biblioteca: a CSP é `script-src 'self'`, e barra
  desenhada em HTML imprime junto com a folha. As duas séries dividem a mesma
  régua, senão comparar mês com mês não significa nada.
- **A tela e a folha compartilham a tabela** (`views/_consolidado_apoio.php`): um
  consolidado impresso que não bate com o da tela é pior do que não imprimir.

### O que ficou para depois

Documentos por competência, contratos, cadastro de motivos de glosa e o trio
`faturas`/`glosas`/`recursos` — este último só depois de decidir qual é a fonte
da verdade, já que mede glosa e recurso por um caminho paralelo ao das contas.

---

## Comercial — tabelas de valores dos convênios

Quanto o convênio paga por cada coisa. Duas tabelas: `com_tabelas` é a capa — um
edital de um convênio com a data em que passou a valer — e `com_itens` são as
linhas, uma por item cobrável.

A primeira carga é o **Edital de Credenciamento 011/2022 da CAPEP-SAÚDE, 29º
retificado**, vigente desde 01/06/2026: 4.020 itens lidos do PDF oficial
(páginas 22 a 31 e 42 a 266).

O que está no desenho e vale explicar:

- **O edital inteiro é UMA tabela com vigência.** O reajuste do ano que vem entra
  como outra, e a antiga continua ali — é o que permite explicar, meses depois,
  quanto valia um procedimento no dia do atendimento. `tabelaVigente()` pega a
  vigência mais recente que já começou, então reajuste lançado com antecedência
  fica guardado até a data chegar.
- **Uma lista só, filtrada por `tipo`.** Procedimento, honorário, pacote, OPME,
  material, dieta, diária, taxa e porte moram na mesma tela. Quem pergunta o
  preço sabe o código ou o nome — não sabe em qual anexo do edital a linha está.
- **`variante` em vez de uma coluna por porte.** Diária de enfermaria vale um
  valor no PORTE I e outro no PORTE II; porte de honorário tem um valor para
  honorários e outro para SADT. Cada combinação é uma linha, e um edital que
  amanhã criar o PORTE IV não pede mudança de tabela.
- **`extras` é JSON em texto.** Porte, UCO, valor HM, fabricante, registro Anvisa,
  página do edital: campos que só existem em alguns tipos. Uma coluna para cada um
  deixaria a tabela com metade das colunas vazias em qualquer consulta.
- **`origem` separa o que é carga do que é digitado.** A recarga do edital apaga e
  recria só as linhas `edital`; o que foi incluído pela tela (`manual`) sobrevive.
  É o que permite corrigir uma linha torta sem perder a correção na carga seguinte.
- **FK com CASCADE, ao contrário do resto do sistema.** A linha só existe dentro da
  tabela de valores; deixar 4 mil órfãs seria pior. Em compensação, excluir uma
  tabela com itens exige desativá-la antes — o controller recusa e diz quantos
  itens iriam junto.

### A carga do edital

`sql/com_capep_2026.sql` é **gerado** por `tools/gerar_comercial.py` a partir do
PDF do edital:

```bash
pdftotext -layout edital.pdf edital.txt
python3 tools/gerar_comercial.py edital.txt > sql/com_capep_2026.sql
```

O parser é escrito **por seção**, e não genérico: cada trecho do edital tem um
desenho de coluna diferente (diárias em três colunas de porte, Anexo V com porte e
UCO, materiais com registro Anvisa quebrado de linha, pacotes de hemodinâmica em
texto corrido). Parser genérico em PDF de edital erra calado — que é o pior jeito
de errar numa tabela de preço. Cada item guarda em `extras.pagina` a página de onde
foi lido, para conferir no PDF quando a conta não fechar.

> Rodar a carga de novo **reescreve** os itens de origem `edital` daquela tabela.
> Itens incluídos pela tela não são tocados.

---

## Códigos de exame por convênio

O mesmo exame tem código diferente em cada convênio, e às vezes mais de um código
no mesmo convênio. Esse de/para costumava viver numa folha na parede.

### Duas tabelas

`amb_exame_codigos` guarda o par convênio + descrição; `amb_exame_codigos_tuss`
guarda os 1..N códigos daquele par. Guardar "1,2,3" numa coluna de texto pareceria
mais simples e impediria procurar por um código.

Os convênios são **os mesmos** das Autorizações (`amb_convenios`) — dois cadastros
do mesmo convênio divergiriam no primeiro que alguém renomeasse.

### Decisões

- **`UNIQUE (convenio_id, descricao)`.** Tentar cadastrar o que já existe é
  recusado com um link para abrir o registro atual. A mesma informação em duas
  linhas é a origem de metade das divergências de cadastro.
- **`codigo` é VARCHAR, não bigint.** Nem todo código de convênio é TUSS, e
  alguns têm zero à esquerda que um número comeria.
- **`termo` é copiado, não referenciado.** A ANS revisa a Tabela 22; o que foi
  combinado com o convênio precisa continuar dizendo o que dizia então.
- **A descrição é texto livre**, não a lista de procedimentos das Autorizações:
  aqui vale o nome da operadora ("ECO TRANSTORÁCICO C/ DOPPLER"), que quase nunca
  é o nome que a casa usa.
- **A busca por código entra por `EXISTS`, não por JOIN** — com JOIN, um exame com
  três códigos apareceria três vezes na lista.
- **Os códigos são apagados e regravados** a cada gravação, dentro de uma
  transação. A lista é curta e a ordem importa; um diff item a item seria código
  complicado para um problema que não existe nesta escala.

### O autocomplete e o overflow

As linhas de código usam `<div>` em vez de `<table class="tabela">`: o
`overflow-x-auto` do contêiner da tabela **recorta** a lista de sugestões
posicionada por cima. Isso apareceu no primeiro teste com o dropdown cortado ao
meio. Em tela estreita os campos ainda empilham, que é melhor que rolar.

`ondeMaisAparece()` procura o mesmo código no cadastro inteiro e alimenta a coluna
*Também usado em* — a pergunta que o papel na parede não respondia.

---

## Autorização de exames

Substitui uma planilha em que **cada dia começava um bloco novo**, com o
cabeçalho repetido no meio da folha. Ali não dava para perguntar "quantas estão
esperando?" sem ler tudo com o dedo; aqui a data é uma coluna e o recorte é do
filtro.

### As quatro tabelas

| Tabela | Guarda |
| --- | --- |
| `amb_autorizacoes` | Uma linha por pedido, com CPF, carteirinha, as duas datas e a situação. |
| `amb_convenios` | Nome e o telefone da autorização. |
| `amb_procedimentos` | Nome e o código TUSS, quando o convênio exige. |
| `amb_autorizacao_anexos` | Os documentos: a guia autorizada e o pedido médico. |

`status` percorre `aguardando` → `autorizado` → `agendado`, com `negado` como
saída e `pendente` como desvio enquanto falta informação. O negado fica
registrado: na planilha ele sumia da folha, e sem ele ninguém sabe por que a
paciente não fez o exame.

**A chave `agendado` aparece na tela como "Para Autorizar".** Só o rótulo mudou: o
valor no banco, os filtros e os links continuam os mesmos, porque reescrever o
ENUM trocaria o passado de lugar e quebraria endereço que alguém tenha guardado.
Com a data preenchida, o ciclo está fechado. Por isso "Para Autorizar" **sem
data** continua contando como trabalho em aberto e entra na faixa âmbar das
paradas, ao lado de `autorizado`.

A condição da faixa âmbar vive em `AutorizacaoModel::ONDE_PARADA`, usada pela
contagem **e** pelo filtro `parado=1` que o aviso abre — se fossem duas, o número
da faixa deixaria de bater com a lista que ela mostra no primeiro ajuste.

### Informações pendentes: a situação que um campo escreve

A quinta situação (`pendente`) não é escolhida como as outras: ela é consequência
do campo **Informações pendentes** (`info_pendente`), onde se escreve o que falta
para o pedido andar.

A regra inteira cabe em `AutorizacaoModel::situacaoComPendencia()`, e está ali —
e não espalhada pelo Controller — porque o formulário, o atalho da lista e
qualquer tela futura precisam chegar ao mesmo resultado:

- com texto escrito e o convênio ainda sem responder → a situação é `pendente`,
  qualquer que tenha sido a escolhida no seletor;
- sem texto, vale a situação escolhida;
- `pendente` **sem** texto não é resolvido ali de propósito: é erro de
  preenchimento, e quem diz isso é a validação. Devolver "aguardando" em silêncio
  escolheria a situação no lugar da pessoa — e é justamente isto que o pedido
  original queria: ao apagar o que estava pendente, é preciso dizer em que pé o
  pedido ficou.

Duas decisões que o campo trouxe:

- **Campo próprio, separado de `observacao`.** A observação é recado ("a paciente
  prefere pela manhã") e nunca mexeu em situação nenhuma. Reaproveitá-la faria
  todo recado antigo virar pendência na primeira vez que alguém salvasse o
  registro — reescrevendo o passado por causa de um campo que mudou de
  significado. A migração não converte nada.
- **A pendência só manda antes da resposta** (`ANTES_DA_RESPOSTA`: `aguardando`,
  `agendado`, `pendente`). Num pedido já autorizado ou negado, o texto continua
  visível na linha, em âmbar, mas não troca o selo: o convênio já respondeu, e
  essa é a informação mais importante da linha — apagá-la da tela por causa de um
  bilhete seria perder o que mais importa.

O texto sobrevive à mudança de situação: uma pendência resolvida se apaga à mão,
porque quem apaga é quem sabe que ela foi resolvida. `pendente` entra em "só o
que está em aberto" e fica fora da faixa âmbar das paradas — ela já tem o próprio
selo dizendo o que falta.

### Decisões que o dado da planilha impôs

- **`data_pedido` é NULL.** A planilha trazia a palavra "SEM DATA" escrita numa
  coluna de data. Guardar isso como texto seria mentir sobre o tipo; vazio é
  vazio, e a tela escreve "sem data" em cinza.
- **Pedido posterior à solicitação avisa, não recusa.** A primeira versão
  recusava — e barrou uma linha real da planilha (solicitação 09/jul, pedido
  28/ago). Costuma ser ano digitado errado, mas acontece de o médico reemitir o
  pedido depois. Então grava e avisa.
- **CPF e carteirinha são opcionais, mas o CPF é validado quando vem.** Na hora
  da ligação a paciente nem sempre tem os documentos em mãos; travar o registro
  faria a atendente anotar no papel para digitar depois — o que o sistema veio
  evitar. Já um CPF inválido gravado é pior que campo vazio, porque alguém vai
  usá-lo para achar a paciente no convênio. Ambos vão a `NULL` quando vazios, não
  a string vazia: a ausência do dado é o que de fato acontece.
- **CPF guardado só com dígitos** (como `usuarios.cpf`), carteirinha como veio —
  cada convênio tem seu formato, e normalizar seria inventar uma regra que eles
  não seguem. A busca por CPF manda os dois termos, com e sem pontuação.
- **Convênio e procedimento são cadastro, não texto.** Digitados à mão,
  "BLUEMED", "Blue Med" e "Bluemed" viram três convênios na hora de contar.
- **O que já foi usado não pode ser excluído**, só desativado — a chave
  estrangeira recusa e a tela explica por quê. Excluir o convênio de uma
  autorização de 2025 apagaria o passado dela.

### Os documentos: guia autorizada e pedido médico

A guia que o convênio devolve e o pedido do médico ficam anexados à própria
autorização (`sql/amb_autorizacoes_anexos.sql`), em `uploads/autorizacoes/<id>/`.

- **É uma tabela, não duas colunas.** "A imagem da guia" parece um campo, mas o
  pedido tem frente e verso, a guia volta em duas páginas e às vezes chega a foto
  tremida de manhã e o PDF legível à tarde. Com duas colunas, a segunda foto
  apagaria a primeira. Cada arquivo é uma linha, marcada no ENUM `tipo` com o
  papel que tem — texto livre acabaria com "guia", "Guia" e "guia autorizada"
  significando a mesma coisa.
- **Na inclusão o arquivo sobe junto; depois, cada tipo tem seu envio.** No
  formulário de inclusão os campos ficam dentro do próprio `<form>` (daí o
  `enctype="multipart/form-data"`): quem está com o pedido na mão grava tudo de
  uma vez. O preço é que um erro de validação devolve o formulário sem os
  arquivos — o navegador não repõe um campo de arquivo —, então o controller
  avisa quando isso acontece, em vez de deixar a pessoa descobrir depois. Numa
  autorização que já existe vale o contrário: cada tipo tem seu próprio POST,
  porque anexar a guia que chegou hoje não deve exigir regravar o cadastro nem
  perder o que estiver digitado se um arquivo for recusado.
- **O tipo do arquivo é lido do conteúdo, nunca da extensão**, e o nome em disco
  é sorteado com a extensão derivada desse tipo — um "guia.php" jamais vira um
  `.php` dentro de `uploads/`. Aceita JPG, PNG, WEBP, GIF, PDF e **HTML**, até
  10 MB, oito por tipo em cada autorização.
- **HTML entra porque é assim que o convênio entrega.** Vários portais salvam a
  guia como página, e `.htm` e `.html` chegam os dois como `text/html` — a
  extensão continua sem opinar: um `<?php` renomeado para `.html` é lido como
  `text/x-php` e recusado, e um SVG com `<script>` dentro nunca esteve na lista.
- **Página de fora não roda nada aqui dentro.** O `anexo()` serve todo arquivo
  com `Content-Security-Policy: default-src 'none'; sandbox` — sandbox sem
  nenhuma permissão derruba script, formulário e acesso à sessão, e o documento
  abre numa origem própria, onde `document.cookie` lança `SecurityError`. Para o
  HTML há duas frestas, e só estas: `style-src 'unsafe-inline'`, senão a guia
  chega desmontada, e `img-src data:`, que é como a página salva carrega
  logotipo e carimbo. Imagem de endereço externo continua barrada de propósito:
  num documento de paciente ela avisaria o outro lado toda vez que alguém
  abrisse o arquivo.
- **Nada em `uploads/` é servido direto pelo servidor web** (`uploads/.htaccess`).
  Todo arquivo passa pelo PHP, que confere o login e a permissão e registra na
  auditoria quem abriu. Endereço aleatório é segredo, e segredo vaza — no
  histórico do navegador, no print, no grupo de WhatsApp.
- **Autorizado sem a guia avisa, não trava.** O convênio responde por telefone e
  a imagem chega depois; travar faria a atendente deixar de registrar. A cobrança
  fica visível: um "sem a guia" em âmbar na linha da lista, que leva direto ao
  campo.
- **Excluir a autorização apaga os arquivos.** O banco apaga as linhas sozinho
  (CASCADE); o disco, não — e documento de paciente esquecido no servidor é dado
  guardado sem registro de que existe.
- **A folha de impressão tem rosto.** `imprimir` monta uma folha de rosto com o
  cabeçalho do hospital e os dados do pedido e depois uma página por imagem,
  cada uma repetindo paciente, convênio e senha: foto de guia solta na impressora
  não diz de quem é. `tipo=guia|pedido` recorta a impressão. PDF e página do
  convênio não são embutidos — saem no tamanho original pelo programa de cada um,
  e a folha de rosto lista quais são, com o formato ao lado.
- **Miniatura não vira linha de auditoria.** A mesma URL serve o documento e as
  miniaturas da tela, então o registro de "baixou" só acontece quando
  `Sec-Fetch-Dest` não é `image` — senão abrir o formulário com três anexos
  escreveria três downloads que ninguém fez, justamente na trilha que existe para
  dizer quem abriu o documento de uma paciente.

### Detalhes de implementação

`AutorizacaoModel::onde()` monta o WHERE dos filtros uma vez e é usada pela
listagem **e** pela contagem, para o total do rodapé nunca discordar da tela.

Os parâmetros são todos posicionais: o PDO recusa misturar `?` com `:nome` na
mesma instrução, e os filtros já entram como `?` — o primeiro teste do módulo
estourou exatamente nisso quando um filtro foi aplicado.

`paradas()` conta o que está autorizado há mais de sete dias sem data agendada.
É o número que a planilha não sabia dar, e o que evita autorização vencendo na
gaveta.

Os dois cadastros de apoio dividem uma view (`views/apoio.php`) e um par de
actions, porque são a mesma tela com outro rótulo. O nome da tabela nunca vem da
URL: `tabelaApoio()` traduz o apelido e estoura em qualquer outro valor.

---

## Senha do próprio usuário

Cada pessoa troca a própria senha em **Minha conta** (`usuarios&action=minhaConta`),
no rodapé do menu. Pede a senha atual, a nova e a confirmação.

### O que está onde, e por quê

- **A conferência da senha atual vive no Model** (`UsuarioModel::trocarSenha`),
  não no controller: o hash é lido na hora e a troca só acontece se ele bater.
  Um caminho novo que chame o método não tem como esquecer a conferência.
- **As regras de força estão em um lugar só** — `UsuariosController::erroSenha()`,
  usada pelo cadastro do administrador e pela troca. Duas cópias divergiriam na
  primeira vez que a regra mudasse.
- **A senha atual é conferida por último.** Quem estiver tentando adivinhá-la não
  ganha, de brinde, a informação de que o resto do formulário passou.
- **Depois de trocar, `session_regenerate_id(true)` e token CSRF novo.** A sessão
  continua valendo (ninguém é deslogado), mas um cookie copiado antes não herda
  a senha nova.

### Senha provisória

`usuarios.senha_provisoria` liga sempre que o administrador cria um usuário ou
digita uma senha no cadastro de alguém — ou seja, sempre que quem escolheu a
senha não foi o dono da conta.

Enquanto estiver ligada, `Auth::requireLogin()` devolve qualquer tela para
**Minha conta**. A guarda fica ali, e não espalhada pelos controllers, porque
uma checagem por controller vira um caminho esquecido na primeira tela nova.
As únicas actions liberadas são `minhaConta`, `trocarSenha` e `logout`.

A sidebar também esconde os módulos nesse estado: menu que só devolve para a
mesma tela mente sobre onde dá para ir.

`usuarios.senha_alterada_em` guarda quando a senha mudou pela última vez — é o
que a lista de usuários usa para marcar quem ainda está com a senha de fábrica.

> Quem já usava o sistema antes desta mudança **não** foi marcado como
> provisório. Forçar a troca de todo mundo de uma vez geraria fila no suporte
> sem ganho real; a regra vale para as senhas criadas daqui para a frente.

### O que NÃO existe

Não há "esqueci minha senha" por e-mail — o cadastro não guarda e-mail de
ninguém e o sistema não envia e-mail. Quem esquece procura o administrador, que
redefine a senha; ela entra como provisória e a pessoa escolhe a sua no acesso
seguinte.

---

## Convenções de código

- `declare(strict_types=1)` em todos os arquivos PHP
- Nomes de classes, variáveis, mensagens e comentários em português
- Saída sempre escapada com o helper `e()`
- Feedback ao usuário via `Flash::sucesso()` / `erro()` / `aviso()`
- Fim de linha **LF** em todo o repositório (ver `.gitattributes`)
