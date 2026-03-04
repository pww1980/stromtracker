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

    return [
        'year'             => $year,
        'days_elapsed'     => $daysElapsed,
        'price'            => $price,
        'consumed_ytd'     => round($consumedYTD, 2),
        'produced_ytd'     => round($producedYTD, 2),
        'total_used_ytd'   => round($consumedYTD + $producedYTD, 2),
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

    return [
        'year'           => $year,
        'month'          => $month,
        'month_name'     => monthName($month),
        'price'          => $price,
        'consumed'       => round($consumed, 2),
        'produced'       => round($produced, 2),
        'total_used'     => round($consumed + $produced, 2),
        'costs'          => round($consumed * $price, 2),
        'savings'        => round($produced * $price, 2),
        'daily_consumed' => $dailyConsumed,
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

// ─── Helpers ──────────────────────────────────────────────────────────────────

function emptyStats(): array {
    return [
        'year' => 0, 'days_elapsed' => 0, 'price' => 0,
        'consumed_ytd' => 0, 'produced_ytd' => 0, 'total_used_ytd' => 0,
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
        'costs' => 0, 'savings' => 0, 'daily_consumed' => 0, 'has_data' => false,
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
