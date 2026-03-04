/* ═══════════════════════════════════════════════════════════════
   StromTracker – Entry Form JS
   ═══════════════════════════════════════════════════════════════ */

document.addEventListener('DOMContentLoaded', () => {
  const form       = document.getElementById('entryForm');
  const editId     = document.getElementById('editId')?.value;
  const meterInput = document.getElementById('meter_reading');
  const btnText    = document.getElementById('btnText');
  const btnSpin    = document.getElementById('btnSpin');
  const submitBtn  = document.getElementById('submitBtn');
  const alertBox   = document.getElementById('formAlert');
  const preview    = document.getElementById('livePreview');

  // Live preview when meter value changes
  meterInput?.addEventListener('input', updatePreview);

  function updatePreview() {
    const val = parseFloat(meterInput.value);
    if (!val || !LAST_METER || val <= LAST_METER) {
      preview?.classList.add('d-none');
      return;
    }
    const diff  = val - LAST_METER;
    const costs = diff * CUR_PRICE;

    document.getElementById('prevConsumed').textContent = fmtKwh(diff);
    document.getElementById('prevCosts').textContent    = fmtEur(costs);
    preview?.classList.remove('d-none');
  }

  // Show meter hint
  meterInput?.addEventListener('blur', () => {
    const val = parseFloat(meterInput.value);
    const hint = document.getElementById('meterHint');
    if (!hint) return;
    if (LAST_METER && val < LAST_METER && val > 0) {
      hint.textContent = `⚠ Wert muss größer als ${fmtKwh(LAST_METER)} sein`;
      hint.classList.remove('d-none');
      hint.style.color = '#f87171';
    } else {
      hint.classList.add('d-none');
    }
  });

  // Form submit
  form?.addEventListener('submit', async (e) => {
    e.preventDefault();
    setLoading(true);
    showAlert('', '');

    const payload = {
      entry_date:   document.getElementById('entry_date').value,
      meter_reading: parseFloat(document.getElementById('meter_reading').value),
      produced_ytd:  parseFloat(document.getElementById('produced_ytd').value) || 0,
      produced_day:  parseFloat(document.getElementById('produced_day').value) || 0,
      notes:         document.getElementById('notes').value.trim(),
    };

    try {
      let url    = '/api/entries.php';
      let method = 'POST';

      if (editId) {
        url    = `/api/entries.php?id=${editId}`;
        method = 'PUT';
      }

      await apiCall(url, { method, body: payload });
      showToast(editId ? 'Eintrag aktualisiert!' : 'Eintrag gespeichert!', 'success');

      // Redirect to dashboard after brief delay
      setTimeout(() => { window.location.href = '/dashboard.php'; }, 800);
    } catch (err) {
      showAlert(err.message, 'danger');
      setLoading(false);
    }
  });

  function setLoading(on) {
    btnText.classList.toggle('d-none', on);
    btnSpin.classList.toggle('d-none', !on);
    submitBtn.disabled = on;
  }

  function showAlert(msg, type) {
    if (!msg) {
      alertBox.classList.add('d-none');
      return;
    }
    alertBox.className = `alert alert-${type} mb-3`;
    alertBox.innerHTML = `<i class="bi bi-exclamation-triangle me-2"></i>${escHtml(msg)}`;
  }
});
