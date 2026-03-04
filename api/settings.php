<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$method = $_SERVER['REQUEST_METHOD'];
$input  = json_decode(file_get_contents('php://input'), true) ?? [];

if ($method === 'GET') {
    $action = $_GET['action'] ?? 'all';
    if ($action === 'prices') {
        jsonResponse(getAllPrices());
    } else {
        jsonResponse([
            'current_price' => getCurrentPrice(),
            'prices'        => getAllPrices(),
            'app_title'     => getSetting('app_title', 'StromTracker'),
            'currency'      => getSetting('currency', 'EUR'),
        ]);
    }
} elseif ($method === 'POST') {
    $action = $input['action'] ?? '';

    if ($action === 'add_price') {
        $date  = trim($input['valid_from'] ?? '');
        $price = (float)($input['price_kwh'] ?? 0);
        $note  = trim($input['note'] ?? '');

        if (!$date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            jsonResponse(['error' => 'Ungültiges Datum'], 400);
        }
        if ($price <= 0) {
            jsonResponse(['error' => 'Preis muss größer als 0 sein'], 400);
        }

        $stmt = getDB()->prepare(
            "INSERT INTO electricity_prices (valid_from, price_kwh, note)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE price_kwh = VALUES(price_kwh), note = VALUES(note)"
        );
        $stmt->execute([$date, $price, $note]);
        jsonResponse(['success' => true]);

    } elseif ($action === 'delete_price') {
        $id = (int)($input['id'] ?? 0);
        // Don't allow deleting the last price
        $count = (int)getDB()->query("SELECT COUNT(*) FROM electricity_prices")->fetchColumn();
        if ($count <= 1) {
            jsonResponse(['error' => 'Mindestens ein Strompreis muss vorhanden sein'], 400);
        }
        $stmt = getDB()->prepare("DELETE FROM electricity_prices WHERE id = ?");
        $stmt->execute([$id]);
        jsonResponse(['success' => true]);

    } elseif ($action === 'change_password') {
        $current = $input['current_password'] ?? '';
        $new     = $input['new_password'] ?? '';
        $confirm = $input['confirm_password'] ?? '';

        $hash = getSetting('password_hash');
        if (!password_verify($current, $hash)) {
            jsonResponse(['error' => 'Aktuelles Passwort falsch'], 400);
        }
        if (strlen($new) < 6) {
            jsonResponse(['error' => 'Neues Passwort muss mindestens 6 Zeichen haben'], 400);
        }
        if ($new !== $confirm) {
            jsonResponse(['error' => 'Passwörter stimmen nicht überein'], 400);
        }
        setSetting('password_hash', password_hash($new, PASSWORD_BCRYPT));
        jsonResponse(['success' => true]);

    } elseif ($action === 'update_title') {
        $title = trim($input['app_title'] ?? '');
        if ($title) {
            setSetting('app_title', htmlspecialchars($title, ENT_QUOTES));
        }
        jsonResponse(['success' => true]);

    } elseif ($action === 'update_contract_month') {
        $month = (int)($input['contract_start_month'] ?? 0);
        if ($month < 1 || $month > 12) {
            jsonResponse(['error' => 'Ungültiger Monat'], 400);
        }
        setSetting('contract_start_month', (string)$month);
        jsonResponse(['success' => true]);

    } else {
        jsonResponse(['error' => 'Unbekannte Aktion'], 400);
    }
} else {
    jsonResponse(['error' => 'Method not allowed'], 405);
}
