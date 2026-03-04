<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$action = $_GET['action'] ?? 'year';
$year   = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

switch ($action) {
    case 'year':
        jsonResponse(calcYearStats($year));
        break;

    case 'months':
        jsonResponse(calcAllMonthStats($year));
        break;

    case 'chart_year':
        jsonResponse(getChartDataForYear($year));
        break;

    case 'multi_year':
        jsonResponse(getMultiYearChartData());
        break;

    case 'years':
        jsonResponse(getAvailableYears());
        break;

    default:
        jsonResponse(['error' => 'Unbekannte Aktion'], 400);
}
