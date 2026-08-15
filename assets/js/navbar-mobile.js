/**
 * assets/js/navbar-mobile.js
 * Gère l'ouverture et la fermeture du menu de navigation sur les petits écrans.
 */
document.addEventListener('DOMContentLoaded', () => {
    const toggleButton = document.getElementById('navbar-toggle');
    const menu = document.getElementById('navbar-menu');

    if (!toggleButton || !menu) {
        return;
    }

    toggleButton.addEventListener('click', () => {
        const isOpen = menu.classList.toggle('is-open');
        toggleButton.setAttribute('aria-expanded', isOpen.toString());
    });

    // Ferme le menu si un clic est détecté en dehors du menu ou du bouton
    document.addEventListener('click', (event) => {
        if (!menu.contains(event.target) && !toggleButton.contains(event.target) && menu.classList.contains('is-open')) {
            menu.classList.remove('is-open');
            toggleButton.setAttribute('aria-expanded', 'false');
        }
    });
});