/**
 * assets/js/product-gallery.js
 * Gestion de la galerie d'images sur la page détail produit
 */

document.addEventListener('DOMContentLoaded', function () {
    const mainImage = document.getElementById('main-image');
    const thumbButtons = document.querySelectorAll('.thumb-item');
    const quantityInput = document.getElementById('quantity');
    const qtyMinusBtn = document.querySelector('.qty-minus');
    const qtyPlusBtn = document.querySelector('.qty-plus');
    const addToCartBtn = document.querySelector('.js-add-to-cart');
    const message = document.getElementById('add-to-cart-message');

    // Galerie : changer l'image principale en cliquant sur les miniatures
    thumbButtons.forEach(button => {
        button.addEventListener('click', function () {
            const src = this.dataset.src;
            mainImage.src = src;

            // Mettre à jour l'indicateur actif
            thumbButtons.forEach(btn => btn.classList.remove('is-active'));
            this.classList.add('is-active');
        });
    });

    // Contrôles de quantité
    if (qtyMinusBtn) {
        qtyMinusBtn.addEventListener('click', function () {
            const currentVal = parseInt(quantityInput.value) || 1;
            quantityInput.value = Math.max(1, currentVal - 1);
        });
    }

    if (qtyPlusBtn) {
        qtyPlusBtn.addEventListener('click', function () {
            const currentVal = parseInt(quantityInput.value) || 1;
            quantityInput.value = Math.min(100, currentVal + 1);
        });
    }

    // Valider la saisie manuelle de quantité
    if (quantityInput) {
        quantityInput.addEventListener('change', function () {
            let val = parseInt(this.value) || 1;
            val = Math.max(1, Math.min(100, val));
            this.value = val;
        });
    }

    // Ajouter au panier
    if (addToCartBtn) {
        addToCartBtn.addEventListener('click', function () {
            const id = parseInt(this.dataset.id);
            const name = this.dataset.name;
            const price = parseFloat(this.dataset.price);
            const image = this.dataset.image;
            const qty = parseInt(quantityInput.value) || 1;

            // Utiliser cartAddItem() depuis cart.js
            if (typeof cartAddItem === 'function') {
                for (let i = 0; i < qty; i++) {
                    cartAddItem(id, name, price, image);
                }

                // Afficher le message de confirmation
                message.style.display = 'block';
                setTimeout(() => {
                    message.style.display = 'none';
                }, 3000);
            }
        });
    }
});
