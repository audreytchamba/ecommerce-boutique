/* assets/js/cart-page.js
 * Rendu du panier côté client en se basant sur les fonctions de assets/js/cart.js
 */

document.addEventListener('DOMContentLoaded', () => {
    function renderCart() {
        const items = cartGetAll();
        const list = document.getElementById('cart-list');
        const empty = document.getElementById('cart-empty');
        const totalEl = document.getElementById('cart-total');

        if (!list || !empty || !totalEl) return;

        if (items.length === 0) {
            list.innerHTML = '';
            empty.style.display = 'block';
            totalEl.textContent = `0 ${window.CURRENCY_SYMBOL || ''}`;
            document.getElementById('btn-checkout').setAttribute('aria-disabled', 'true');
            document.getElementById('btn-checkout').classList.add('disabled');
            return;
        }

        empty.style.display = 'none';
        document.getElementById('btn-checkout').removeAttribute('aria-disabled');
        document.getElementById('btn-checkout').classList.remove('disabled');

        let html = '<div class="cart-items-grid">';
        items.forEach((it) => {
            html += `
                <div class="cart-item" data-id="${it.id}">
                    <div class="cart-item-media">
                        <img src="${it.image}" alt="${it.name}" width="90" />
                    </div>
                    <div class="cart-item-body">
                        <div class="cart-item-name">${it.name}</div>
                        <div class="cart-item-price">${(it.price).toLocaleString()} ${window.CURRENCY_SYMBOL || ''}</div>
                        <div class="cart-item-controls">
                            <label>Qté: <input type="number" class="cart-qty" min="0" value="${it.quantity}" data-id="${it.id}"></label>
                            <button class="btn btn-link cart-remove" data-id="${it.id}">Supprimer</button>
                        </div>
                    </div>
                </div>
            `;
        });
        html += '</div>';
        list.innerHTML = html;

        // Attach events
        list.querySelectorAll('.cart-qty').forEach((input) => {
            input.addEventListener('change', (e) => {
                const id = e.target.dataset.id;
                const qty = parseInt(e.target.value, 10) || 0;
                cartUpdateQuantity(id, qty);
            });
        });

        list.querySelectorAll('.cart-remove').forEach((btn) => {
            btn.addEventListener('click', (e) => {
                const id = e.target.dataset.id;
                cartRemoveItem(id);
            });
        });

        totalEl.textContent = `${cartGetTotal().toLocaleString()} ${window.CURRENCY_SYMBOL || ''}`;
    }

    // Expose currency symbol from server-rendered CURRENCY_SYMBOL
    window.CURRENCY_SYMBOL = document.getElementById('cart-total') ? document.getElementById('cart-total').textContent.replace(/\d|\s|\.|,|\u00A0/g,'') : '';

    renderCart();
    document.addEventListener('cart:updated', renderCart);
});
