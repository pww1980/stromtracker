<?php
require_once __DIR__ . '/db.php';

// ─── Price helpers ────────────────────────────────────────────────────────────

function getCurrentPrice(): float {
    $stmt = getDB()->prepare(
        "SELECT price_kwh FROM electricity_prices
         WHERE valid_from <= CURDATE()
         ORDER BY valid_from DESC LIMIT 1"
    );
    $stmt->execute();
    $row = $stmt->fetch();
    return $row ? (float)$row['price_kwh'] : 0.32;
}

function getPriceForDate(string $date): float {
    $stmt = getDB()->prepare(
        "SELECT price_kwh FROM electricity_prices
         WHERE valid_from <= ?
         ORDER BY valid_from DESC LIMIT 1"
    );
    $stmt->execute([$date]);
    $row = $stmt->fetch();
    return $row ? (float)$row['price_kwh'] : 0.32;
}

function getAllPrices(): array {
    $stmt = getDB()->query(
        "SELECT * FROM electricity_prices ORDER BY valid_from DESC"
    );
    return $stmt->fetchAll();
}

// ─── Reading helpers ──────────────────────────────────────────────────────────

function getLatestReading(?int $year = null): ?array {
    if ($year) {
        $stmt = getDB()->prepare(
            "SELECT * FROM readings
             WHERE YEAR(entry_date) = ?
             ORDER BY entry_date DESC LIMIT 1"
        );
        $stmt->execute([$year]);
    } else {
        $stmt = getDB()->query(
            "SELECT * FROM readings ORDER BY entry_date DESC LIMIT 1"
        );
    }
    return $stmt->fetch() ?: null;
}

function getFirstReadingOfYear(int $year): ?array {
    // Get first reading on or after Jan 1 of given year
    $stmt = getDB()->prepare(
        "SELECT * FROM readings
         WHERE YEAR(entry_date) = ?
         ORDER BY entry_date ASC LIMIT 1"
    );
    $stmt->execute([$year]);
    return $stmt->fetch() ?: null;
}

function getLastReadingBeforeYear(int $year): ?array {
    $stmt = getDB()->prepare(
        "SELECT * FROM readings
         WHERE entry_date < ?
         ORDER BY entry_date DESC LIMIT 1"
    );
    $stmt->execute([$year . '-01-01']);
    return $stmt->fetch() ?: null;
}

function getReadingsForYear(int $year): array {
    $stmt = getDB()->prepare(
        "SELECT * FROM readings
         WHERE YEAR(entry_date) = ?
         ORDER BY entry_date ASC"
    );
    $stmt->execute([$year]);
    return $stmt->fetchAll();
}

function getAllReadings(): array {
    $stmt = getDB()->query(
        "SELECT * FROM readings ORDER BY entry_date DESC"
    );
    return $stmt->fetchAll();
}

function getAvailableYears(): array {
    $stmt = getDB()->query(
        "SELECT DISTINCT YEAR(entry_date) AS yr FROM readings ORDER BY yr DESC"
    );
    return array_column($stmt->fetchAll(), 'yr');
}

// ─── Contract-year helpers ────────────────────────────────────────────────────

/** Month (1–12) the billing/contract year starts. DB setting takes priority over config.php. */
function contractStartMonth(): int {
    $fromDB = getSetting('contract_start_month', '');
    if ($fromDB !== '') return max(1, min(12, (int)$fromDB));
    return defined('CONTRACT_START_MONTH') ? (int)CONTRACT_START_MONTH : 1;
}

/**
 * Return the label and date range for a given contract year.
 *
 * With CONTRACT_START_MONTH=2, contractYear 2025 covers 2025-02-01 – 2026-01-31
 * and has label "2025/26".  With start=1 it equals the calendar year.
 */
function getContractPeriod(int $contractYear): array {
    $sm    = contractStartMonth();
    $start = new DateTime(sprintf('%04d-%02d-01', $contractYear, $sm));
    $end   = (clone $start)->modify('+12 months')->modify('-1 day');
    $label = ($sm === 1)
        ? (string)$contractYear
        : $contractYear . '/' . substr((string)($contractYear + 1), -2);
    return [
        'start'      => $start,
        'end'        => $end,
        'label'      => $label,
        'days_total' => (int)$start->diff($end)->days + 1,
    ];
}

