/**
 * Componentes Alpine do APASBS.
 * Registrados em alpine:init — este arquivo é carregado ANTES do alpine.min.js.
 */
document.addEventListener('alpine:init', function () {
    var Alpine = window.Alpine;

    // ── Casca da aplicação: gaveta no celular, tema, sidebar ─────────────
    Alpine.data('appShell', function () {
        return {
            gaveta: false,
            escuro: window.APASBS.temaEscuro(),

            abrirGaveta()  { this.gaveta = true; },
            fecharGaveta() { this.gaveta = false; },

            alternarTema() {
                window.APASBS.alternarTema();
                this.escuro = window.APASBS.temaEscuro();
            },

            alternarSidebar() {
                window.APASBS.alternarSidebar();
            }
        };
    });

    // ── Alerta que some sozinho ──────────────────────────────────────────
    Alpine.data('alerta', function (segundos) {
        return {
            visivel: true,
            init() {
                if (segundos > 0) {
                    setTimeout(() => { this.visivel = false; }, segundos * 1000);
                }
            }
        };
    });

    // ── Confirmação de exclusão ──────────────────────────────────────────
    // O formulário fica no HTML com o token CSRF; o modal só o dispara.
    Alpine.data('confirmacao', function () {
        return {
            aberto: false,
            titulo: '',
            texto: '',
            acao: 'Excluir',
            perigo: true,
            alvo: null,

            abrir(evento) {
                var form = evento.currentTarget.closest('form');
                this.alvo   = form;
                this.titulo = form.dataset.titulo || 'Confirmar exclusão';
                this.texto  = form.dataset.texto  || 'Esta ação não pode ser desfeita.';
                // Sem data-acao o diálogo continua sendo o de exclusão, que é
                // o caso mais comum. Quem confirma outra coisa diz o nome do
                // botão e, se não for destrutivo, data-tom="normal".
                this.acao   = form.dataset.acao || 'Excluir';
                this.perigo = (form.dataset.tom || 'perigo') !== 'normal';
                this.aberto = true;
                this.$nextTick(() => { if (this.$refs.confirmar) this.$refs.confirmar.focus(); });
            },

            fechar() { this.aberto = false; this.alvo = null; },

            confirmar() {
                if (this.alvo) { this.alvo.submit(); }
                this.aberto = false;
            }
        };
    });

    // ── Envio de formulário ──────────────────────────────────────────────
    // Desabilita o botão enquanto o navegador processa, evitando que um
    // duplo clique gere dois registros.
    Alpine.data('envio', function () {
        return {
            enviando: false,
            enviar() { this.enviando = true; }
        };
    });

    // ── Fechamento mensal: confere a soma dos motivos contra o total ─────
    Alpine.data('fechamentoForm', function () {
        return {
            enviando: false,
            total: 0,
            soma: 0,

            init() { this.recalcular(); },

            enviar() { this.enviando = true; },

            numero(el) {
                return parseInt(String(el.value).replace(/\D/g, ''), 10) || 0;
            },

            recalcular() {
                var campoTotal = this.$root.querySelector('#total_atendimentos');
                this.total = campoTotal ? this.numero(campoTotal) : 0;

                var s = 0;
                this.$root.querySelectorAll('input[data-motivo]').forEach(i => { s += this.numero(i); });
                this.soma = s;
            },

            get diferenca() { return this.total - this.soma; },
            get lancou()    { return this.soma > 0; },
            get confere()   { return this.lancou && this.diferenca === 0; },

            fmt(n) { return Math.abs(n).toLocaleString('pt-BR'); },

            get textoDiferenca() {
                if (!this.lancou) return 'Nenhum motivo lançado ainda.';
                if (this.diferenca === 0) return 'A soma dos motivos bate com o total informado.';
                if (this.diferenca > 0) return 'Faltam ' + this.fmt(this.diferenca) + ' atendimento(s) para chegar ao total informado.';
                return 'A soma dos motivos passa ' + this.fmt(this.diferenca) + ' do total informado.';
            }
        };
    });

    // ── Máscara de CPF ───────────────────────────────────────────────────
    Alpine.data('cpf', function () {
        return {
            formatar(evento) {
                var v = evento.target.value.replace(/\D/g, '').slice(0, 11);
                var saida = v;

                if (v.length > 9)      saida = v.replace(/(\d{3})(\d{3})(\d{3})(\d{1,2})/, '$1.$2.$3-$4');
                else if (v.length > 6) saida = v.replace(/(\d{3})(\d{3})(\d{1,3})/, '$1.$2.$3');
                else if (v.length > 3) saida = v.replace(/(\d{3})(\d{1,3})/, '$1.$2');

                evento.target.value = saida;
            }
        };
    });

    // ── Máscara de telefone ──────────────────────────────────────────────
    Alpine.data('telefone', function () {
        return {
            formatar(evento) {
                var v = evento.target.value.replace(/\D/g, '').slice(0, 11);
                var saida = v;

                if (v.length > 10)     saida = v.replace(/(\d{2})(\d{5})(\d{1,4})/, '($1) $2-$3');
                else if (v.length > 6) saida = v.replace(/(\d{2})(\d{4})(\d{1,4})/, '($1) $2-$3');
                else if (v.length > 2) saida = v.replace(/(\d{2})(\d{1,5})/, '($1) $2');
                else if (v.length > 0) saida = '(' + v;

                evento.target.value = saida;
            }
        };
    });

    // ── Declaração de saúde: o cabeçalho ─────────────────────────────────
    // Médico orientador só existe quando houve orientação; o aviso sobre
    // "concluída" só aparece quando ela é escolhida.
    Alpine.data('declaracao', function (inicial) {
        return {
            enviando: false,
            orientacao: (inicial && inicial.orientacao) || 'dispensa',
            status: (inicial && inicial.status) || 'em_preenchimento',
            enviar() { this.enviando = true; }
        };
    });

    // ── Declaração de saúde: o questionário de uma pessoa ────────────────
    // A descrição só existe embaixo de um "sim". O contador do cabeçalho
    // serve para quem preenche saber que terminou sem recontar 22 linhas.
    Alpine.data('questionario', function (config) {
        return {
            enviando: false,
            itens: config.itens || {},

            enviar() { this.enviando = true; },

            get respondidos() {
                var n = 0;
                for (var k in this.itens) {
                    var i = this.itens[k];
                    if (i && (i.resposta === 'S' || i.resposta === 'N')) { n++; }
                }
                // Os itens de valor (grau, peso, altura, idade) não passam
                // pelo Alpine: são campos de texto simples. O contador conta
                // o que ele enxerga, e a tela da declaração dá o número final.
                return n;
            },

            get quantosSim() {
                var n = 0;
                for (var k in this.itens) {
                    if (this.itens[k] && this.itens[k].resposta === 'S') { n++; }
                }
                return n;
            }
        };
    });

    // ── Beneficiário do plano: o plano que a regra indica ────────────────
    // A avaliação vem do servidor, e não de uma cópia das regras aqui: o
    // regulamento tem uma matriz de 26 parentescos com exceções, e duas
    // versões dela divergiriam na primeira correção — sendo a da tela,
    // justamente, a que o usuário acredita.
    Alpine.data('beneficiario', function (config) {
        return {
            enviando: false,
            carregando: false,
            url: config.url,
            contratoTipo: config.contrato_tipo,
            status: 'ativo',
            planoEscolhido: '0',
            r: config.inicial || { elegivel: false, pendencias: [] },
            idade: null,
            faixa: '',
            temporizador: null,
            controlador: null,

            p: {
                tipo: config.tipo || 'dependente',
                categoria: '',
                parentesco: '',
                nascimento: '',
                guarda_judicial: false,
                tutela_curatela: false,
                dependencia_economica: false,
                uniao_estavel: false,
                emancipado: false,
                vinculo_comprovado: false
            },

            init() {
                // Espelha o que o PHP já renderizou nos campos: recarregar a
                // página com erro de validação não pode zerar a caixa.
                var raiz = this.$root;
                var pega = function (sel) { var el = raiz.querySelector(sel); return el ? el.value : ''; };

                this.p.categoria  = pega('#categoria');
                this.p.parentesco = pega('#parentesco');
                this.p.nascimento = pega('#nascimento');
                this.status       = pega('#status') || 'ativo';
                this.planoEscolhido = pega('#plano_id') || '0';

                Object.keys(this.p).forEach(function (chave) {
                    var cx = raiz.querySelector('input[name="' + chave + '"]');
                    if (cx && cx.type === 'checkbox') { this.p[chave] = cx.checked; }
                }, this);

                this.avaliar();
            },

            enviar() { this.enviando = true; },

            avaliar() {
                clearTimeout(this.temporizador);
                this.temporizador = setTimeout(() => { this.consultar(); }, 200);
            },

            consultar() {
                if (this.controlador) { this.controlador.abort(); }
                this.controlador = new AbortController();
                this.carregando = true;

                var params = new URLSearchParams({ contrato_tipo: this.contratoTipo });
                Object.keys(this.p).forEach(function (chave) {
                    var v = this.p[chave];
                    params.append(chave, v === true ? '1' : (v === false ? '' : v));
                }, this);

                fetch(this.url + '&' + params.toString(), {
                    signal: this.controlador.signal,
                    headers: { 'Accept': 'application/json' },
                    credentials: 'same-origin'
                })
                    .then(res => res.ok ? res.json() : null)
                    .then(dados => {
                        if (!dados) { return; }
                        this.r = dados;
                        this.idade = (dados.idade === null || dados.idade === undefined) ? null : dados.idade;
                        this.faixa = dados.faixa || '';
                        this.carregando = false;
                    })
                    .catch(() => { this.carregando = false; });
            }
        };
    });

    // ── Ficha de saúde ocupacional ───────────────────────────────────────
    // O IMC é conta de peso e altura, e por isso não é campo digitado nem
    // coluna no banco: aparece na hora, enquanto se mede. A restrição só
    // existe quando o médico conclui "apto com restrição".
    // ── Conta da auditoria hospitalar ────────────────────────────────────
    // O valor final e os quatro percentuais aparecem enquanto se digita. São
    // os mesmos cálculos que o servidor refaz ao gravar — a tela aqui existe
    // para quem audita ver o efeito de cada número antes de salvar, não para
    // ser a fonte da verdade.
    Alpine.data('contaAuditoria', function (inicial, regraInicial) {
        // Quem exige o valor inicial é o tipo da conta, e no PA o setor manda
        // por cima: PA/NC e AMB/NC são isentos. A regra vem pronta do PHP
        // (AuditoriaHospModel::exigeInicial) para a tela e o servidor não
        // discordarem — e o asterisco acompanha a troca do setor na hora.
        var regra = regraInicial || {};

        return {
            enviando: false,
            inicialV:      (inicial && inicial.inicial)  || '',
            retirado:      (inicial && inicial.retirado) || '',
            finalV:        (inicial && inicial.final)    || '',
            glosado:       (inicial && inicial.glosado)  || '',
            aceito:        (inicial && inicial.aceito)   || '',
            setor:         regra.setor || '',
            situacao:      regra.situacao || '',

            get exigeInicial() {
                if (!regra.tipoExige) { return false; }
                if (!regra.temSetor)  { return true; }
                return (regra.setoresIsentos || []).indexOf(this.setor) === -1;
            },

            enviar() { this.enviando = true; },

            // "1.234,56" e "1234.56" — as duas formas que aparecem no teclado
            // de quem digita o dia inteiro.
            num(valor) {
                var t = String(valor == null ? '' : valor).replace(/[R$\s]/g, '');
                if (t.indexOf(',') > -1) { t = t.replace(/\./g, '').replace(',', '.'); }
                var n = parseFloat(t);
                return isFinite(n) ? n : 0;
            },

            moeda(n) {
                return n.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            },

            pct(parte, base) {
                if (!(base > 0)) { return '—'; }
                return (parte / base * 100).toFixed(2).replace('.', ',') + '%';
            },

            // O acrescentado é o que fecha a diferença entre o que entrou e o
            // que saiu: é a identidade de sempre (final = inicial − retirado +
            // acrescentado) resolvida para o outro lado. Negativo quer dizer
            // que a conta saiu abaixo do que o retirado explica — acontece, e
            // por isso não é erro.
            get acrescentadoCalc() {
                return this.num(this.finalV) + this.num(this.retirado) - this.num(this.inicialV);
            },

            get acrescentadoBr() { return this.moeda(this.acrescentadoCalc); },
            get pctRetirado() { return this.pct(this.num(this.retirado), this.num(this.inicialV)); },
            get pctAcresc()   { return this.pct(this.acrescentadoCalc, this.num(this.inicialV)); },
            get pctGlosado()  { return this.pct(this.num(this.glosado), this.num(this.finalV)); },
            get pctAceito()   { return this.pct(this.num(this.aceito), this.num(this.glosado)); },

            // Retirar mais do que a conta tem é erro de digitação: a tela
            // avisa antes de o servidor recusar.
            get retirouDemais() {
                return this.num(this.inicialV) > 0 && this.num(this.retirado) > this.num(this.inicialV);
            },

            // Auditada sem valor final o servidor recusa. A tela diz antes —
            // e só quando a conta ainda não estava auditada, que é o mesmo
            // recorte da trava lá: a conta que já veio assim do AuditorHosp
            // continua podendo ser gravada.
            get auditadaSemFinal() {
                return this.situacao === regra.auditado
                    && !(this.num(this.finalV) > 0)
                    && !regra.jaAuditada;
            }
        };
    });

    Alpine.data('fichaOcupacional', function (inicial) {
        return {
            enviando: false,
            conclusao: (inicial && inicial.conclusao) || 'pendente',
            peso: (inicial && inicial.peso) || '',
            altura: (inicial && inicial.altura) || '',

            enviar() { this.enviando = true; },

            // Aceita 1,72 e 1.72: a vírgula é como se escreve em português, e
            // exigir ponto faria errar a medida mais básica da ficha.
            numero(valor) {
                var n = parseFloat(String(valor || '').replace(',', '.'));
                return isFinite(n) ? n : 0;
            },

            get imc() {
                var p = this.numero(this.peso);
                var a = this.numero(this.altura);

                if (p <= 0 || a <= 0) { return ''; }

                // Quem digita a altura em centímetros (172) em vez de metros
                // teria um IMC de 0,0 sem um palpite aqui.
                if (a > 3) { a = a / 100; }

                return (p / (a * a)).toFixed(1).replace('.', ',');
            },

            get faixa() {
                var v = this.numero(this.imc.replace(',', '.'));
                if (v <= 0) { return ''; }

                if (v < 18.5) { return 'Abaixo do peso'; }
                if (v < 25)   { return 'Peso adequado'; }
                if (v < 30)   { return 'Sobrepeso'; }
                if (v < 35)   { return 'Obesidade grau I'; }
                if (v < 40)   { return 'Obesidade grau II'; }
                return 'Obesidade grau III';
            }
        };
    });

    // ── Entrega de atestado: retorno calculado e forma "outro" ───────────
    // A conta do retorno é de papel: quem se afasta a partir do dia 10 por
    // 3 dias volta no dia 13. O campo continua editável — atestado de meio
    // período e retorno em fim de semana existem, e a conta não sabe disso.
    Alpine.data('entregaAtestado', function (inicial) {
        return {
            enviando: false,
            inicio: (inicial && inicial.inicio) || '',
            dias: (inicial && inicial.dias) || '',
            retorno: (inicial && inicial.retorno) || '',
            forma: (inicial && inicial.forma) || 'presencial',
            // Retorno digitado à mão não é recalculado por cima.
            mexeuNoRetorno: !!(inicial && inicial.retorno),

            enviar() { this.enviando = true; },

            calcular() {
                if (this.mexeuNoRetorno) { return; }

                var dias = parseInt(this.dias, 10);

                if (!this.inicio || !(dias > 0)) { this.retorno = ''; return; }

                // Data em UTC de propósito: somar dias num Date local erra
                // na noite em que o horário de verão começa.
                var partes = this.inicio.split('-');
                var d = new Date(Date.UTC(+partes[0], +partes[1] - 1, +partes[2]));
                d.setUTCDate(d.getUTCDate() + dias);

                this.retorno = d.toISOString().slice(0, 10);
            },

            get previsao() {
                if (!this.retorno) { return ''; }
                var p = this.retorno.split('-');
                return p[2] + '/' + p[1] + '/' + p[0];
            }
        };
    });

    // ── Autorização: os campos que dependem da situação ──────────────────
    // Data agendada só faz sentido depois do "sim" do convênio; motivo da
    // negativa, depois do "não". Mostrar os dois sempre encheria a tela de
    // campo que nunca vai ser preenchido.
    // A situação do pedido e o que está pendente andam juntos: enquanto o
    // convênio não respondeu, escrever uma pendência coloca o pedido em
    // "Informações pendentes" na gravação. O aviso ao vivo existe para que
    // ninguém descubra isso só depois, olhando a lista — a conta que vale
    // continua sendo a do servidor.
    Alpine.data('autorizacao', function (statusInicial, pendenteInicial) {
        var ANTES = ['aguardando', 'agendado', 'pendente'];

        return {
            enviando: false,
            status: statusInicial || 'aguardando',
            pendencia: pendenteInicial || '',

            get temPendencia() { return this.pendencia.trim() !== ''; },

            get antesDaResposta() { return ANTES.indexOf(this.status) !== -1; },

            /** Vai virar pendente ao gravar, embora o seletor mostre outra coisa. */
            get viraPendente() {
                return this.temPendencia && this.antesDaResposta && this.status !== 'pendente';
            },

            /** Já é a situação, e continua sendo enquanto houver texto. */
            get pendenteAtiva() {
                return this.status === 'pendente' && this.temPendencia;
            },

            /** Pendente sem dizer o que falta: o servidor vai recusar. */
            get pendenteVazia() {
                return this.status === 'pendente' && !this.temPendencia;
            },

            /** Escrita num pedido já respondido: fica como recado, não muda nada. */
            get pendenciaSemEfeito() {
                return this.temPendencia && !this.antesDaResposta;
            },

            enviar() { this.enviando = true; }
        };
    });

    // ── Códigos de exame: as linhas de TUSS ──────────────────────────────
    // Um exame tem 1..N códigos. Cada linha busca na Tabela 22 da ANS e
    // traz a descrição oficial junto, mas aceita código que não esteja lá:
    // nem todo convênio usa TUSS, e travar por isso impediria cadastrar o
    // que existe de verdade.
    Alpine.data('codigosTuss', function (config) {
        // Cada linha tem o seu próprio temporizador e o seu próprio
        // AbortController. Compartilhar os dois fazia a busca de uma linha
        // cancelar a da outra quando se preenche várias seguidas.
        function nova(codigo, termo) {
            return {
                codigo: codigo || '',
                termo: termo || '',
                // auto = a descrição veio da Tabela 22, não da mão de quem
                // digitou. Só o que é auto pode ser trocado sozinho.
                auto: false,
                sugestoes: [],
                aberto: false,
                temporizador: null,
                controlador: null
            };
        }

        return {
            enviando: false,
            url: config.url,
            linhas: (config.linhas && config.linhas.length)
                    ? config.linhas.map(function (l) { return nova(l.codigo, l.termo); })
                    : [nova('', '')],

            enviar() { this.enviando = true; },

            acrescentar() {
                this.linhas.push(nova('', ''));
                this.$nextTick(() => {
                    var campos = this.$root.querySelectorAll('input[name="codigo[]"]');
                    if (campos.length) { campos[campos.length - 1].focus(); }
                });
            },

            remover(i) {
                clearTimeout(this.linhas[i].temporizador);
                this.linhas.splice(i, 1);
                if (!this.linhas.length) { this.acrescentar(); }
            },

            digitou(linha) {
                clearTimeout(linha.temporizador);
                var q = String(linha.codigo || '').trim();

                // Mudou o código: a descrição que o sistema tinha posto não
                // vale mais. A que a pessoa escreveu, sim — essa fica.
                if (linha.auto) { linha.termo = ''; linha.auto = false; }

                if (q.length < 2) { linha.sugestoes = []; linha.aberto = false; return; }

                linha.temporizador = setTimeout(() => { this.buscar(linha, q); }, 250);
            },

            buscar(linha, q) {
                if (linha.controlador) { linha.controlador.abort(); }
                linha.controlador = new AbortController();

                fetch(this.url + '&q=' + encodeURIComponent(q), {
                    signal: linha.controlador.signal,
                    headers: { 'Accept': 'application/json' },
                    credentials: 'same-origin'
                })
                    .then(r => r.ok ? r.json() : [])
                    .then(itens => {
                        linha.sugestoes = Array.isArray(itens) ? itens : [];
                        linha.aberto = linha.sugestoes.length > 0;
                        this.casarExato(linha);
                    })
                    .catch(() => {});
            },

            // Quem sabe o código de cor digita os oito dígitos e segue para o
            // campo seguinte, sem olhar a lista. Se o que foi digitado é
            // exatamente um código da Tabela 22, a descrição entra sozinha.
            casarExato(linha) {
                var q = String(linha.codigo || '').trim();
                if (q === '') { return false; }

                var achado = linha.sugestoes.find(function (s) {
                    return String(s.codigo) === q;
                });

                if (!achado) { return false; }

                // Não passa por cima do que foi escrito à mão.
                if (String(linha.termo || '').trim() === '' || linha.auto) {
                    linha.termo = achado.termo;
                    linha.auto = true;
                }
                return true;
            },

            escolher(linha, item) {
                linha.codigo = String(item.codigo);
                linha.termo  = item.termo;
                linha.auto   = true;
                linha.sugestoes = [];
                linha.aberto = false;
            },

            // A descrição foi escrita à mão: o sistema não mexe mais nela.
            escreveu(linha) { linha.auto = false; },

            fechar(linha) {
                // Sem timeout, o clique na sugestão fecharia a lista antes de
                // o navegador registrar em qual item foi.
                setTimeout(() => { linha.aberto = false; }, 150);

                // Saiu do campo com código preenchido e descrição vazia:
                // última tentativa de achar o termo oficial. É o caso de quem
                // colou o código e passou direto — e o de quem digitou rápido
                // demais para a busca do @input ter voltado.
                var q = String(linha.codigo || '').trim();
                if (q === '' || String(linha.termo || '').trim() !== '') { return; }

                clearTimeout(linha.temporizador);
                if (this.casarExato(linha)) { return; }

                fetch(this.url + '&q=' + encodeURIComponent(q), {
                    headers: { 'Accept': 'application/json' },
                    credentials: 'same-origin'
                })
                    .then(r => r.ok ? r.json() : [])
                    .then(itens => {
                        linha.sugestoes = Array.isArray(itens) ? itens : [];
                        this.casarExato(linha);
                    })
                    .catch(() => {});
            },

            get quantos() {
                return this.linhas.filter(l => String(l.codigo || '').trim() !== '').length;
            }
        };
    });

    // ── Mostrar/ocultar senha ────────────────────────────────────────────
    Alpine.data('senha', function () {
        return {
            visivel: false,
            alternar() { this.visivel = !this.visivel; },
            get tipo() { return this.visivel ? 'text' : 'password'; }
        };
    });

    // ── Autocomplete da tabela TUSS ──────────────────────────────────────
    Alpine.data('tussAutocomplete', function (config) {
        return {
            url: config.url,
            busca: config.rotulo || '',
            codigo: config.codigo || '',
            termo: config.termo || '',
            itens: [],
            aberto: false,
            carregando: false,
            indice: -1,
            temporizador: null,
            controlador: null,

            get selecionado() { return this.codigo !== '' && this.codigo !== '0'; },

            digitou() {
                this.codigo = '';
                this.termo  = '';
                this.indice = -1;

                clearTimeout(this.temporizador);

                var q = this.busca.trim();
                if (q.length < 2) { this.itens = []; this.aberto = false; return; }

                this.temporizador = setTimeout(() => { this.buscar(q); }, 250);
            },

            buscar(q) {
                if (this.controlador) { this.controlador.abort(); }
                this.controlador = new AbortController();
                this.carregando  = true;

                fetch(this.url + '&q=' + encodeURIComponent(q), {
                    signal: this.controlador.signal,
                    headers: { 'Accept': 'application/json' },
                    credentials: 'same-origin'
                })
                    .then(r => r.ok ? r.json() : [])
                    .then(itens => {
                        this.itens  = Array.isArray(itens) ? itens : [];
                        this.aberto = this.itens.length > 0;
                        this.carregando = false;
                    })
                    .catch(() => { this.carregando = false; });
            },

            escolher(item) {
                this.codigo = String(item.codigo);
                this.termo  = item.termo;
                this.busca  = item.codigo + ' — ' + item.termo;
                this.aberto = false;
                this.itens  = [];
                this.indice = -1;
            },

            limpar() {
                this.busca = ''; this.codigo = ''; this.termo = '';
                this.itens = []; this.aberto = false;
                if (this.$refs.entrada) this.$refs.entrada.focus();
            },

            desce() { if (this.aberto && this.indice < this.itens.length - 1) this.indice++; },
            sobe()  { if (this.aberto && this.indice > 0) this.indice--; },

            enter(evento) {
                if (this.aberto && this.indice >= 0) {
                    evento.preventDefault();
                    this.escolher(this.itens[this.indice]);
                }
            },

            fechar() { setTimeout(() => { this.aberto = false; }, 150); }
        };
    });

    // ── Seleção de anexos ────────────────────────────────────────────────
    // O teto chega em bytes, calculado no servidor: desde que o documento
    // mora no banco, o limite real depende do max_allowed_packet da
    // hospedagem, e não de um número escrito aqui.
    Alpine.data('anexos', function (maxBytes) {
        return {
            arquivos: [],
            maxBytes: maxBytes,

            selecionou(evento) {
                this.arquivos = Array.from(evento.target.files).map(f => ({
                    nome: f.name,
                    tamanho: this.formatar(f.size),
                    grande: f.size > this.maxBytes
                }));
            },

            formatar(bytes) {
                if (bytes < 1024) return bytes + ' B';
                if (bytes < 1048576) return (bytes / 1024).toFixed(1).replace('.', ',') + ' KB';
                return (bytes / 1048576).toFixed(1).replace('.', ',') + ' MB';
            },

            get algumGrande() { return this.arquivos.some(a => a.grande); }
        };
    });

    // ── Matriz de permissões ─────────────────────────────────────────────
    Alpine.data('matrizPermissoes', function () {
        return {
            enviando: false,

            init() { this.recontarTudo(); },

            enviar() { this.enviando = true; },

            marcarLinha(evento) {
                var linha = evento.currentTarget.closest('tr');
                var marcar = evento.currentTarget.dataset.acao === 'marcar';
                linha.querySelectorAll('input[type=checkbox]').forEach(c => { c.checked = marcar; });
                this.recontar(linha);
            },

            marcarTudo(marcar) {
                this.$root.querySelectorAll('tbody input[type=checkbox]').forEach(c => { c.checked = marcar; });
                this.recontarTudo();
            },

            // "Ver" é pré-requisito: marcar qualquer outra ação marca o ver
            garantirVer(evento) {
                var caixa = evento.currentTarget;
                var linha = caixa.closest('tr');

                if (caixa.checked) {
                    var ver = linha.querySelector('input[data-tipo="pode_ver"]');
                    if (ver) ver.checked = true;
                }

                this.recontar(linha);
            },

            aoMudar(evento) {
                this.recontar(evento.currentTarget.closest('tr'));
            },

            // Resumo por linha: "3 de 4" — mostra o acesso do módulo de relance
            recontar(linha) {
                if (!linha) return;
                var caixas = linha.querySelectorAll('input[type=checkbox]');
                var marcadas = linha.querySelectorAll('input[type=checkbox]:checked').length;
                var alvo = linha.querySelector('[data-resumo]');
                if (alvo) {
                    alvo.textContent = marcadas === 0 ? 'sem acesso' : marcadas + ' de ' + caixas.length;
                    alvo.dataset.vazio = marcadas === 0 ? '1' : '0';
                }
            },

            recontarTudo() {
                this.$root.querySelectorAll('tbody tr').forEach(l => this.recontar(l));
            }
        };
    });
    /* Formulário de bloco da agenda: soma capacidade e marcadas enquanto
       a pessoa digita, e mostra as vagas livres de cada tipo de exame. */
    Alpine.data('blocoForm', function () {
        return {
            enviando: false,
            totalCapacidade: 0,
            totalOcupadas: 0,
            vagas: {},

            init() { this.recalcular(); },

            enviar() { this.enviando = true; },

            numero(el) { return parseInt(String(el.value).replace(/\D/g, ''), 10) || 0; },

            recalcular() {
                var vagas = {}, cap = 0, ocu = 0;

                this.$root.querySelectorAll('input[data-vaga]').forEach(campo => {
                    var id = campo.dataset.vaga;
                    var n  = this.numero(campo);

                    if (!vagas[id]) vagas[id] = { capacidade: 0, ocupadas: 0 };
                    vagas[id][campo.dataset.papel] = n;

                    if (campo.dataset.papel === 'capacidade') { cap += n; } else { ocu += n; }
                });

                this.vagas = vagas;
                this.totalCapacidade = cap;
                this.totalOcupadas = ocu;
            },

            livres(id) {
                var v = this.vagas[id];
                return v ? v.capacidade - v.ocupadas : 0;
            }
        };
    });
    /* Editor de blocos do POP: adicionar, remover e reordenar antes de salvar. */
    Alpine.data('popBlocos', function (iniciais) {
        return {
            enviando: false,
            blocos: Array.isArray(iniciais) && iniciais.length
                ? iniciais
                : [{ tipo: 'passos', titulo: 'O que fazer', conteudo: '' }],

            enviar() { this.enviando = true; },

            adicionar(tipo) {
                this.blocos.push({ tipo: tipo, titulo: '', conteudo: '' });
            },

            remover(i) {
                this.blocos.splice(i, 1);
                if (!this.blocos.length) this.adicionar('texto');
            },

            mover(i, passo) {
                var destino = i + passo;
                if (destino < 0 || destino >= this.blocos.length) return;
                var t = this.blocos[i];
                this.blocos[i] = this.blocos[destino];
                this.blocos[destino] = t;
            }
        };
    });

    /* Botão "Copiar mensagem". navigator.clipboard exige HTTPS ou localhost;
       o textarea escondido é o caminho que funciona em HTTP na rede interna. */
    Alpine.data('copiar', function () {
        return {
            copiado: false,

            copiarTexto(texto) {
                var pronto = () => {
                    this.copiado = true;
                    setTimeout(() => { this.copiado = false; }, 2000);
                };

                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(texto).then(pronto, () => this.viaCampo(texto, pronto));
                    return;
                }
                this.viaCampo(texto, pronto);
            },

            viaCampo(texto, pronto) {
                var campo = document.createElement('textarea');
                campo.value = texto;
                campo.setAttribute('readonly', '');
                campo.style.position = 'fixed';
                campo.style.opacity = '0';
                document.body.appendChild(campo);
                campo.select();
                try { document.execCommand('copy'); pronto(); } catch (e) { /* silencioso */ }
                document.body.removeChild(campo);
            }
        };
    });

    // ── OPME: as parcelas de uma nota ────────────────────────────────────
    // O parcelamento é sempre igual: divide o total, primeiro vencimento
    // numa data, os outros de mês em mês. Mas o fornecedor às vezes ajusta
    // centavos ou muda uma data, então cada linha continua editável.
    Alpine.data('opmeParcelas', function (iniciais) {
        return {
            enviando: false,
            linhas: iniciais || [],
            quantas: (iniciais && iniciais.length) || 1,
            primeiro: (iniciais && iniciais.length) ? iniciais[0].vencimento : '',
            valorTotal: document.getElementById('valor_total')
                        ? document.getElementById('valor_total').value : '',

            init() {
                if (!this.primeiro) {
                    var hoje = new Date();
                    this.primeiro = hoje.toISOString().slice(0, 10);
                }
            },

            numero(texto) {
                var v = String(texto || '').replace(/[R$\s]/g, '');
                var virgula = v.lastIndexOf(','), ponto = v.lastIndexOf('.');
                if (virgula > -1 && virgula > ponto) {
                    v = v.replace(/\./g, '').replace(',', '.');
                } else if (virgula === -1 && /\.\d{3}$/.test(v)) {
                    v = v.replace(/\./g, '');       // "4.450" são quatro mil e quatrocentos e cinquenta
                } else {
                    v = v.replace(/,/g, '');
                }
                var n = parseFloat(v);
                return isNaN(n) ? 0 : n;
            },

            moeda(n) {
                return n.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            },

            /* Deixa digitar só o que é dinheiro. Sem isso, uma letra no meio
               vira zero silenciosamente na hora de somar. */
            soDinheiro(ev) {
                var limpo = ev.target.value.replace(/[^\d.,]/g, '');
                if (limpo !== ev.target.value) {
                    var pos = ev.target.selectionStart - 1;
                    ev.target.value = limpo;
                    ev.target.dispatchEvent(new Event('input'));
                    ev.target.setSelectionRange(pos, pos);
                }
                this.marcarSujo();
            },

            /* Ao sair do campo, o que foi digitado vira o padrão brasileiro:
               "17800" → "17.800,00", "17800,5" → "17.800,50". Formatar só na
               saída é de propósito — máscara que empurra centavos da direita
               transforma "17800" em "178,00" sem ninguém perceber, e num
               módulo financeiro isso é um erro de cem vezes. */
            formatar(texto) {
                if (String(texto || '').trim() === '') { return ''; }
                return this.moeda(this.numero(texto));
            },

            get soma() {
                return this.linhas.reduce((s, l) => s + this.numero(l.valor), 0);
            },
            get total() { return this.numero(this.valorTotal); },
            get somaFormatada() { return 'R$ ' + this.moeda(this.soma); },
            get totalFormatado() { return 'R$ ' + this.moeda(this.total); },
            get bate() { return Math.abs(this.soma - this.total) <= 1 || !this.linhas.length; },

            /** Divide o total em N vezes, mês a mês a partir do primeiro. */
            gerar() {
                var n = Math.max(1, Math.min(parseInt(this.quantas, 10) || 1, 60));
                var total = this.total;
                // A sobra dos centavos vai na primeira parcela, que é como o
                // fornecedor costuma fazer.
                var base = Math.floor((total / n) * 100) / 100;
                var sobra = Math.round((total - base * n) * 100) / 100;

                var partes = this.primeiro.split('-');
                var ano = parseInt(partes[0], 10), mes = parseInt(partes[1], 10) - 1,
                    dia = parseInt(partes[2], 10);

                var novas = [];
                for (var i = 0; i < n; i++) {
                    var d = new Date(ano, mes + i, 1);
                    var ultimo = new Date(d.getFullYear(), d.getMonth() + 1, 0).getDate();
                    d.setDate(Math.min(dia, ultimo));      // 31 em fevereiro vira o dia 28
                    novas.push({
                        vencimento: d.getFullYear() + '-' +
                                    String(d.getMonth() + 1).padStart(2, '0') + '-' +
                                    String(d.getDate()).padStart(2, '0'),
                        valor: this.moeda(i === 0 ? base + sobra : base),
                    });
                }
                this.linhas = novas;
            },

            adicionar() {
                var ultima = this.linhas[this.linhas.length - 1];
                var venc = this.primeiro;
                if (ultima && ultima.vencimento) {
                    var p = ultima.vencimento.split('-');
                    var d = new Date(parseInt(p[0], 10), parseInt(p[1], 10), 1);
                    var ultimo = new Date(d.getFullYear(), d.getMonth() + 1, 0).getDate();
                    d.setDate(Math.min(parseInt(p[2], 10), ultimo));
                    venc = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') +
                           '-' + String(d.getDate()).padStart(2, '0');
                }
                this.linhas.push({ vencimento: venc, valor: '' });
            },

            remover(i) { this.linhas.splice(i, 1); },
            marcarSujo() { /* o total mudou; a soma é recalculada sozinha */ },
            enviar() { this.enviando = true; }
        };
    });
});

/* ── Selects que navegam ──────────────────────────────────────────────────
   Um <select data-navegar> em que cada opção é uma URL. Usado pelo "quantas
   por página" do rodapé das listagens.

   É um ouvinte delegado, e não um onchange no HTML, porque a CSP do sistema
   bloqueia script embutido — inclusive atributo de evento. */
document.addEventListener('change', function (ev) {
    var alvo = ev.target;
    if (alvo && alvo.matches && alvo.matches('select[data-navegar]') && alvo.value) {
        window.location.href = alvo.value;
    }
});

/* ── Botão que manda imprimir ─────────────────────────────────────────────
   Mesma razão do de cima: `onclick="window.print()"` é atributo de evento, e
   a CSP bloqueia. A ficha de saúde ocupacional usa este botão. */
document.addEventListener('click', function (ev) {
    var alvo = ev.target;
    if (alvo && alvo.closest && alvo.closest('[data-imprimir]')) {
        window.print();
    }
});
