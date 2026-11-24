document.addEventListener('DOMContentLoaded', () => {
    // 1. Mobile sidebar toggle
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.getElementById('sidebarBackdrop');

    if (menuToggle && sidebar && backdrop) {
        const closeMenu = () => {
            sidebar.classList.remove('open');
            backdrop.classList.remove('open');
        };
        menuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('open');
            backdrop.classList.toggle('open');
        });
        backdrop.addEventListener('click', closeMenu);
    }

    // 2. Confirm dialogs
    document.querySelectorAll('[data-confirm]').forEach((el) => {
        el.addEventListener('click', (ev) => {
            const msg = el.getAttribute('data-confirm') || '¿Confirmar esta acción?';
            if (!window.confirm(msg)) {
                ev.preventDefault();
            }
        });
    });

    // 3. Dynamic Quote Line Items
    const quoteBody = document.getElementById('quoteItemsBody');
    const addRowBtn = document.getElementById('addQuoteRow');
    const ivaInput = document.getElementById('iva_porcentaje');

    if (quoteBody && addRowBtn) {
        const recalc = () => {
            let subtotal = 0;
            quoteBody.querySelectorAll('tr.quote-row').forEach((row) => {
                const qty = parseFloat(row.querySelector('.item-qty')?.value || '0') || 0;
                const price = parseFloat(row.querySelector('.item-price')?.value || '0') || 0;
                const lineTotal = qty * price;
                subtotal += lineTotal;
                const lineCell = row.querySelector('.item-subtotal');
                if (lineCell) {
                    lineCell.textContent = '$ ' + lineTotal.toFixed(2);
                }
            });

            const ivaPct = parseFloat(ivaInput?.value || '16') || 0;
            const ivaAmt = subtotal * (ivaPct / 100);
            const total = subtotal + ivaAmt;

            const subEl = document.getElementById('quoteSubtotal');
            const ivaEl = document.getElementById('quoteIva');
            const totEl = document.getElementById('quoteTotal');
            if (subEl) subEl.textContent = '$ ' + subtotal.toFixed(2);
            if (ivaEl) ivaEl.textContent = '$ ' + ivaAmt.toFixed(2);
            if (totEl) totEl.textContent = '$ ' + total.toFixed(2);
        };

        const bindRowEvents = (row) => {
            const select = row.querySelector('.item-service');
            const desc = row.querySelector('.item-desc');
            const price = row.querySelector('.item-price');
            const qty = row.querySelector('.item-qty');
            const rm = row.querySelector('.remove-row');

            if (select) {
                select.addEventListener('change', () => {
                    const opt = select.options[select.selectedIndex];
                    if (opt && opt.value) {
                        const sName = opt.getAttribute('data-name') || '';
                        const sPrice = opt.getAttribute('data-price') || '0';
                        if (desc && !desc.value.trim()) {
                            desc.value = sName;
                        } else if (desc) {
                            desc.value = sName;
                        }
                        if (price) {
                            price.value = parseFloat(sPrice).toFixed(2);
                        }
                        recalc();
                    }
                });
            }

            if (qty) qty.addEventListener('input', recalc);
            if (price) price.addEventListener('input', recalc);

            if (rm) {
                rm.addEventListener('click', () => {
                    if (quoteBody.querySelectorAll('tr.quote-row').length > 1) {
                        row.remove();
                        recalc();
                    }
                });
            }
        };

        quoteBody.querySelectorAll('tr.quote-row').forEach(bindRowEvents);

        addRowBtn.addEventListener('click', () => {
            const firstRow = quoteBody.querySelector('tr.quote-row');
            if (!firstRow) return;
            const clone = firstRow.cloneNode(true);
            clone.querySelectorAll('input').forEach((inp) => {
                if (inp.classList.contains('item-qty')) {
                    inp.value = '1';
                } else if (inp.classList.contains('item-price')) {
                    inp.value = '0.00';
                } else {
                    inp.value = '';
                }
            });
            const sel = clone.querySelector('select');
            if (sel) sel.selectedIndex = 0;
            const sub = clone.querySelector('.item-subtotal');
            if (sub) sub.textContent = '$ 0.00';

            quoteBody.appendChild(clone);
            bindRowEvents(clone);
            recalc();
        });

        if (ivaInput) {
            ivaInput.addEventListener('input', recalc);
        }

        recalc();
    }
});
