<?php
$pageTitle  = 'Dashboard';
$activePage = 'dashboard';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/layout.php';

$currentContractYear = getCurrentContractYear();
$availYears          = getAvailableContractYears();
if (empty($availYears)) $availYears = [$currentContractYear];
$selectedYear = isset($_GET['year']) ? (int)$_GET['year'] : $currentContractYear;
if (!in_array($selectedYear, $availYears)) $selectedYear = $availYears[0];

$period   = getContractPeriod($selectedYear);
$stats    = calcContractStats($selectedYear);
$months   = calcAllContractMonthStats($selectedYear);
$forecast = ($selectedYear === $currentContractYear) ? calcCurrentMonthForecast() : ['has_data' => false];

// YTD progress for the selected period
$ytdPct        = $period['days_total'] > 0
    ? min(100, round($stats['days_elapsed'] / $period['days_total'] * 100, 1))
    : 0;
$periodStartFmt = $period['start']->format('d.m.Y');
$periodEndFmt   = $period['end']->format('d.m.Y');
$isActivePeriod = ($selectedYear === $currentContractYear);
?>

<!-- Year tabs -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
  <div class="year-tabs" id="yearTabs">
    <?php foreach ($availYears as $yr): ?>
    <button class="year-tab <?= $yr == $selectedYear ? 'active' : '' ?>"
            data-year="<?= $yr ?>">
      <?= getContractPeriod($yr)['label'] ?>
    </button>
    <?php endforeach; ?>
  </div>
  <a href="<?= BASE_PATH ?>/entry.php" class="btn btn-primary btn-sm">
    <i class="bi bi-plus-lg me-1"></i>Neuer Eintrag
  </a>
</div>

<?php if ($stats['days_elapsed'] > 0 || $stats['consumed_ytd'] > 0): ?>
<!-- ── YTD Progress bar ──────────────────────────────────────── -->
<div class="card mb-4 px-3 py-3">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
    <div>
      <span class="fw-semibold">Vertragsjahr <?= $period['label'] ?></span>
      <span class="text-muted ms-2 small"><?= $periodStartFmt ?> – <?= $periodEndFmt ?></span>
    </div>
    <div class="text-end">
      <?php if ($isActivePeriod): ?>
        <span class="badge" style="background:rgba(102,126,234,.15);color:#818cf8">
          Laufend · Tag <?= $stats['days_elapsed'] ?> von <?= $period['days_total'] ?>
        </span>
      <?php else: ?>
        <span class="badge" style="background:rgba(100,116,139,.15);color:#94a3b8">
          Abgeschlossen · <?= $period['days_total'] ?> Tage
        </span>
      <?php endif; ?>
      <span class="fw-num ms-2 fw-semibold" style="color:#818cf8"><?= $ytdPct ?> %</span>
    </div>
  </div>
  <div class="progress" style="height:8px;background:rgba(255,255,255,.06);border-radius:4px">
    <div class="progress-bar" role="progressbar"
         style="width:<?= $ytdPct ?>%;background:linear-gradient(90deg,#667eea,#764ba2);border-radius:4px"
         aria-valuenow="<?= $ytdPct ?>" aria-valuemin="0" aria-valuemax="100"></div>
  </div>
  <div class="d-flex justify-content-between mt-1" style="font-size:.72rem;color:var(--text-muted)">
    <span><?= $periodStartFmt ?></span>
    <?php if ($isActivePeriod): ?>
      <span style="position:relative;left:<?= ($ytdPct - 50) ?>%">Heute</span>
    <?php endif; ?>
    <span><?= $periodEndFmt ?></span>
  </div>
</div>
<?php endif; ?>

<?php if ($stats['days_elapsed'] === 0 && $stats['consumed_ytd'] == 0): ?>
<div class="alert" style="background:rgba(102,126,234,.1);border:1px solid rgba(102,126,234,.3);">
  <i class="bi bi-info-circle me-2 text-accent"></i>
  Noch keine Daten für <?= $period['label'] ?>. <a href="<?= BASE_PATH ?>/entry.php" class="fw-semibold">Ersten Eintrag anlegen →</a>
</div>
<?php else: ?>