/** Contract year that contains today. */
function getCurrentContractYear(): int {
    $sm = contractStartMonth();
    $m  = (int)date('n');
    $y  = (int)date('Y');
    return ($m >= $sm) ? $y : $y - 1;
}

function getReadingsForPeriod(string $from, string $to): array {
    $stmt = getDB()->prepare(
        "SELECT * FROM readings
         WHERE entry_date >= ? AND entry_date <= ?
         ORDER BY entry_date ASC"
    );
    $stmt->execute([$from, $to]);
    return $stmt->fetchAll();
}

function getLastReadingBefore(string $date): ?array {
    $stmt = getDB()->prepare(
        "SELECT * FROM readings
         WHERE entry_date < ?
         ORDER BY entry_date DESC LIMIT 1"
    );
    $stmt->execute([$date]);
    return $stmt->fetch() ?: null;
}

/** Available contract years derived from the min/max reading dates. */
function getAvailableContractYears(): array {
    $sm = contractStartMonth();
    if ($sm === 1) return getAvailableYears();

    $stmt = getDB()->query(
        "SELECT MIN(entry_date) AS min_d, MAX(entry_date) AS max_d FROM readings"
    );
    $row = $stmt->fetch();
    if (!$row || !$row['min_d']) return [];

    $minDate  = new DateTime($row['min_d']);
    $maxDate  = new DateTime($row['max_d']);

    // Contract year that contains $minDate
    $minY  = (int)$minDate->format('Y');
    $minM  = (int)$minDate->format('n');
    $first = ($minM >= $sm) ? $minY : $minY - 1;

    // Contract year that contains $maxDate
    $maxY = (int)$maxDate->format('Y');
    $maxM = (int)$maxDate->format('n');
    $last = ($maxM >= $sm) ? $maxY : $maxY - 1;

    $years = [];
    for ($cy = $last; $cy >= $first; $cy--) {
        $years[] = $cy;
    }
    return $years;
}

// ─── Statistics calculation ───────────────────────────────────────────────────

function calcYearStats(int $year): array {
    $readings = getReadingsForYear($year);
    if (empty($readings)) {
        return emptyStats();
    }

    $now       = new DateTime();
    $isCurrentYear = ($year === (int)$now->format('Y'));
    $refDate   = $isCurrentYear ? $now : new DateTime("{$year}-12-31");

    // Days elapsed in the year up to refDate
    $yearStart = new DateTime("{$year}-01-01");
    $daysElapsed = (int)$yearStart->diff($refDate)->days + 1;

    $firstReading = $readings[0];
    $lastReading  = end($readings);

    // For meter consumption YTD we need the reading just before the year starts
    $prevYearReading = getLastReadingBeforeYear($year);

    $meterStart   = $prevYearReading
        ? (float)$prevYearReading['meter_reading']
        : (float)$firstReading['meter_reading'];

    $meterEnd     = (float)$lastReading['meter_reading'];
    $consumedYTD  = max(0, $meterEnd - $meterStart);   // Verbrauch laut Zähler YTD
    $producedYTD  = (float)$lastReading['produced_ytd']; // Solar YTD

    $price = getPriceForDate($lastReading['entry_date']);

    // Entry days span (for per-day calculations, use actual elapsed days)
    $entryDays = $daysElapsed;

    $dailyConsumed  = $entryDays > 0 ? $consumedYTD  / $entryDays : 0;
    $dailyProduced  = $entryDays > 0 ? $producedYTD  / $entryDays : 0;
    $dailyTotal     = $dailyConsumed + $dailyProduced;

    $costsYTD       = $consumedYTD  * $price;
    $savingsYTD     = $producedYTD  * $price;

    $projConsumed   = $dailyConsumed  * 365;
    $projProduced   = $dailyProduced  * 365;
    $projCosts      = $projConsumed   * $price;
    $projSavings    = $projProduced   * $price;

    $totalYTD       = $consumedYTD + $producedYTD;
    $autarkieYTD    = $totalYTD > 0 ? round($producedYTD / $totalYTD * 100, 1) : 0.0;
    $overprodYTD    = $producedYTD > $consumedYTD;

    return [
        'year'             => $year,
        'days_elapsed'     => $daysElapsed,
        'price'            => $price,
        'consumed_ytd'     => round($consumedYTD, 2),
        'produced_ytd'     => round($producedYTD, 2),
        'total_used_ytd'   => round($totalYTD, 2),
        'autarkie_ytd'     => $autarkieYTD,
        'overprod_ytd'     => $overprodYTD,
        'daily_consumed'   => round($dailyConsumed, 3),
        'daily_produced'   => round($dailyProduced, 3),
        'daily_total'      => round($dailyTotal, 3),
        'costs_ytd'        => round($costsYTD, 2),
        'savings_ytd'      => round($savingsYTD, 2),
        'proj_consumed'    => round($projConsumed, 0),
        'proj_produced'    => round($projProduced, 0),
        'proj_costs'       => round($projCosts, 2),
        'proj_savings'     => round($projSavings, 2),
        'meter_start'      => round($meterStart, 2),
        'meter_end'        => round($meterEnd, 2),
    ];
}

