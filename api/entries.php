<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$method = $_SERVER['REQUEST_METHOD'];
$input  = json_decode(file_get_contents('php://input'), true) ?? [];

switch ($method) {
    case 'GET':
        handleGet();
        break;
    case 'POST':
        handlePost($input);
        break;
    case 'PUT':
        handlePut($input);
        break;
    case 'DELETE':
        handleDelete();
        break;
    default:
        jsonResponse(['error' => 'Method not allowed'], 405);
}

function handleGet(): void {
    $id   = isset($_GET['id']) ? (int)$_GET['id'] : null;
    $year = isset($_GET['year']) ? (int)$_GET['year'] : null;

    if ($id) {
        $stmt = getDB()->prepare("SELECT * FROM readings WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        jsonResponse($row ?: [], $row ? 200 : 404);
    } elseif ($year) {
        jsonResponse(getReadingsForYear($year));
    } else {
        jsonResponse(getAllReadings());
    }
}

function handlePost(array $data): void {
    $required = ['entry_date', 'meter_reading'];
    foreach ($required as $field) {
        if (!isset($data[$field]) || $data[$field] === '') {
            jsonResponse(['error' => "Feld '{$field}' ist erforderlich"], 400);
        }
    }

    // Validate date
    $date = $data['entry_date'];
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !strtotime($date)) {
        jsonResponse(['error' => 'Ungültiges Datum'], 400);
    }

    $meter       = (float)$data['meter_reading'];
    $prodYtd     = isset($data['produced_ytd'])  ? (float)$data['produced_ytd']  : 0;
    $prodDay     = isset($data['produced_day'])  ? (float)$data['produced_day']  : 0;
    $notes       = trim($data['notes'] ?? '');

    // Note: meter may legitimately run backwards during solar overproduction (net metering).
    // No ascending validation – a lower reading than the previous is allowed.

    try {
        $stmt = getDB()->prepare(
            "INSERT INTO readings (entry_date, meter_reading, produced_ytd, produced_day, notes)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
               meter_reading = VALUES(meter_reading),
               produced_ytd  = VALUES(produced_ytd),
               produced_day  = VALUES(produced_day),
               notes         = VALUES(notes)"
        );
        $stmt->execute([$date, $meter, $prodYtd, $prodDay, $notes]);
        $id = getDB()->lastInsertId() ?: getIdByDate($date);
        jsonResponse(['success' => true, 'id' => $id]);
    } catch (PDOException $e) {
        jsonResponse(['error' => 'Datenbankfehler: ' . $e->getMessage()], 500);
    }
}

function handlePut(array $data): void {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) {
        jsonResponse(['error' => 'ID fehlt'], 400);
    }

    $fields  = [];
    $params  = [];
    $allowed = ['entry_date', 'meter_reading', 'produced_ytd', 'produced_day', 'notes'];

    foreach ($allowed as $field) {
        if (array_key_exists($field, $data)) {
            $fields[] = "{$field} = ?";
            $params[] = $data[$field];
        }
    }

    if (empty($fields)) {
        jsonResponse(['error' => 'Keine Felder zum Aktualisieren'], 400);
    }

    $params[] = $id;
    $stmt = getDB()->prepare("UPDATE readings SET " . implode(', ', $fields) . " WHERE id = ?");
    $stmt->execute($params);
    jsonResponse(['success' => true]);
}

function handleDelete(): void {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) {
        jsonResponse(['error' => 'ID fehlt'], 400);
    }
    $stmt = getDB()->prepare("DELETE FROM readings WHERE id = ?");
    $stmt->execute([$id]);
    jsonResponse(['success' => true]);
}

function getIdByDate(string $date): int {
    $stmt = getDB()->prepare("SELECT id FROM readings WHERE entry_date = ?");
    $stmt->execute([$date]);
    return (int)($stmt->fetchColumn() ?: 0);
}
