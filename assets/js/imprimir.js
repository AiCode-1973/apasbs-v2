/**
 * Botão de impressão das folhas geradas pelo sistema.
 *
 * Fica num arquivo próprio porque a política de segurança do sistema
 * (CSP) bloqueia script inline — nada de onclick="window.print()".
 */
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-imprimir]').forEach(function (botao) {
        botao.addEventListener('click', function (evento) {
            evento.preventDefault();
            window.print();
        });
    });
});