function calcMonthStats(int $year, int $month): array {
    $price = getCurrentPrice();

    // Get all readings for the month plus bracketing readings
    $monthStart = sprintf('%04d-%02d-01', $year, $month);
    $monthEnd   = date('Y-m-t', strtotime($monthStart));

    // Last reading before or on the last day of the month
    $stmt = getDB()->prepare(
        "SELECT * FROM readings WHERE entry_date <= ? ORDER BY entry_date DESC LIMIT 1"
    );
    $stmt->execute([$monthEnd]);
    $endReading = $stmt->fetch() ?: null;

    // Last reading before this month
    $stmt = getDB()->prepare(
        "SELECT * FROM readings WHERE entry_date < ? ORDER BY entry_date DESC LIMIT 1"
    );
    $stmt->execute([$monthStart]);
    $startReading = $stmt->fetch() ?: null;

    if (!$endReading) {
        return emptyMonthStats($year, $month);
    }

    // Meter start: last reading before month, or first reading OF the month if none exists before
    if ($startReading) {
        $startForCalc = $startReading;
    } else {
        // No reading before this month – use the first reading within the month
        $stmt = getDB()->prepare(
            "SELECT * FROM readings WHERE entry_date >= ? AND entry_date <= ? ORDER BY entry_date ASC LIMIT 1"
        );
        $stmt->execute([$monthStart, $monthEnd]);
        $startForCalc = $stmt->fetch() ?: null;
    }
    $meterStart = $startForCalc ? (float)$startForCalc['meter_reading'] : (float)$endReading['meter_reading'];
    $meterEnd   = (float)$endReading['meter_reading'];
    $consumed   = max(0, $meterEnd - $meterStart);

    // Produced for this month: difference in ytd values
    // But ytd resets at year start, so only valid within same year
    $producedStart = 0;
    if ($startForCalc && (int)date('Y', strtotime($startForCalc['entry_date'])) === $year) {
        $producedStart = (float)$startForCalc['produced_ytd'];
    }
    // If endReading is not in this year, produced = 0
    $producedEnd = ((int)date('Y', strtotime($endReading['entry_date'])) === $year)
        ? (float)$endReading['produced_ytd']
        : 0;

    $produced = max(0, $producedEnd - $producedStart);

    // Daily average: span between the two bracketing readings
    $spanDays      = 1;
    if ($startForCalc) {
        $d1       = new DateTime($startForCalc['entry_date']);
        $d2       = new DateTime($endReading['entry_date']);
        $spanDays = max(1, (int)$d1->diff($d2)->days);
    }
    $dailyConsumed = $consumed > 0 ? round($consumed / $spanDays, 2) : 0;

    $totalMonth = $consumed + $produced;
    $autarkie   = $totalMonth > 0 ? round($produced / $totalMonth * 100, 1) : 0.0;
    $overprod   = $produced > $consumed; // solar exceeded grid draw → likely feed-in

    return [
        'year'           => $year,
        'month'          => $month,
        'month_name'     => monthName($month),
        'price'          => $price,
        'consumed'       => round($consumed, 2),
        'produced'       => round($produced, 2),
        'total_used'     => round($totalMonth, 2),
        'costs'          => round($consumed * $price, 2),
        'savings'        => round($produced * $price, 2),
        'daily_consumed' => $dailyConsumed,
        'autarkie'       => $autarkie,
        'overprod'       => $overprod,
        'has_data'       => true,
    ];
}

function calcAllMonthStats(int $year): array {
    $months = [];
    $currentYear  = (int)date('Y');
    $currentMonth = (int)date('m');
    $maxMonth = ($year < $currentYear) ? 12 : $currentMonth;

    for ($m = 1; $m <= $maxMonth; $m++) {
        $months[] = calcMonthStats($year, $m);
    }
    return $months;
}

