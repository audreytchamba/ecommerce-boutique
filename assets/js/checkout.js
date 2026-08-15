

function renderCheckoutSummary() {
    const items = cartGetAll();
    const container = document.getElementById('checkout-summary-items');
    const totalEl = document.getElementById('checkout-summary-total');
    const emptyWarning = document.getElementById('checkout-empty-warning');
    const submitBtn = document.getElementById('checkout-submit-btn');

    if (!container || !totalEl) return;

    if (items.length === 0) {
        container.innerHTML = '<p>Votre panier est vide.</p>';
        totalEl.textContent = '0 FCFA';
        if (emptyWarning) emptyWarning.style.display = 'block';
        if (submitBtn) submitBtn.disabled = true;
        return;
    }

    if (emptyWarning) emptyWarning.style.display = 'none';
    if (submitBtn) submitBtn.disabled = false;

    container.innerHTML = items
        .map(
            (item) => `
        <div class="checkout-summary__item">
            <span>${escapeHtml(item.name)} × ${item.quantity}</span>
            <span>${(item.price * item.quantity).toLocaleString('fr-FR')} FCFA</span>
        </div>
    `
        )
        .join('');

    totalEl.textContent = `${cartGetTotal().toLocaleString('fr-FR')} FCFA`;
}


function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

document.addEventListener('DOMContentLoaded', () => {
    renderCheckoutSummary();
    document.addEventListener('cart:updated', renderCheckoutSummary);

    const form = document.getElementById('checkout-form');
    if (!form) return;

    form.addEventListener('submit', (event) => {
        const items = cartGetAll();

        if (items.length === 0) {
            event.preventDefault();
            document.getElementById('checkout-empty-warning').style.display = 'block';
            return;
        }

      
        document.getElementById('cart_items_json').value = JSON.stringify(items);

        const submitBtn = document.getElementById('checkout-submit-btn');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Envoi en cours...';
        }
        
    });
});
