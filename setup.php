<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

$error   = '';
$success = '';
$step    = 'check';

// Try DB connection
try {
    $db = getDB();
    $step = 'configure';

    // Check if already installed
    try {
        $hash = getSetting('password_hash');
        if (!empty($hash) && $hash !== '$2y$12$placeholder_replace_on_install') {
            $step = 'done';
        }
    } catch (Exception $e) {
        $step = 'install';
    }
} catch (Exception $e) {
    $error = 'Datenbankverbindung fehlgeschlagen: ' . $e->getMessage();
    $step  = 'db_error';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step !== 'done') {
    $action = $_POST['action'] ?? '';

    if ($action === 'install') {
        try {
            // Create database using configured DB_NAME (not the hardcoded name in SQL file)
            $db->exec(
                "CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "`
                 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
            );
            $db->exec("USE `" . DB_NAME . "`");

            $sql = file_get_contents(__DIR__ . '/install.sql');
            // Split by ; and execute each statement.
            // Strip leading comment/blank lines from each chunk to get the
            // actual SQL verb, then skip CREATE DATABASE and USE statements
            // (those are handled above with the configured DB_NAME).
            $statements = array_filter(
                array_map('trim', explode(';', $sql)),
                function (string $s): bool {
                    // Strip leading blank lines and SQL comment lines
                    $effective = '';
                    foreach (explode("\n", $s) as $line) {
                        $t = ltrim($line);
                        if ($effective === '' && ($t === '' || substr($t, 0, 2) === '--')) {
                            continue;
                        }
                        $effective .= $line . "\n";
                    }
                    $effective = trim($effective);
                    if ($effective === '') return false;
                    $upper = strtoupper($effective);
                    if (substr($upper, 0, 15) === 'CREATE DATABASE') return false;
                    if (substr($upper, 0, 4) === 'USE ')            return false;
                    return true;
                }
            );
            foreach ($statements as $stmt) {
                if (trim($stmt)) {
                    $db->exec($stmt);
                }
            }
            $step = 'set_password';
        } catch (Exception $e) {
            $error = 'Installation fehlgeschlagen: ' . $e->getMessage();
        }
    } elseif ($action === 'set_password') {
        $pw  = $_POST['password'] ?? '';
        $pw2 = $_POST['password2'] ?? '';
        $price = (float)($_POST['price'] ?? 0.32);

        if (strlen($pw) < 6) {
            $error = 'Passwort muss mindestens 6 Zeichen haben.';
        } elseif ($pw !== $pw2) {
            $error = 'Passwörter stimmen nicht überein.';
        } elseif ($price <= 0) {
            $error = 'Strompreis muss größer als 0 sein.';
        } else {
            try {
                $hash = password_hash($pw, PASSWORD_BCRYPT);
                setSetting('password_hash', $hash);
                // Update or insert initial price
                $pstmt = $db->prepare(
                    "INSERT INTO electricity_prices (valid_from, price_kwh, note)
                     VALUES (?, ?, 'Initialer Strompreis')
                     ON DUPLICATE KEY UPDATE price_kwh = VALUES(price_kwh)"
                );
                $pstmt->execute([date('Y') . '-01-01', $price]);
                $success = 'Installation erfolgreich! Du kannst dich jetzt anmelden.';
                $step    = 'done';
            } catch (Exception $e) {
                $error = 'Fehler beim Speichern: ' . $e->getMessage();
            }
        }
    }
}

if ($step === 'configure') {
    // Tables might not exist yet
    try {
        $db->query("SELECT 1 FROM settings LIMIT 1");
    } catch (Exception $e) {
        $step = 'install';
    }
}
?><!DOCTYPE html>
<html lang="de" data-bs-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>StromTracker – Setup</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
  body { background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%); min-height: 100vh; }
  .setup-card { max-width: 500px; border: 1px solid rgba(255,255,255,.1); background: rgba(255,255,255,.05); backdrop-filter: blur(10px); }
  .setup-card .card-header { background: rgba(255,255,255,.05); border-bottom: 1px solid rgba(255,255,255,.1); }
  .text-accent { color: #ffd700; }
  .btn-primary { background: linear-gradient(135deg, #667eea, #764ba2); border: none; }
  .btn-primary:hover { background: linear-gradient(135deg, #764ba2, #667eea); }
  .form-control, .form-select { background: rgba(255,255,255,.1); border-color: rgba(255,255,255,.2); color: #fff; }
  .form-control:focus { background: rgba(255,255,255,.15); border-color: #667eea; color: #fff; box-shadow: 0 0 0 .25rem rgba(102,126,234,.25); }
  .form-control::placeholder { color: rgba(255,255,255,.4); }
</style>
</head>
<body class="d-flex align-items-center justify-content-center p-3">
<div class="setup-card card text-white shadow-lg w-100 mx-auto">
  <div class="card-header py-3 text-center">
    <h4 class="mb-0"><i class="bi bi-lightning-charge-fill text-accent me-2"></i>StromTracker Setup</h4>
  </div>
  <div class="card-body p-4">

    <?php if ($error): ?>
    <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($step === 'db_error'): ?>
      <p class="text-danger">Bitte prüfe die Datenbankverbindung in <code>includes/config.php</code>.</p>
      <hr class="border-secondary">
      <h6>Konfiguration:</h6>
      <ul class="small text-muted">
        <li>Host: <code><?= DB_HOST ?>:<?= DB_PORT ?></code></li>
        <li>Datenbank: <code><?= DB_NAME ?></code></li>
        <li>Benutzer: <code><?= DB_USER ?></code></li>
      </ul>

    <?php elseif ($step === 'install'): ?>
      <p>Datenbank ist erreichbar. Die Tabellen müssen jetzt erstellt werden.</p>
      <form method="post">
        <input type="hidden" name="action" value="install">
        <button class="btn btn-primary w-100">
          <i class="bi bi-database me-2"></i>Tabellen erstellen
        </button>
      </form>

    <?php elseif ($step === 'set_password' || ($step === 'configure' && empty($success))): ?>
      <p>Fast fertig! Lege dein Passwort und den aktuellen Strompreis fest.</p>
      <form method="post">
        <input type="hidden" name="action" value="set_password">
        <div class="mb-3">
          <label class="form-label">Passwort</label>
          <input type="password" name="password" class="form-control" required minlength="6" placeholder="Mindestens 6 Zeichen">
        </div>
        <div class="mb-3">
          <label class="form-label">Passwort bestätigen</label>
          <input type="password" name="password2" class="form-control" required placeholder="Passwort wiederholen">
        </div>
        <div class="mb-4">
          <label class="form-label">Aktueller Strompreis (€/kWh)</label>
          <input type="number" name="price" class="form-control" value="0.32" step="0.0001" min="0.01" required>
        </div>
        <button class="btn btn-primary w-100">
          <i class="bi bi-check-circle me-2"></i>Einrichtung abschließen
        </button>
      </form>

    <?php elseif ($step === 'done'): ?>
      <?php if ($success): ?>
      <div class="alert alert-success"><i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($success) ?></div>
      <?php else: ?>
      <div class="alert alert-info"><i class="bi bi-info-circle me-2"></i>StromTracker ist bereits eingerichtet.</div>
      <?php endif; ?>
      <a href="login.php" class="btn btn-primary w-100">
        <i class="bi bi-box-arrow-in-right me-2"></i>Zur Anmeldung
      </a>

    <?php endif; ?>

  </div>
</div>
</body>
</html>
