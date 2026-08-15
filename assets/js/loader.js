/**
 * loader.js
 * Gère l'affichage et le masquage du loader de page
 */

// Afficher le loader
function showLoader() {
    const loader = document.getElementById('loader');
    if (loader) {
        loader.classList.remove('hidden');
    }
}

// Masquer le loader
function hideLoader() {
    const loader = document.getElementById('loader');
    if (loader) {
        loader.classList.add('hidden');
    }
}

// Masquer le loader au chargement complet de la page
document.addEventListener('DOMContentLoaded', function() {
    hideLoader();
});

// Afficher le loader lors des changements de page (pour les liens internes)
document.addEventListener('click', function(e) {
    const link = e.target.closest('a');
    if (link && link.href && !link.target && link.hostname === window.location.hostname) {
        // Vérifier que ce n'est pas un lien d'ancre
        if (!link.href.includes('#')) {
            showLoader();
        }
    }
});

// Masquer le loader au déchargement de la page
window.addEventListener('beforeunload', function() {
    hideLoader();
});
