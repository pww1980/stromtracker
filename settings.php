<?php
$pageTitle  = 'Einstellungen';
$activePage = 'settings';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/layout.php';

$prices   = getAllPrices();
$curPrice = getCurrentPrice();
?>

<div class="row g-4">

  <!-- ── Electricity prices ───────────────────────────────────── -->
  <div class="col-12 col-lg-6">
    <div class="card h-100">
      <div class="card-header">
        <i class="bi bi-currency-euro me-2 text-accent"></i>Strompreise
      </div>
      <div class="card-body">
        <p class="text-muted small mb-3">
          Füge neue Preise mit Gültigkeitsdatum hinzu. Der Preis wird automatisch dem richtigen Zeitraum zugeordnet.
        </p>

        <!-- Current price badge -->
        <div class="mb-4 p-3 rounded" style="background:rgba(74,222,128,.08);border:1px solid rgba(74,222,128,.2);">
          <div class="text-muted small">Aktueller Preis</div>
          <div class="fw-bold fs-4 text-green fw-num"><?= number_format($curPrice, 4, ',', '.') ?> €/kWh</div>
        </div>

        <!-- Add price form -->
        <form id="addPriceForm" class="mb-4">
          <div class="row g-2">
            <div class="col-12 col-sm-4">
              <label class="form-label">Gültig ab</label>
              <input type="date" id="price_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="col-12 col-sm-4">
              <label class="form-label">Preis (€/kWh)</label>
              <input type="number" id="price_kwh" class="form-control" step="0.0001" min="0.001"
                     placeholder="0.3200" required>
            </div>
            <div class="col-12 col-sm-4">
              <label class="form-label">Notiz</label>
              <input type="text" id="price_note" class="form-control" placeholder="Optional">
            </div>
          </div>
          <button type="submit" class="btn btn-primary btn-sm mt-2">
            <i class="bi bi-plus-circle me-1"></i>Preis hinzufügen
          </button>
        </form>

        <!-- Price history table -->
        <div class="table-responsive">
          <table class="table table-sm mb-0" id="priceTable">
            <thead>
              <tr>
                <th>Gültig ab</th>
                <th class="text-end">Preis</th>
                <th class="text-muted">Notiz</th>
                <th></th>
              </tr>
            </thead>
            <tbody id="priceTableBody">
              <?php foreach ($prices as $p): ?>
              <tr data-id="<?= $p['id'] ?>">
                <td><?= date('d.m.Y', strtotime($p['valid_from'])) ?></td>
                <td class="text-end fw-num text-green"><?= number_format((float)$p['price_kwh'], 4, ',', '.') ?> €</td>
                <td class="text-muted small"><?= htmlspecialchars($p['note'] ?? '') ?></td>
                <td class="text-end">
                  <button class="btn btn-sm btn-outline-danger delete-price" data-id="<?= $p['id'] ?>">
                    <i class="bi bi-trash"></i>
                  </button>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- ── Password change ──────────────────────────────────────── -->
  <div class="col-12 col-lg-6">
    <div class="card mb-4">
      <div class="card-header"><i class="bi bi-lock me-2 text-accent"></i>Passwort ändern</div>
      <div class="card-body">
        <div id="pwAlert" class="d-none mb-3"></div>
        <form id="pwForm" novalidate>
          <div class="mb-3">
            <label class="form-label">Aktuelles Passwort</label>
            <input type="password" id="current_pw" class="form-control" required autocomplete="current-password">
          </div>
          <div class="mb-3">
            <label class="form-label">Neues Passwort</label>
            <input type="password" id="new_pw" class="form-control" minlength="6" required autocomplete="new-password">
          </div>
          <div class="mb-4">
            <label class="form-label">Neues Passwort bestätigen</label>
            <input type="password" id="confirm_pw" class="form-control" required autocomplete="new-password">
          </div>
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle me-2"></i>Passwort speichern
          </button>
        </form>
      </div>
    </div>

    <!-- ── App title ──────────────────────────────────────────── -->
    <div class="card">
      <div class="card-header"><i class="bi bi-gear me-2 text-accent"></i>Allgemein</div>
      <div class="card-body">
        <div id="titleAlert" class="d-none mb-3"></div>
        <form id="titleForm">
          <div class="mb-3">
            <label class="form-label">App-Titel</label>
            <input type="text" id="app_title" class="form-control"
                   value="<?= htmlspecialchars(getSetting('app_title', 'StromTracker')) ?>">
            <div class="form-text text-muted">Wird im Seitentitel und Navigation angezeigt</div>
          </div>
          <button type="submit" class="btn btn-primary btn-sm">
            <i class="bi bi-save me-1"></i>Speichern
          </button>
        </form>
      </div>
    </div>
  </div>

  <!-- ── Database info ─────────────────────────────────────────── -->
  <div class="col-12">
    <div class="card">
      <div class="card-header"><i class="bi bi-info-circle me-2 text-accent"></i>Systeminfo</div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-auto">
            <div class="text-muted small">PHP Version</div>
            <div class="fw-semibold"><?= PHP_VERSION ?></div>
          </div>
          <div class="col-auto">
            <div class="text-muted small">Einträge gesamt</div>
            <div class="fw-semibold">
              <?= (int)getDB()->query("SELECT COUNT(*) FROM readings")->fetchColumn() ?>
            </div>
          </div>
          <div class="col-auto">
            <div class="text-muted small">Jahre mit Daten</div>
            <div class="fw-semibold"><?= count(getAvailableYears()) ?></div>
          </div>
          <div class="col-auto">
            <div class="text-muted small">Version</div>
            <div class="fw-semibold"><?= APP_VERSION ?></div>
          </div>
        </div>
      </div>
    </div>
  </div>

</div>

<?php
$bp = BASE_PATH;
$extraScripts = "<script src=\"{$bp}/assets/js/settings.js\"></script>";
require_once __DIR__ . '/includes/layout_end.php';
?>
