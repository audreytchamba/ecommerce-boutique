/**
 * assets/js/cart.js
 * Gestion complète du panier côté client via localStorage.
 * Ne connaît RIEN du checkout (voir checkout.js) ni du back-office.
 *
 * Structure d'un item panier stocké :
 * { id, name, price, image, quantity }
 */

const CART_STORAGE_KEY = 'ecom_cart_v1';

/* ============================ CŒUR DU PANIER ============================ */

function cartGetAll() {
    try {
        const raw = localStorage.getItem(CART_STORAGE_KEY);
        const items = raw ? JSON.parse(raw) : [];
        if (!Array.isArray(items)) {
            return [];
        }

        // On filtre pour ne garder que les items valides et complets.
        // Cela évite des erreurs si un item a été mal ajouté ou est corrompu.
        // Un item valide doit avoir un id, un nom, un prix (nombre) et une quantité (nombre > 0).
        return items.filter(item =>
            item &&
            item.id &&
            item.name &&
            typeof item.price === 'number' &&
            typeof item.quantity === 'number' &&
            item.quantity > 0
        );
    } catch (e) {
        console.error('Panier corrompu, réinitialisation.', e);
        localStorage.removeItem(CART_STORAGE_KEY); // On nettoie le localStorage pour éviter de futures erreurs.
        return [];
    }
}

function cartSaveAll(items) {
    localStorage.setItem(CART_STORAGE_KEY, JSON.stringify(items));
    cartUpdateBadge();
    document.dispatchEvent(new CustomEvent('cart:updated', { detail: items }));
}

function cartAddItem({ id, name, price, image }) {
    const items = cartGetAll();
    const existing = items.find((item) => String(item.id) === String(id));

    if (existing) {
        existing.quantity += 1;
    } else {
        items.push({
            id: String(id),
            name,
            price: Number(price),
            image,
            quantity: 1,
        });
    }

    cartSaveAll(items);
}

function cartUpdateQuantity(id, quantity) {
    let items = cartGetAll();
    quantity = Math.max(0, parseInt(quantity, 10) || 0);

    if (quantity === 0) {
        items = items.filter((item) => String(item.id) !== String(id));
    } else {
        items = items.map((item) =>
            String(item.id) === String(id) ? { ...item, quantity } : item
        );
    }

    cartSaveAll(items);
}

function cartRemoveItem(id) {
    const items = cartGetAll().filter((item) => String(item.id) !== String(id));
    cartSaveAll(items);
}

function cartClear() {
    cartSaveAll([]);
}

function cartGetTotal() {
    return cartGetAll().reduce((sum, item) => sum + item.price * item.quantity, 0);
}

function cartGetItemCount() {
    return cartGetAll().reduce((sum, item) => sum + item.quantity, 0);
}

/* ============================ BADGE NAVBAR ============================ */

function cartUpdateBadge() {
    const badge = document.getElementById('cart-badge');
    if (badge) {
        badge.textContent = cartGetItemCount();
    }
}

/* ============================ ÉCOUTEURS GLOBAUX ============================ */

document.addEventListener('DOMContentLoaded', () => {
    cartUpdateBadge();

    // Boutons "Ajouter au panier" présents sur le catalogue / fiche produit
    document.querySelectorAll('.js-add-to-cart').forEach((button) => {
        button.addEventListener('click', () => {
            const { id, name, price, image } = button.dataset;
            cartAddItem({ id, name, price, image });

            // Petit feedback visuel, sans bloquer l'UI avec une alert()
            button.classList.add('is-added');
            const originalText = button.textContent;
            button.textContent = 'Ajouté ✓';
            setTimeout(() => {
                button.classList.remove('is-added');
                button.textContent = originalText;
            }, 1200);
        });
    });
});
