<?php
$pageTitle  = 'Dashboard';
$activePage = 'dashboard';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/layout.php';

$currentYear  = (int)date('Y');
$availYears   = getAvailableYears();
if (empty($availYears)) $availYears = [$currentYear];
$selectedYear = isset($_GET['year']) ? (int)$_GET['year'] : $currentYear;
if (!in_array($selectedYear, $availYears)) $selectedYear = $availYears[0];

$stats  = calcYearStats($selectedYear);
$months = calcAllMonthStats($selectedYear);
?>

<!-- Year tabs -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
  <div class="year-tabs" id="yearTabs">
    <?php foreach ($availYears as $yr): ?>
    <button class="year-tab <?= $yr == $selectedYear ? 'active' : '' ?>"
            data-year="<?= $yr ?>">
      <?= $yr ?>
    </button>
    <?php endforeach; ?>
  </div>
  <a href="/entry.php" class="btn btn-primary btn-sm">
    <i class="bi bi-plus-lg me-1"></i>Neuer Eintrag
  </a>
</div>

<?php if ($stats['days_elapsed'] === 0 && $stats['consumed_ytd'] == 0): ?>
<div class="alert" style="background:rgba(102,126,234,.1);border:1px solid rgba(102,126,234,.3);">
  <i class="bi bi-info-circle me-2 text-accent"></i>
  Noch keine Daten für <?= $selectedYear ?>. <a href="/entry.php" class="fw-semibold">Ersten Eintrag anlegen →</a>
</div>
<?php else: ?>

<!-- ── YTD Stats ────────────────────────────────────────────── -->
<div class="section-title">Jahresübersicht <?= $selectedYear ?> · <?= $stats['days_elapsed'] ?> Tage</div>
<div class="row g-3 mb-4" id="statsGrid">

  <div class="col-6 col-md-4 col-xl-3">
    <div class="stat-card stat-blue">
      <div class="stat-icon"><i class="bi bi-speedometer2"></i></div>
      <div class="stat-label">Verbrauch laut Zähler YTD</div>
      <div class="stat-value fw-num"><?= fmtNum($stats['consumed_ytd']) ?></div>
      <div class="stat-sub">kWh</div>
    </div>
  </div>

  <div class="col-6 col-md-4 col-xl-3">
    <div class="stat-card stat-gold">
      <div class="stat-icon"><i class="bi bi-sun-fill"></i></div>
      <div class="stat-label">Produzierter Strom YTD</div>
      <div class="stat-value fw-num"><?= fmtNum($stats['produced_ytd']) ?></div>
      <div class="stat-sub">kWh</div>
    </div>
  </div>

  <div class="col-6 col-md-4 col-xl-3">
    <div class="stat-card stat-purple">
      <div class="stat-icon"><i class="bi bi-lightning-charge"></i></div>
      <div class="stat-label">Gesamtverbrauch YTD</div>
      <div class="stat-value fw-num"><?= fmtNum($stats['total_used_ytd']) ?></div>
      <div class="stat-sub">kWh (Zähler + Solar)</div>
    </div>
  </div>

  <div class="col-6 col-md-4 col-xl-3">
    <div class="stat-card stat-red">
      <div class="stat-icon"><i class="bi bi-currency-euro"></i></div>
      <div class="stat-label">Kosten YTD</div>
      <div class="stat-value fw-num"><?= fmtNum($stats['costs_ytd']) ?></div>
      <div class="stat-sub">@ <?= fmtNum($stats['price'], 4) ?> €/kWh</div>
    </div>
  </div>

  <div class="col-6 col-md-4 col-xl-3">
    <div class="stat-card stat-green">
      <div class="stat-icon"><i class="bi bi-piggy-bank"></i></div>
      <div class="stat-label">Ersparnis YTD</div>
      <div class="stat-value fw-num"><?= fmtNum($stats['savings_ytd']) ?></div>
      <div class="stat-sub">€ durch Solar</div>
    </div>
  </div>

  <div class="col-6 col-md-4 col-xl-3">
    <div class="stat-card">
      <div class="stat-icon" style="background:rgba(167,139,250,.12);color:#a78bfa"><i class="bi bi-graph-up"></i></div>
      <div class="stat-label">Ø Verbrauch / Tag</div>
      <div class="stat-value fw-num"><?= fmtNum($stats['daily_consumed'], 3) ?></div>
      <div class="stat-sub">kWh (laut Zähler)</div>
    </div>
  </div>

  <div class="col-6 col-md-4 col-xl-3">
    <div class="stat-card">
      <div class="stat-icon" style="background:rgba(251,191,36,.12);color:#fbbf24"><i class="bi bi-brightness-high"></i></div>
      <div class="stat-label">Ø Solar / Tag</div>
      <div class="stat-value fw-num"><?= fmtNum($stats['daily_produced'], 3) ?></div>
      <div class="stat-sub">kWh</div>
    </div>
  </div>

  <div class="col-6 col-md-4 col-xl-3">
    <div class="stat-card">
      <div class="stat-icon" style="background:rgba(52,211,153,.12);color:#34d399"><i class="bi bi-calculator"></i></div>
      <div class="stat-label">Ø Gesamtverbrauch / Tag</div>
      <div class="stat-value fw-num"><?= fmtNum($stats['daily_total'], 3) ?></div>
      <div class="stat-sub">kWh</div>
    </div>
  </div>

