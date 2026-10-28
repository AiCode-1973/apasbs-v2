/**
 * Aplica tema e estado da sidebar ANTES da primeira pintura.
 * Carregado de forma bloqueante no <head> justamente para não piscar.
 */
(function () {
    'use strict';

    var raiz = document.documentElement;

    // ── Tema claro/escuro ────────────────────────────────────────────────
    var salvo = null;
    try { salvo = localStorage.getItem('apasbs_tema'); } catch (e) { /* modo privado */ }

    var preferido = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches
        ? 'escuro'
        : 'claro';

    var tema = salvo || preferido;
    raiz.classList.toggle('dark', tema === 'escuro');

    // ── Sidebar recolhida ────────────────────────────────────────────────
    var colapsada = null;
    try { colapsada = localStorage.getItem('apasbs_sidebar'); } catch (e) { /* modo privado */ }

    if (colapsada === '1') {
        raiz.classList.add('sb-colapsada');
    }

    // API mínima usada pelos botões do topbar
    window.APASBS = {
        alternarTema: function () {
            var escuro = !raiz.classList.contains('dark');
            raiz.classList.toggle('dark', escuro);
            try { localStorage.setItem('apasbs_tema', escuro ? 'escuro' : 'claro'); } catch (e) {}
            document.dispatchEvent(new CustomEvent('apasbs:tema', { detail: { escuro: escuro } }));
        },
        temaEscuro: function () {
            return raiz.classList.contains('dark');
        },
        alternarSidebar: function () {
            var colapsou = !raiz.classList.contains('sb-colapsada');
            raiz.classList.toggle('sb-colapsada', colapsou);
            try { localStorage.setItem('apasbs_sidebar', colapsou ? '1' : '0'); } catch (e) {}
        }
    };
})();
