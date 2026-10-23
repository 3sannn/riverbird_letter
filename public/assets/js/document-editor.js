(function () {
  const form = document.getElementById('document-form');
  const preview = document.getElementById('live-preview');
  if (!form || !preview) return;

  const debounce = (fn, ms) => {
    let t;
    return (...args) => {
      clearTimeout(t);
      t = setTimeout(() => fn(...args), ms);
    };
  };

  const collectItems = () => {
    const rows = form.querySelectorAll('.item-row');
    return Array.from(rows).map((row) => ({
      description: row.querySelector('[name="item_description[]"]')?.value || '',
      quantity: row.querySelector('[name="item_quantity[]"]')?.value || '1',
      unit_price: row.querySelector('[name="item_unit_price[]"]')?.value || '0',
    }));
  };

  const renderPreview = () => {
    const data = new FormData(form);
    data.append('ajax', 'preview');
    fetch(form.action, { method: 'POST', body: data })
      .then((r) => r.json())
      .then((res) => {
        if (res.html) preview.innerHTML = res.html;
      })
      .catch(() => {});
  };

  const updatePreviewLocally = () => {
    const clientSelect = form.querySelector('[name="client_id"]');
    const clientText = clientSelect?.selectedOptions[0]?.text || '';
    preview.querySelectorAll('[data-field="client"]').forEach((el) => {
      el.textContent = clientText;
    });
    preview.querySelectorAll('[data-field="document_date"]').forEach((el) => {
      el.textContent = form.querySelector('[name="document_date"]')?.value || '';
    });
    preview.querySelectorAll('[data-field="remarks"]').forEach((el) => {
      el.textContent = form.querySelector('[name="remarks"]')?.value || '';
    });
    const tbody = preview.querySelector('[data-items-body]');
    if (!tbody) return;
    tbody.innerHTML = '';
    let total = 0;
    collectItems().forEach((item, i) => {
      const qty = parseFloat(item.quantity) || 0;
      const price = parseFloat(item.unit_price) || 0;
      const line = qty * price;
      total += line;
      const tr = document.createElement('tr');
      tr.innerHTML = `<td>${i + 1}</td><td>${item.description}</td><td class="num">₹${line.toLocaleString('en-IN')}</td>`;
      tbody.appendChild(tr);
    });
    const totalEl = preview.querySelector('[data-field="total"]');
    if (totalEl) totalEl.textContent = '₹' + total.toLocaleString('en-IN');
  };

  form.addEventListener('input', debounce(updatePreviewLocally, 200));
  form.addEventListener('change', debounce(updatePreviewLocally, 200));

  document.getElementById('add-line-item')?.addEventListener('click', () => {
    const container = document.getElementById('line-items');
    const row = container.querySelector('.item-row').cloneNode(true);
    row.querySelectorAll('input').forEach((i) => (i.value = i.name.includes('quantity') ? '1' : i.name.includes('price') ? '0' : ''));
    container.appendChild(row);
    updatePreviewLocally();
  });

  form.addEventListener('click', (e) => {
    if (e.target.classList.contains('remove-line')) {
      const rows = form.querySelectorAll('.item-row');
      if (rows.length > 1) {
        e.target.closest('.item-row')?.remove();
        updatePreviewLocally();
      }
    }
  });

  updatePreviewLocally();
})();
