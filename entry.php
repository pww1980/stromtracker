<?php
$pageTitle  = 'Neuer Eintrag';
$activePage = 'entry';
$extraHead  = '';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/layout.php';

// Pre-fill values from last entry
$lastReading = getLatestReading();
$todayDate   = date('Y-m-d');
$editId      = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$editData    = null;

if ($editId) {
    $stmt = getDB()->prepare("SELECT * FROM readings WHERE id = ?");
    $stmt->execute([$editId]);
    $editData   = $stmt->fetch() ?: null;
    $pageTitle  = 'Eintrag bearbeiten';
}
?>

<div class="row justify-content-center">
<div class="col-12 col-lg-8 col-xl-6">

<?php if ($editData): ?>
<div class="alert mb-3" style="background:rgba(251,191,36,.08);border:1px solid rgba(251,191,36,.2);">
  <i class="bi bi-pencil-square me-2" style="color:#fbbf24"></i>
  Eintrag vom <strong><?= date('d.m.Y', strtotime($editData['entry_date'])) ?></strong> bearbeiten
</div>
<?php endif; ?>

<div class="card">
  <div class="card-header">
    <i class="bi bi-plus-circle me-2 text-accent"></i>
    <?= $pageTitle ?>
  </div>
  <div class="card-body">

    <!-- Alert placeholder -->
    <div id="formAlert" class="d-none mb-3"></div>

    <form id="entryForm" novalidate>
      <?php if ($editId): ?>
      <input type="hidden" id="editId" value="<?= $editId ?>">
      <?php endif; ?>

      <!-- Date -->
      <div class="mb-4">
        <label class="form-label" for="entry_date">
          <i class="bi bi-calendar3 me-1"></i>Datum <span class="text-danger">*</span>
        </label>
        <input type="date" id="entry_date" class="form-control"
               value="<?= $editData ? $editData['entry_date'] : $todayDate ?>"
               max="<?= $todayDate ?>" required>
        <div class="form-text text-muted">Datum der Messung</div>
      </div>

      <hr class="divider my-4">

      <!-- Meter reading -->
      <div class="mb-4">
        <label class="form-label" for="meter_reading">
          <i class="bi bi-speedometer2 me-1 text-blue"></i>Zählerstand <span class="text-danger">*</span>
        </label>
        <div class="input-group">
          <input type="number" id="meter_reading" class="form-control" step="0.01" min="0"
                 value="<?= $editData ? $editData['meter_reading'] : '' ?>"
                 placeholder="z.B. 12450.50" required>
          <span class="input-group-text">kWh</span>
        </div>
        <?php if ($lastReading && !$editData): ?>
        <div class="form-text text-muted">
          Letzter Wert: <strong class="text-blue"><?= fmtKwh((float)$lastReading['meter_reading']) ?></strong>
          (<?= date('d.m.Y', strtotime($lastReading['entry_date'])) ?>)
        </div>
        <?php endif; ?>
        <div id="meterHint" class="form-text text-muted d-none"></div>
      </div>

      <hr class="divider my-4">

      <!-- Solar section -->
      <div class="mb-3">
        <div class="d-flex align-items-center gap-2 mb-3">
          <i class="bi bi-sun-fill text-gold fs-5"></i>
          <span class="fw-semibold">Solar-Ertrag</span>
        </div>

        <div class="mb-4">
          <label class="form-label" for="produced_ytd">
            Produzierter Strom (Jahr gesamt)
          </label>
          <div class="input-group">
            <input type="number" id="produced_ytd" class="form-control" step="0.01" min="0"
                   value="<?= $editData ? $editData['produced_ytd'] : '' ?>"
                   placeholder="z.B. 1840.00">
            <span class="input-group-text">kWh</span>
          </div>
          <div class="form-text text-muted">
            Gesamtproduktion des Jahres bis heute (aus Wechselrichter-Anzeige)
            <?php if ($lastReading && !$editData && (float)$lastReading['produced_ytd'] > 0): ?>
            – Letzter Wert: <strong class="text-gold"><?= fmtKwh((float)$lastReading['produced_ytd']) ?></strong>
            <?php endif; ?>
          </div>
        </div>

        <div class="mb-4">
          <label class="form-label" for="produced_day">
            Produzierter Strom (heute)
          </label>
          <div class="input-group">
            <input type="number" id="produced_day" class="form-control" step="0.01" min="0"
                   value="<?= $editData ? $editData['produced_day'] : '' ?>"
                   placeholder="z.B. 14.50">
            <span class="input-group-text">kWh</span>
          </div>
          <div class="form-text text-muted">Tagesproduktion der Solaranlage</div>
        </div>
      </div>

      <hr class="divider my-4">

      <!-- Notes -->
      <div class="mb-4">
        <label class="form-label" for="notes">
          <i class="bi bi-chat-text me-1"></i>Notizen
        </label>
        <textarea id="notes" class="form-control" rows="2"
                  placeholder="Optionale Bemerkungen…"><?= $editData ? htmlspecialchars($editData['notes']) : '' ?></textarea>
      </div>

      <!-- Live preview -->
      <div id="livePreview" class="d-none mb-4 p-3 rounded" style="background:rgba(102,126,234,.08);border:1px solid rgba(102,126,234,.2);">
        <div class="fw-semibold mb-2 text-accent"><i class="bi bi-calculator me-1"></i>Vorschau</div>
        <div class="row g-2 text-sm">
          <div class="col-6">
            <span class="text-muted">Verbrauch seit letztem Eintrag:</span><br>
            <strong id="prevConsumed" class="text-blue fw-num"></strong>
          </div>
          <div class="col-6">
            <span class="text-muted">Kosten (geschätzt):</span><br>
            <strong id="prevCosts" class="text-red fw-num"></strong>
          </div>
        </div>
      </div>

      <!-- Submit -->
      <div class="d-flex gap-3">
        <button type="submit" class="btn btn-primary flex-fill py-2" id="submitBtn">
          <span id="btnText">
            <i class="bi bi-check-circle me-2"></i><?= $editData ? 'Speichern' : 'Eintrag hinzufügen' ?>
          </span>
          <span id="btnSpin" class="d-none">
            <span class="spinner-border spinner-border-sm me-2"></span>Speichern…
          </span>
        </button>
        <a href="<?= BASE_PATH ?>/dashboard.php" class="btn btn-outline-secondary px-4">Abbrechen</a>
      </div>

    </form>
  </div>
</div>

</div>
</div>

<?php
$lastMeter = $lastReading ? (float)$lastReading['meter_reading'] : 0;
$lastDate  = $lastReading ? $lastReading['entry_date'] : '';
$curPrice  = getCurrentPrice();

$bp = BASE_PATH;
$extraScripts = <<<HTML
<script>
const LAST_METER = $lastMeter;
const LAST_DATE  = "$lastDate";
const CUR_PRICE  = $curPrice;
</script>
<script src="{$bp}/assets/js/entry.js"></script>
HTML;

require_once __DIR__ . '/includes/layout_end.php';
?>
