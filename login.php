<?php
require_once __DIR__ . '/includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ' . BASE_PATH . '/dashboard.php');
    exit;
}

// Check if installed
if (!isInstalled()) {
    header('Location: ' . BASE_PATH . '/setup.php');
    exit;
}
$bp = BASE_PATH;
?><!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Anmelden – StromTracker</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="<?= $bp ?>/assets/css/style.css">
</head>
<body>
<div class="login-wrapper">
  <div class="login-card shadow-lg">
    <div class="login-header">
      <i class="bi bi-lightning-charge-fill logo-icon"></i>
      <h1>StromTracker</h1>
      <p>Strom &amp; Solar überwachen</p>
    </div>
    <div class="login-body">
      <div id="alertBox" class="alert alert-danger d-none" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <span id="alertText">Falsches Passwort</span>
      </div>
      <form id="loginForm" novalidate>
        <div class="mb-4">
          <label class="form-label" for="password">Passwort</label>
          <div class="input-group">
            <input type="password" id="password" class="form-control form-control-lg"
                   placeholder="Dein Passwort" required autofocus autocomplete="current-password">
            <button type="button" class="btn btn-outline-secondary" id="togglePw" tabindex="-1">
              <i class="bi bi-eye" id="toggleIcon"></i>
            </button>
          </div>
        </div>
        <button type="submit" class="btn btn-primary w-100 py-2 fs-6" id="submitBtn">
          <span id="btnText"><i class="bi bi-box-arrow-in-right me-2"></i>Anmelden</span>
          <span id="btnSpinner" class="d-none">
            <span class="spinner-border spinner-border-sm me-2"></span>Bitte warten…
          </span>
        </button>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const BASE_PATH = <?= json_encode($bp) ?>;
const form     = document.getElementById('loginForm');
const pwInput  = document.getElementById('password');
const alertBox = document.getElementById('alertBox');
const alertTxt = document.getElementById('alertText');
const btnText  = document.getElementById('btnText');
const btnSpin  = document.getElementById('btnSpinner');
const submitBtn= document.getElementById('submitBtn');

document.getElementById('togglePw').addEventListener('click', () => {
  const icon = document.getElementById('toggleIcon');
  if (pwInput.type === 'password') {
    pwInput.type = 'text';
    icon.className = 'bi bi-eye-slash';
  } else {
    pwInput.type = 'password';
    icon.className = 'bi bi-eye';
  }
  pwInput.focus();
});

form.addEventListener('submit', async (e) => {
  e.preventDefault();
  alertBox.classList.add('d-none');
  btnText.classList.add('d-none');
  btnSpin.classList.remove('d-none');
  submitBtn.disabled = true;

  try {
    const res  = await fetch(BASE_PATH + '/api/auth.php', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({ action: 'login', password: pwInput.value }),
    });
    const data = await res.json();
    if (data.success) {
      window.location.href = BASE_PATH + '/dashboard.php';
    } else {
      throw new Error(data.error || 'Falsches Passwort');
    }
  } catch (err) {
    alertTxt.textContent = err.message;
    alertBox.classList.remove('d-none');
    pwInput.value = '';
    pwInput.focus();
  } finally {
    btnText.classList.remove('d-none');
    btnSpin.classList.add('d-none');
    submitBtn.disabled = false;
  }
});
</script>
</body>
</html>
