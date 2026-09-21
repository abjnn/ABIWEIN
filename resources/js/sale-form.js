/** Active les lignes dynamiques uniquement sur le formulaire de vente. */
const initializeSaleForm = () => {
    // L’absence du conteneur signifie que l’utilisateur consulte une autre page.
    const linesContainer = document.getElementById('sale-lines');

    if (!linesContainer) {
        return;
    }

    // Ces références correspondent aux identifiants documentés dans la vue Blade.
    const template = document.getElementById('sale-line-template');
    const addButton = document.getElementById('add-sale-line');
    const typeField = document.getElementById('sale_type');
    const clientField = document.getElementById('client_id');
    const paymentGroup = document.getElementById('initial-payment-group');
    const paymentField = document.getElementById('initial_payment');
    const grandTotal = document.getElementById('sale-grand-total');
    let nextIndex = Number(linesContainer.dataset.nextIndex) || 1;

    // Le format français rend les montants estimés lisibles en francs CFA.
    const formatMoney = (value) => `${new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 }).format(value)} FCFA`;

    // Le serveur recalculera le total ; cette fonction fournit uniquement un aperçu immédiat.
    const updateGrandTotal = () => {
        const total = [...linesContainer.querySelectorAll('[data-sale-line]')].reduce((sum, line) => {
            const quantity = Number(line.querySelector('[data-quantity]').value) || 0;
            const price = Number(line.querySelector('[data-price]').value) || 0;
            line.querySelector('[data-line-total]').textContent = formatMoney(quantity * price);

            return sum + (quantity * price);
        }, 0);

        grandTotal.textContent = formatMoney(total);
    };

    // Cette fonction relie le calcul financier et le retrait d'une ligne.
    const bindLine = (line) => {
        const removeButton = line.querySelector('[data-remove-line]');

        [line.querySelector('[data-quantity]'), line.querySelector('[data-price]')]
            .forEach((field) => field.addEventListener('input', updateGrandTotal));

        removeButton.addEventListener('click', () => {
            if (linesContainer.querySelectorAll('[data-sale-line]').length > 1) {
                line.remove();
                updateGrandTotal();
            }
        });
    };

    // Une nouvelle ligne reçoit un index unique compatible avec la validation Laravel.
    addButton.addEventListener('click', () => {
        const wrapper = document.createElement('div');
        wrapper.innerHTML = template.innerHTML.replaceAll('__INDEX__', String(nextIndex++));
        const newLine = wrapper.firstElementChild;
        linesContainer.appendChild(newLine);
        bindLine(newLine);
        updateGrandTotal();
    });

    // Les champs client et premier versement ne deviennent obligatoires qu’à crédit.
    const updateSaleType = () => {
        const isCredit = typeField.value === 'credit';
        clientField.required = isCredit;
        paymentField.disabled = !isCredit;
        paymentGroup.classList.toggle('opacity-50', !isCredit);
    };

    linesContainer.querySelectorAll('[data-sale-line]').forEach(bindLine);
    typeField.addEventListener('change', updateSaleType);
    updateSaleType();
    updateGrandTotal();
};

// Le module est différé par Vite, mais ce contrôle reste robuste pendant les tests.
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeSaleForm, { once: true });
} else {
    initializeSaleForm();
}