</div>

<!-- ── Projections ──────────────────────────────────────────── -->
<div class="section-title">Hochrechnung auf Gesamtjahr</div>
<div class="row g-3 mb-4">

  <div class="col-6 col-md-3">
    <div class="stat-card stat-blue">
      <div class="stat-icon"><i class="bi bi-graph-up-arrow"></i></div>
      <div class="stat-label">Stromverbrauch (Hochrechnung)</div>
      <div class="stat-value fw-num"><?= fmtNum($stats['proj_consumed'], 0) ?></div>
      <div class="stat-sub">kWh / Jahr</div>
    </div>
  </div>

  <div class="col-6 col-md-3">
    <div class="stat-card stat-gold">
      <div class="stat-icon"><i class="bi bi-sun"></i></div>
      <div class="stat-label">Solar (Hochrechnung)</div>
      <div class="stat-value fw-num"><?= fmtNum($stats['proj_produced'], 0) ?></div>
      <div class="stat-sub">kWh / Jahr</div>
    </div>
  </div>

  <div class="col-6 col-md-3">
    <div class="stat-card stat-red">
      <div class="stat-icon"><i class="bi bi-receipt"></i></div>
      <div class="stat-label">Kosten (Hochrechnung)</div>
      <div class="stat-value fw-num"><?= fmtNum($stats['proj_costs']) ?></div>
      <div class="stat-sub">€ / Jahr</div>
    </div>
  </div>

  <div class="col-6 col-md-3">
    <div class="stat-card stat-green">
      <div class="stat-icon"><i class="bi bi-piggy-bank-fill"></i></div>
      <div class="stat-label">Ersparnis (Hochrechnung)</div>
      <div class="stat-value fw-num"><?= fmtNum($stats['proj_savings']) ?></div>
      <div class="stat-sub">€ / Jahr</div>
    </div>
  </div>

</div>