/**
 * Like calcYearStats but respects the configured contract period.
 * produced_ytd resets each calendar year, so we sum across the year boundary.
 */
function calcContractStats(int $contractYear): array {
    $period = getContractPeriod($contractYear);
    $from   = $period['start']->format('Y-m-d');
    $to     = $period['end']->format('Y-m-d');

    $now             = new DateTime();
    $isCurrentPeriod = ($now >= $period['start'] && $now <= $period['end']);
    $refDate         = $isCurrentPeriod ? $now : $period['end'];
    $effectiveTo     = min($refDate->format('Y-m-d'), $to);

    $readings = getReadingsForPeriod($from, $effectiveTo);
    if (empty($readings)) return emptyStats();

    $daysElapsed  = (int)$period['start']->diff($refDate)->days + 1;
    $prevReading  = getLastReadingBefore($from);
    $firstReading = $readings[0];
    $lastReading  = end($readings);

    // Meter reading is absolute (never resets)
    $meterStart  = $prevReading
        ? (float)$prevReading['meter_reading']
        : (float)$firstReading['meter_reading'];
    $meterEnd    = (float)$lastReading['meter_reading'];
    $consumedYTD = max(0, $meterEnd - $meterStart);

    // produced_ytd resets on Jan 1 each calendar year
    $sm = contractStartMonth();
    if ($sm === 1) {
        $baseline    = $prevReading ? (float)$prevReading['produced_ytd'] : 0.0;
        $producedYTD = max(0, (float)$lastReading['produced_ytd'] - $baseline);
    } else {
        // Contract spans two calendar years: sum each year's portion separately
        $startYear = (int)$period['start']->format('Y');
        $endYear   = (int)$period['end']->format('Y');

        // Year-1 portion: contractStart → Dec 31 of startYear
        $baseline1   = $prevReading ? (float)$prevReading['produced_ytd'] : 0.0;
        $lastOfYear1 = null;
        foreach ($readings as $r) {
            if ((int)substr($r['entry_date'], 0, 4) === $startYear) {
                $lastOfYear1 = $r;
            }
        }
        $solar1 = $lastOfYear1
            ? max(0, (float)$lastOfYear1['produced_ytd'] - $baseline1)
            : 0.0;

        // Year-2 portion: Jan 1 of endYear → contractEnd (ytd resets to 0 on Jan 1)
        $solar2 = 0.0;
        foreach ($readings as $r) {
            if ((int)substr($r['entry_date'], 0, 4) === $endYear) {
                $solar2 = (float)$r['produced_ytd'];
            }
        }

        $producedYTD = $solar1 + $solar2;
    }

    $price         = getPriceForDate($lastReading['entry_date']);
    $dailyConsumed = $daysElapsed > 0 ? $consumedYTD  / $daysElapsed : 0;
    $dailyProduced = $daysElapsed > 0 ? $producedYTD  / $daysElapsed : 0;
    $dailyTotal    = $dailyConsumed + $dailyProduced;
    $costsYTD      = $consumedYTD  * $price;
    $savingsYTD    = $producedYTD  * $price;
    $projConsumed  = $dailyConsumed  * $period['days_total'];
    $projProduced  = $dailyProduced  * $period['days_total'];
    $totalYTD      = $consumedYTD + $producedYTD;
    $autarkie      = $totalYTD > 0 ? round($producedYTD / $totalYTD * 100, 1) : 0.0;

    return [
        'year'           => $contractYear,
        'period_label'   => $period['label'],
        'days_elapsed'   => $daysElapsed,
        'price'          => $price,
        'consumed_ytd'   => round($consumedYTD, 2),
        'produced_ytd'   => round($producedYTD, 2),
        'total_used_ytd' => round($totalYTD, 2),
        'autarkie_ytd'   => $autarkie,
        'overprod_ytd'   => $producedYTD > $consumedYTD,
        'daily_consumed' => round($dailyConsumed, 3),
        'daily_produced' => round($dailyProduced, 3),
        'daily_total'    => round($dailyTotal, 3),
        'costs_ytd'      => round($costsYTD, 2),
        'savings_ytd'    => round($savingsYTD, 2),
        'proj_consumed'  => round($projConsumed, 0),
        'proj_produced'  => round($projProduced, 0),
        'proj_costs'     => round($projConsumed * $price, 2),
        'proj_savings'   => round($projProduced * $price, 2),
        'meter_start'    => round($meterStart, 2),
        'meter_end'      => round($meterEnd, 2),
    ];
}