<!-- ── YTD Stats ────────────────────────────────────────────── -->
<div class="section-title">Jahresübersicht <?= $period['label'] ?> · <?= $stats['days_elapsed'] ?> Tage</div>
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

  <div class="col-6 col-md-4 col-xl-3">
    <?php
    $aq = $stats['autarkie_ytd'];
    $aqColor = $aq >= 60 ? '#4ade80' : ($aq >= 30 ? '#fbbf24' : '#60a5fa');
    $aqBg    = $aq >= 60 ? 'rgba(74,222,128,.12)' : ($aq >= 30 ? 'rgba(251,191,36,.12)' : 'rgba(96,165,250,.12)');
    ?>
    <div class="stat-card">
      <div class="stat-icon" style="background:<?= $aqBg ?>;color:<?= $aqColor ?>">
        <i class="bi bi-house-check"></i>
      </div>
      <div class="stat-label">Solar-Autarkie YTD</div>
      <div class="stat-value fw-num" style="color:<?= $aqColor ?>">
        <?= $aq > 0 ? fmtNum($aq, 1) . ' %' : '–' ?>
        <?php if ($stats['overprod_ytd']): ?><i class="bi bi-lightning-charge-fill text-warning ms-1" title="Einspeisung ins Netz" style="font-size:.9rem"></i><?php endif; ?>
      </div>
      <div class="stat-sub">
        <?php if ($stats['overprod_ytd']): ?>
          Solar &gt; Netzbezug · Einspeisung
        <?php else: ?>
          Solar-Anteil am Gesamtverbrauch
        <?php endif; ?>
      </div>
      <?php if ($aq > 0): ?>
      <div class="mt-2" style="background:rgba(255,255,255,.08);border-radius:4px;height:4px;overflow:hidden">
        <div style="width:<?= min(100, $aq) ?>%;height:100%;background:<?= $aqColor ?>;border-radius:4px;transition:width .4s"></div>
      </div>
      <?php endif; ?>
    </div>
  </div>

</div>

<?php if ($forecast['has_data']): ?>
<!-- ── Current-month forecast ────────────────────────────────── -->
<div class="section-title">
  Prognose <?= $forecast['month_name'] ?>
  <span class="text-muted fw-normal" style="font-size:.8rem">
    · <?= $forecast['days_elapsed'] ?> von <?= $forecast['days_in_month'] ?> Tagen
  </span>
</div>
<div class="row g-3 mb-4">

  <div class="col-6 col-md-3">
    <div class="stat-card stat-blue">
      <div class="stat-icon"><i class="bi bi-speedometer2"></i></div>
      <div class="stat-label">Strom Prognose (Monat)</div>
      <div class="stat-value fw-num"><?= fmtNum($forecast['forecast_consumed'], 1) ?></div>
      <div class="stat-sub">kWh · bisher <?= fmtNum($forecast['consumed_so_far'], 1) ?> kWh</div>
    </div>
  </div>

  <div class="col-6 col-md-3">
    <div class="stat-card stat-gold">
      <div class="stat-icon"><i class="bi bi-sun-fill"></i></div>
      <div class="stat-label">Solar Prognose (Monat)</div>
      <div class="stat-value fw-num"><?= fmtNum($forecast['forecast_produced'], 1) ?></div>
      <div class="stat-sub">kWh · bisher <?= fmtNum($forecast['produced_so_far'], 1) ?> kWh</div>
    </div>
  </div>

  <div class="col-6 col-md-3">
    <div class="stat-card stat-red">
      <div class="stat-icon"><i class="bi bi-receipt"></i></div>
      <div class="stat-label">Kosten Prognose (Monat)</div>
      <div class="stat-value fw-num"><?= fmtEur($forecast['forecast_costs']) ?></div>
      <div class="stat-sub">@ <?= fmtNum($forecast['price'], 4) ?> €/kWh</div>
    </div>
  </div>

  <div class="col-6 col-md-3">
    <div class="stat-card stat-green">
      <div class="stat-icon"><i class="bi bi-piggy-bank"></i></div>
      <div class="stat-label">Ersparnis Prognose (Monat)</div>
      <div class="stat-value fw-num"><?= fmtEur($forecast['forecast_savings']) ?></div>
      <div class="stat-sub">€ durch Solar</div>
    </div>
  </div>

</div>
<?php endif; ?>

