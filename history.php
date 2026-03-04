<?php
$pageTitle  = 'Datenverlauf';
$activePage = 'history';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/layout.php';

$allReadings = getAllReadings(); // newest first
$years = getAvailableYears();
$selectedYear = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
?>

<div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-4">
  <div class="year-tabs">
    <button class="year-tab <?= empty($_GET['year']) ? 'active' : '' ?>" data-year="all">Alle</button>
    <?php foreach ($years as $yr): ?>
    <button class="year-tab <?= (isset($_GET['year']) && $_GET['year'] == $yr) ? 'active' : '' ?>"
            data-year="<?= $yr ?>"><?= $yr ?></button>
    <?php endforeach; ?>
  </div>
  <a href="<?= BASE_PATH ?>/entry.php" class="btn btn-primary btn-sm">
    <i class="bi bi-plus-lg me-1"></i>Neuer Eintrag
  </a>
</div>

<div class="card">
  <div class="card-header d-flex align-items-center justify-content-between">
    <span><i class="bi bi-table me-2 text-accent"></i>Einträge</span>
    <span class="text-muted small" id="rowCount"><?= count($allReadings) ?> Einträge</span>
  </div>
  <div class="table-responsive">
    <table class="table table-hover mb-0" id="historyTable">
      <thead>
        <tr>
          <th>Datum</th>
          <th class="text-end">Zählerstand</th>
          <th class="text-end">Verbrauch</th>
          <th class="text-end">Solar YTD</th>
          <th class="text-end">Solar Tag</th>
          <th class="text-muted">Notizen</th>
          <th></th>
        </tr>
      </thead>
      <tbody id="tableBody">
        <?php
        $prev = null;
        $rows = $allReadings; // newest first
        // For consumption calc, we need ordered by date asc, then reverse
        usort($rows, fn($a,$b) => strcmp($b['entry_date'], $a['entry_date'])); // newest first
        // Build lookup for previous readings
        $sorted = array_reverse($rows); // oldest first
        $prevMap = [];
        foreach ($sorted as $i => $r) {
            $prevMap[$r['id']] = $i > 0 ? $sorted[$i-1] : null;
        }
        foreach ($rows as $r):
            $prevR = $prevMap[$r['id']];
            $consumed = null;
            if ($prevR) {
                $diff = (float)$r['meter_reading'] - (float)$prevR['meter_reading'];
                $consumed = $diff >= 0 ? $diff : null;
            }
        ?>
        <tr data-year="<?= date('Y', strtotime($r['entry_date'])) ?>">
          <td class="fw-semibold"><?= date('d.m.Y', strtotime($r['entry_date'])) ?></td>
          <td class="text-end fw-num"><?= fmtNum((float)$r['meter_reading']) ?> kWh</td>
          <td class="text-end fw-num">
            <?php if ($consumed !== null): ?>
            <span class="badge-kwh"><?= fmtNum($consumed) ?> kWh</span>
            <?php else: ?>
            <span class="text-muted">–</span>
            <?php endif; ?>
          </td>
          <td class="text-end fw-num">
            <?php if ((float)$r['produced_ytd'] > 0): ?>
            <span class="badge-solar"><?= fmtNum((float)$r['produced_ytd']) ?> kWh</span>
            <?php else: ?>
            <span class="text-muted">–</span>
            <?php endif; ?>
          </td>
          <td class="text-end fw-num">
            <?php if ((float)$r['produced_day'] > 0): ?>
            <?= fmtNum((float)$r['produced_day']) ?> kWh
            <?php else: ?>
            <span class="text-muted">–</span>
            <?php endif; ?>
          </td>
          <td class="text-muted small" style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
            <?= $r['notes'] ? htmlspecialchars($r['notes']) : '' ?>
          </td>
          <td class="text-end" style="white-space:nowrap">
            <a href="<?= BASE_PATH ?>/entry.php?edit=<?= $r['id'] ?>" class="btn btn-sm btn-outline-secondary me-1" title="Bearbeiten">
              <i class="bi bi-pencil"></i>
            </a>
            <button class="btn btn-sm btn-outline-danger delete-btn" data-id="<?= $r['id'] ?>"
                    data-date="<?= date('d.m.Y', strtotime($r['entry_date'])) ?>" title="Löschen">
              <i class="bi bi-trash"></i>
            </button>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($rows)): ?>
        <tr>
          <td colspan="7" class="text-center text-muted py-4">
            Noch keine Einträge. <a href="<?= BASE_PATH ?>/entry.php">Ersten Eintrag anlegen →</a>
          </td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Delete confirmation modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content" style="background:var(--bg-card);border:1px solid var(--border-col);">
      <div class="modal-header border-0">
        <h6 class="modal-title text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Eintrag löschen?</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body pt-0 text-muted">
        Eintrag vom <strong id="deleteDate" class="text-white"></strong> wird unwiderruflich gelöscht.
      </div>
      <div class="modal-footer border-0 gap-2">
        <button class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Abbrechen</button>
        <button class="btn btn-danger btn-sm" id="confirmDelete">Löschen</button>
      </div>
    </div>
  </div>
</div>

<?php
$bp = BASE_PATH;
$extraScripts = "<script src=\"{$bp}/assets/js/history.js\"></script>";
require_once __DIR__ . '/includes/layout_end.php';
?>
