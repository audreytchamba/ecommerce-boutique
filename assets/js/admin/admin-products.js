
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.js-confirm-delete').forEach((button) => {
        button.addEventListener('click', (event) => {
            const productName = button.dataset.productName || 'ce produit';
            const confirmed = window.confirm(
                'Voulez-vous vraiment supprimer "' + productName + '" ?\n' +
                'Cette action est irréversible et supprimera également ' +
                'son média principal et toutes les images de sa galerie.'
            );

            if (!confirmed) {
                event.preventDefault();
            }
        });
    });
});
