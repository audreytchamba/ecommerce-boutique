
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.js-confirm-delete').forEach((button) => {
        button.addEventListener('click', (event) => {
            const itemName = button.dataset.itemName || 'cet élément';
            const customMessage = button.dataset.confirmMessage;
            const confirmed = window.confirm(
                customMessage || `Voulez-vous vraiment supprimer "${itemName}" ?\nCette action est irréversible.`
            );

            if (!confirmed) {
                event.preventDefault();
            }
        });
    });
});