/** Months of a contract period in contract order (e.g. Feb … Jan). */
function calcAllContractMonthStats(int $contractYear): array {
    $period = getContractPeriod($contractYear);
    $now    = new DateTime();
    $months = [];
    $cur    = clone $period['start'];

    while ($cur <= $period['end'] && $cur <= $now) {
        $months[] = calcMonthStats((int)$cur->format('Y'), (int)$cur->format('n'));
        $cur->modify('+1 month');
    }
    return $months;
}

function getChartDataForYear(int $year): array {
    $months     = calcAllMonthStats($year);
    $labels     = array_column($months, 'month_name');
    $consumed   = array_column($months, 'consumed');
    $produced   = array_column($months, 'produced');

    return compact('labels', 'consumed', 'produced');
}

function getMultiYearChartData(): array {
    $years = getAvailableYears();
    $datasets = [];
    foreach ($years as $year) {
        $stats = calcYearStats((int)$year);
        $datasets[] = [
            'year'          => $year,
            'consumed_ytd'  => $stats['consumed_ytd'],
            'produced_ytd'  => $stats['produced_ytd'],
            'costs_ytd'     => $stats['costs_ytd'],
            'savings_ytd'   => $stats['savings_ytd'],
        ];
    }
    return $datasets;
}

function calcCurrentMonthForecast(): array {
    $now         = new DateTime();
    $year        = (int)$now->format('Y');
    $month       = (int)$now->format('n');
    $dayOfMonth  = (int)$now->format('j');
    $daysInMonth = (int)$now->format('t');

    if ($dayOfMonth < 2) {
        return ['has_data' => false]; // zu wenig Daten für sinnvolle Prognose
    }

    $m = calcMonthStats($year, $month);
    if (!$m['has_data'] || ($m['consumed'] == 0 && $m['produced'] == 0)) {
        return ['has_data' => false];
    }

    $price     = $m['price'];
    $fConsumed = round($m['consumed'] / $dayOfMonth * $daysInMonth, 1);
    $fProduced = round($m['produced'] / $dayOfMonth * $daysInMonth, 1);

    return [
        'has_data'          => true,
        'month_name'        => monthName($month),
        'days_in_month'     => $daysInMonth,
        'days_elapsed'      => $dayOfMonth,
        'consumed_so_far'   => $m['consumed'],
        'produced_so_far'   => $m['produced'],
        'forecast_consumed' => $fConsumed,
        'forecast_produced' => $fProduced,
        'forecast_costs'    => round($fConsumed * $price, 2),
        'forecast_savings'  => round($fProduced * $price, 2),
        'price'             => $price,
    ];
}

// ─── Helpers ──────────────────────────────────────────────────────────────────

function emptyStats(): array {
    return [
        'year' => 0, 'days_elapsed' => 0, 'price' => 0,
        'consumed_ytd' => 0, 'produced_ytd' => 0, 'total_used_ytd' => 0,
        'autarkie_ytd' => 0.0, 'overprod_ytd' => false,
        'daily_consumed' => 0, 'daily_produced' => 0, 'daily_total' => 0,
        'costs_ytd' => 0, 'savings_ytd' => 0,
        'proj_consumed' => 0, 'proj_produced' => 0,
        'proj_costs' => 0, 'proj_savings' => 0,
        'meter_start' => 0, 'meter_end' => 0,
    ];
}

function emptyMonthStats(int $year, int $month): array {
    return [
        'year' => $year, 'month' => $month,
        'month_name' => monthName($month), 'price' => 0,
        'consumed' => 0, 'produced' => 0, 'total_used' => 0,
        'costs' => 0, 'savings' => 0, 'daily_consumed' => 0,
        'autarkie' => 0.0, 'overprod' => false, 'has_data' => false,
    ];
}

function monthName(int $month): string {
    $names = ['Jan','Feb','Mär','Apr','Mai','Jun','Jul','Aug','Sep','Okt','Nov','Dez'];
    return $names[$month - 1] ?? '';
}

function fmtKwh(float $val): string {
    return number_format($val, 2, ',', '.') . ' kWh';
}

function fmtEur(float $val): string {
    return number_format($val, 2, ',', '.') . ' €';
}

function fmtNum(float $val, int $dec = 2): string {
    return number_format($val, $dec, ',', '.');
}

function jsonResponse(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