<!-- ── Charts ───────────────────────────────────────────────── -->
<div class="row g-3 mb-4">
  <div class="col-12 col-xl-8">
    <div class="card h-100">
      <div class="card-header d-flex align-items-center justify-content-between">
        <span><i class="bi bi-bar-chart-line me-2 text-accent"></i>Monatlicher Verlauf <?= $selectedYear ?></span>
        <div class="d-flex gap-2">
          <button class="btn btn-sm btn-outline-secondary" id="toggleChartType">
            <i class="bi bi-bar-chart-fill me-1"></i>Balken
          </button>
        </div>
      </div>
      <div class="card-body">
        <div class="chart-wrapper">
          <canvas id="monthlyChart"></canvas>
        </div>
      </div>
    </div>
  </div>

  <div class="col-12 col-xl-4">
    <div class="card h-100">
      <div class="card-header"><i class="bi bi-pie-chart me-2 text-accent"></i>Verteilung YTD</div>
      <div class="card-body d-flex flex-column align-items-center justify-content-center">
        <div class="chart-wrapper" style="height:220px;width:100%">
          <canvas id="donutChart"></canvas>
        </div>
        <div class="d-flex gap-4 mt-2">
          <div class="text-center">
            <div style="color:#60a5fa;font-size:.75rem">Netz</div>
            <div class="fw-bold fw-num"><?= fmtNum($stats['consumed_ytd'], 0) ?> kWh</div>
          </div>
          <div class="text-center">
            <div style="color:#ffd700;font-size:.75rem">Solar</div>
            <div class="fw-bold fw-num"><?= fmtNum($stats['produced_ytd'], 0) ?> kWh</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ── Monthly table ─────────────────────────────────────────── -->
<div class="card mb-4">
  <div class="card-header"><i class="bi bi-calendar3 me-2 text-accent"></i>Monatsauswertung <?= $selectedYear ?></div>
  <div class="table-responsive">
    <table class="table table-hover month-table mb-0">
      <thead>
        <tr>
          <th>Monat</th>
          <th class="text-end">Verbrauch (Zähler)</th>
          <th class="text-end">Solar</th>
          <th class="text-end">Gesamt</th>
          <th class="text-end">Kosten</th>
          <th class="text-end">Ersparnis</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($months as $m): ?>
        <tr>
          <td class="fw-semibold"><?= $m['month_name'] ?></td>
          <?php if ($m['has_data'] && ($m['consumed'] > 0 || $m['produced'] > 0)): ?>
          <td class="text-end fw-num"><span class="badge-kwh"><?= fmtNum($m['consumed']) ?> kWh</span></td>
          <td class="text-end fw-num"><span class="badge-solar"><?= fmtNum($m['produced']) ?> kWh</span></td>
          <td class="text-end fw-num"><?= fmtNum($m['total_used']) ?> kWh</td>
          <td class="text-end fw-num text-red"><?= fmtEur($m['costs']) ?></td>
          <td class="text-end fw-num text-green"><?= fmtEur($m['savings']) ?></td>
          <?php else: ?>
          <td colspan="5" class="no-data text-center">Keine Daten</td>
          <?php endif; ?>
        </tr>
        <?php endforeach; ?>

        <!-- Totals row -->
        <?php
        $totConsumed  = array_sum(array_column($months, 'consumed'));
        $totProduced  = array_sum(array_column($months, 'produced'));
        $totCosts     = array_sum(array_column($months, 'costs'));
        $totSavings   = array_sum(array_column($months, 'savings'));
        ?>
        <tr style="border-top:2px solid var(--border-col);background:rgba(255,255,255,.03)">
          <td class="fw-bold">Gesamt</td>
          <td class="text-end fw-bold fw-num"><?= fmtNum($totConsumed) ?> kWh</td>
          <td class="text-end fw-bold fw-num"><?= fmtNum($totProduced) ?> kWh</td>
          <td class="text-end fw-bold fw-num"><?= fmtNum($totConsumed + $totProduced) ?> kWh</td>
          <td class="text-end fw-bold fw-num text-red"><?= fmtEur($totCosts) ?></td>
          <td class="text-end fw-bold fw-num text-green"><?= fmtEur($totSavings) ?></td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<?php endif; // has data ?>

<?php
$chartData   = json_encode(getChartDataForYear($selectedYear));
$donutData   = json_encode([
    'consumed' => $stats['consumed_ytd'],
    'produced' => $stats['produced_ytd'],
]);
$extraScripts = <<<HTML
<script src="/assets/js/dashboard.js"></script>
<script>
  initDashboard($chartData, $donutData, {selectedYear: $selectedYear});
</script>
HTML;

require_once __DIR__ . '/includes/layout_end.php';
?>
