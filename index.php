<?php
session_start();
require_once __DIR__ . '/functions.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vorname = trim($_POST['vorname'] ?? '');
    $pin = trim($_POST['pin'] ?? '');
    $login = attempt_login($vorname, $pin);

    if ($login === false) {
    $error = "❌ Login fehlgeschlagen. Bitte überprüfe deine Eingaben.";
    } else {
        // Nur Admins dürfen sich einloggen
        if ($login['Rolle'] !== 'adm') {
            $error = "⛔ Zugriff verweigert: Nur Administratoren dürfen sich in der GUI anmelden.";
        } else {
            $_SESSION['PersonalNr'] = $login['PersonalNr'];
            $_SESSION['Vorname'] = $login['Vorname'];
            $_SESSION['Name'] = $login['Name'];
            $_SESSION['Rolle'] = $login['Rolle'];
            header("Location: dashboard.php");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Login – Sentinel</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="login-container">
    <h1>🔐 Sentinel Login</h1>
    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="post">
        <label>Vorname</label>
        <input type="text" name="vorname" required>
        <label>PIN</label>
        <input type="password" name="pin" required>
        <button type="submit">Anmelden</button>
    </form>
</div>
</body>
</html>
