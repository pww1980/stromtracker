/* ═══════════════════════════════════════════════════════════════
   StromTracker – Settings JS
   ═══════════════════════════════════════════════════════════════ */

document.addEventListener('DOMContentLoaded', () => {

  // ── Add price ───────────────────────────────────────────────
  document.getElementById('addPriceForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const date  = document.getElementById('price_date').value;
    const price = parseFloat(document.getElementById('price_kwh').value);
    const note  = document.getElementById('price_note').value;

    try {
      await apiCall(BASE_PATH + '/api/settings.php', {
        method: 'POST',
        body: { action: 'add_price', valid_from: date, price_kwh: price, note },
      });
      showToast('Strompreis gespeichert!', 'success');
      setTimeout(() => location.reload(), 600);
    } catch (err) {
      showToast(err.message, 'error');
    }
  });

  // ── Delete price ─────────────────────────────────────────────
  document.querySelectorAll('.delete-price').forEach(btn => {
    btn.addEventListener('click', async () => {
      if (!confirm('Strompreis wirklich löschen?')) return;
      try {
        await apiCall(BASE_PATH + '/api/settings.php', {
          method: 'POST',
          body: { action: 'delete_price', id: parseInt(btn.dataset.id) },
        });
        btn.closest('tr').remove();
        showToast('Preis gelöscht', 'success');
      } catch (err) {
        showToast(err.message, 'error');
      }
    });
  });

  // ── Change password ──────────────────────────────────────────
  document.getElementById('pwForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const alertBox = document.getElementById('pwAlert');

    try {
      await apiCall(BASE_PATH + '/api/settings.php', {
        method: 'POST',
        body: {
          action:           'change_password',
          current_password: document.getElementById('current_pw').value,
          new_password:     document.getElementById('new_pw').value,
          confirm_password: document.getElementById('confirm_pw').value,
        },
      });
      showAlert(alertBox, 'Passwort erfolgreich geändert!', 'success');
      document.getElementById('pwForm').reset();
    } catch (err) {
      showAlert(alertBox, err.message, 'danger');
    }
  });

  // ── Contract start month ─────────────────────────────────────
  document.getElementById('contractForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const alertBox = document.getElementById('contractAlert');
    try {
      await apiCall(BASE_PATH + '/api/settings.php', {
        method: 'POST',
        body: {
          action:               'update_contract_month',
          contract_start_month: parseInt(document.getElementById('contract_start_month').value),
        },
      });
      showAlert(alertBox, 'Vertragsmonat gespeichert!', 'success');
      setTimeout(() => location.reload(), 800);
    } catch (err) {
      showAlert(alertBox, err.message, 'danger');
    }
  });

  // ── App title ────────────────────────────────────────────────
  document.getElementById('titleForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const alertBox = document.getElementById('titleAlert');
    try {
      await apiCall(BASE_PATH + '/api/settings.php', {
        method: 'POST',
        body: {
          action:    'update_title',
          app_title: document.getElementById('app_title').value,
        },
      });
      showAlert(alertBox, 'Gespeichert!', 'success');
      setTimeout(() => location.reload(), 800);
    } catch (err) {
      showAlert(alertBox, err.message, 'danger');
    }
  });

  function showAlert(el, msg, type) {
    el.className = `alert alert-${type} mb-3`;
    el.innerHTML = msg;
    el.classList.remove('d-none');
    if (type === 'success') {
      setTimeout(() => el.classList.add('d-none'), 3000);
    }
  }
});
