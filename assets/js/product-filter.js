/**
 * assets/js/product-filter.js
 * Filtrage du catalogue (index.php) par catégorie, en JS pur, sans
 * rechargement de page. N'a aucune dépendance sur cart.js.
 */

document.addEventListener('DOMContentLoaded', () => {
    const filterButtons = document.querySelectorAll('#category-filters button');
    const cards = document.querySelectorAll('#product-grid .product-card');

    if (!filterButtons.length || !cards.length) {
        return;
    }

    filterButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const filter = button.dataset.filter;

            filterButtons.forEach((b) => b.classList.remove('is-active'));
            button.classList.add('is-active');

            cards.forEach((card) => {
                const matches = filter === 'all' || card.dataset.category === filter;
                card.style.display = matches ? '' : 'none';
            });
        });
    });
});