<!-- ── Projections ──────────────────────────────────────────── -->
<div class="section-title">Hochrechnung auf Gesamtjahr <?= $period['label'] ?></div>
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
        <span><i class="bi bi-bar-chart-line me-2 text-accent"></i>Monatlicher Verlauf <?= $period['label'] ?></span>
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
          <th class="text-end">Ø pro Tag</th>
          <th class="text-end">Solar</th>
          <th class="text-end">Autarkie</th>
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
          <td class="text-end fw-num text-muted"><?= $m['daily_consumed'] > 0 ? fmtNum($m['daily_consumed']) . ' kWh' : '–' ?></td>
          <td class="text-end fw-num"><span class="badge-solar"><?= fmtNum($m['produced']) ?> kWh</span></td>
          <td class="text-end fw-num">
            <?php if ($m['autarkie'] > 0): ?>
            <?php
              $ac = $m['autarkie'];
              $acCol = $ac >= 60 ? '#4ade80' : ($ac >= 30 ? '#fbbf24' : '#60a5fa');
            ?>
            <span style="color:<?= $acCol ?>;font-variant-numeric:tabular-nums"><?= fmtNum($ac, 1) ?> %</span>
            <?php if ($m['overprod']): ?><i class="bi bi-lightning-charge-fill text-warning ms-1" style="font-size:.75rem" title="Einspeisung ins Netz"></i><?php endif; ?>
            <?php else: ?>
            <span class="text-muted">–</span>
            <?php endif; ?>
          </td>
          <td class="text-end fw-num"><?= fmtNum($m['total_used']) ?> kWh</td>
          <td class="text-end fw-num text-red"><?= fmtEur($m['costs']) ?></td>
          <td class="text-end fw-num text-green"><?= fmtEur($m['savings']) ?></td>
          <?php else: ?>
          <td colspan="7" class="no-data text-center">Keine Daten</td>
          <?php endif; ?>
        </tr>
        <?php endforeach; ?>

        <!-- Totals row -->
        <?php
        $totConsumed     = array_sum(array_column($months, 'consumed'));
        $totProduced     = array_sum(array_column($months, 'produced'));
        $totCosts        = array_sum(array_column($months, 'costs'));
        $totSavings      = array_sum(array_column($months, 'savings'));
        $withDaily       = array_values(array_filter($months, fn($m) => $m['daily_consumed'] > 0));
        $avgDaily        = count($withDaily) > 0
            ? array_sum(array_column($withDaily, 'daily_consumed')) / count($withDaily)
            : 0;
        $totAQ           = ($totConsumed + $totProduced) > 0
            ? round($totProduced / ($totConsumed + $totProduced) * 100, 1)
            : 0.0;
        $totOverprod     = $totProduced > $totConsumed;
        $totAQColor      = $totAQ >= 60 ? '#4ade80' : ($totAQ >= 30 ? '#fbbf24' : '#60a5fa');
        ?>
        <tr style="border-top:2px solid var(--border-col);background:rgba(255,255,255,.03)">
          <td class="fw-bold">Gesamt</td>
          <td class="text-end fw-bold fw-num"><?= fmtNum($totConsumed) ?> kWh</td>
          <td class="text-end fw-num text-muted"><?= $avgDaily > 0 ? '⌀ ' . fmtNum($avgDaily) . ' kWh' : '–' ?></td>
          <td class="text-end fw-bold fw-num"><?= fmtNum($totProduced) ?> kWh</td>
          <td class="text-end fw-bold fw-num">
            <?php if ($totAQ > 0): ?>
            <span style="color:<?= $totAQColor ?>"><?= fmtNum($totAQ, 1) ?> %</span>
            <?php if ($totOverprod): ?><i class="bi bi-lightning-charge-fill text-warning ms-1" style="font-size:.75rem"></i><?php endif; ?>
            <?php else: ?>–<?php endif; ?>
          </td>
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
$chartData   = json_encode([
    'labels'   => array_column($months, 'month_name'),
    'consumed' => array_column($months, 'consumed'),
    'produced' => array_column($months, 'produced'),
]);
$donutData   = json_encode([
    'consumed' => $stats['consumed_ytd'],
    'produced' => $stats['produced_ytd'],
]);
$bp = BASE_PATH;
$extraScripts = <<<HTML
<script src="{$bp}/assets/js/dashboard.js"></script>
<script>
  initDashboard($chartData, $donutData, {selectedYear: $selectedYear});
</script>
HTML;

require_once __DIR__ . '/includes/layout_end.php';
?>
