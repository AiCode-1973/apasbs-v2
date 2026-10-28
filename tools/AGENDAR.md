# Como deixar a coleta do ReciclaLead rodando sozinha

O coletor não faz nada por conta própria: alguém precisa mandar rodar toda
semana. Abaixo estão os dois jeitos, conforme onde o APASBS está instalado.

Antes de agendar, faça a parte de uma vez só:

1. **Crie o usuário do robô no ReciclaLead.** Um login só dele, com
   permissão de ver os relatórios da central e **sem verificação em duas
   etapas** — o robô não tem celular para digitar código.
2. **Preencha a configuração.** Copie `config/reciclalead.example.php` para
   `config/reciclalead.php` e escreva ali a URL, o e-mail e a senha do robô.
   Esse arquivo fica só no servidor: ele não entra no Git nem em pacote de
   entrega, igual ao `config/env.php`.
3. **Teste na mão**, uma vez, antes de agendar:

   ```
   php tools/coletar_reciclalead.php --verboso
   ```

   Terminou sem erro? Abra **Central de Agendamento › Coletas**: a
   competência do mês tem que estar lá, esperando conferência.

---

## Windows (XAMPP) — Agendador de Tarefas

1. Abra `tools\coletar_reciclalead.bat` no Bloco de Notas e confira as duas
   primeiras linhas: onde está o `php.exe` e onde está o sistema.
2. Dê dois cliques no `.bat`. Se aparecer `[OK] Coleta concluída`, seguimos.
3. Menu Iniciar → **Agendador de Tarefas** → *Criar Tarefa…* (não é "criar
   tarefa básica", porque precisamos das opções de baixo).

   **Geral**
   - Nome: `APASBS — coleta ReciclaLead`
   - Marque **Executar estando o usuário conectado ou não**
   - Marque **Executar com privilégios mais altos**

   **Disparadores** → *Novo…*
   - Semanalmente, **segunda-feira**, às **06:00**
   - Marque *Repetir a tarefa a cada* — não é necessário; deixe desmarcado

   **Ações** → *Novo…*
   - Ação: Iniciar um programa
   - Programa: `C:\xampp1\htdocs\apasbs-v2\tools\coletar_reciclalead.bat`
   - Iniciar em: `C:\xampp1\htdocs\apasbs-v2`

   **Configurações**
   - Marque *Executar a tarefa assim que possível após uma inicialização
     agendada ter sido perdida* — se o computador estiver desligado na
     segunda de manhã, ele roda quando ligar.
   - *Interromper a tarefa se ela for executada por mais de*: 1 hora

4. Clique com o botão direito na tarefa → **Executar**. Depois confira a
   coluna *Resultado da última execução*: tem que ser `0x0`.

O que aconteceu em cada execução fica em
`C:\xampp1\htdocs\apasbs-v2\logs\reciclalead-AAAA-MM.log`.

## Linux (servidor da hospedagem) — cron

Toda segunda às 6h:

```cron
0 6 * * 1 cd /caminho/do/apasbs-v2 && /usr/bin/php tools/coletar_reciclalead.php --verboso >> logs/reciclalead-$(date +\%Y-\%m).log 2>&1
```

---

## E depois?

O robô **não** grava fechamento. Ele traz os números e para ali. Quem
transforma em fechamento é uma pessoa, em **Central de Agendamento ›
Coletas**, olhando o que veio e dizendo em qual motivo do APASBS cada
rótulo do ReciclaLead entra.

É de propósito: fornecedor renomeia motivo sem avisar, e número errado no
fechamento é pior que número nenhum.

## Quando der errado

A coleta que falhou aparece na fila em vermelho, com o motivo escrito. Os
casos comuns:

| O que aparece | O que costuma ser |
|---|---|
| `login recusado` | senha do robô trocada, ou o usuário foi desativado no ReciclaLead |
| `não consegui falar com o ReciclaLead` | internet do servidor, ou o site fora do ar |
| `resposta inesperada` | o fornecedor mexeu na API — aí é caso de olhar o `resposta_bruta` da coleta |
| `soma dos motivos não bate` | a coleta veio, mas os números do próprio ReciclaLead estão inconsistentes; confira lá antes de confirmar |

Descartar uma coleta com erro só tira ela da fila — não mexe em fechamento
nenhum.
