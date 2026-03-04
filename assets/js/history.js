/* ═══════════════════════════════════════════════════════════════
   StromTracker – History JS
   ═══════════════════════════════════════════════════════════════ */

document.addEventListener('DOMContentLoaded', () => {
  let deleteId = null;
  const modal  = new bootstrap.Modal(document.getElementById('deleteModal'));

  // Year filter tabs
  document.querySelectorAll('.year-tab').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.year-tab').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');

      const year = btn.dataset.year;
      filterTable(year);
    });
  });

  function filterTable(year) {
    const rows   = document.querySelectorAll('#tableBody tr[data-year]');
    let visible  = 0;
    rows.forEach(row => {
      const show = year === 'all' || row.dataset.year === year;
      row.style.display = show ? '' : 'none';
      if (show) visible++;
    });
    document.getElementById('rowCount').textContent = visible + ' Einträge';
  }

  // Delete buttons
  document.querySelectorAll('.delete-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      deleteId = btn.dataset.id;
      document.getElementById('deleteDate').textContent = btn.dataset.date;
      modal.show();
    });
  });

  // Confirm delete
  document.getElementById('confirmDelete')?.addEventListener('click', async () => {
    if (!deleteId) return;
    try {
      await apiCall(`/api/entries.php?id=${deleteId}`, { method: 'DELETE' });
      modal.hide();
      showToast('Eintrag gelöscht', 'success');
      // Remove row from DOM
      const row = document.querySelector(`.delete-btn[data-id="${deleteId}"]`)?.closest('tr');
      row?.remove();
      // Update count
      const active = document.querySelector('.year-tab.active')?.dataset.year || 'all';
      filterTable(active);
    } catch (err) {
      showToast(err.message, 'error');
    }
  });
});
