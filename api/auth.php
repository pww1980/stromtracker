<?php
require_once __DIR__ . '/../includes/auth.php';

$method = $_SERVER['REQUEST_METHOD'];
$input  = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? $_GET['action'] ?? '';

header('Content-Type: application/json; charset=utf-8');

if ($action === 'login') {
    $password = $input['password'] ?? '';
    if (login($password)) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(401);
        echo json_encode(['error' => 'Falsches Passwort']);
    }
} elseif ($action === 'logout') {
    logout();
    echo json_encode(['success' => true]);
} elseif ($action === 'check') {
    echo json_encode(['logged_in' => isLoggedIn()]);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Unbekannte Aktion']);
}
