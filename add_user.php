<?php
session_start();
require_once __DIR__ . '/db.php';

// Nur Admin darf zugreifen
if ($_SESSION['Rolle'] !== 'adm') {
    header("Location: dashboard.php");
    exit;
}

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vorname = trim($_POST['vorname']);
    $name = trim($_POST['name']);
    $pin = trim($_POST['pin']);

    // 1️⃣ Benutzer anlegen
    $stmt = $conn->prepare("INSERT INTO Benutzer (Name, Vorname, Rolle) VALUES (?, ?, 'usr')");
    $stmt->bind_param("ss", $name, $vorname);
    $stmt->execute();
    $personalNr = $conn->insert_id;
    $stmt->close();

    // 2️⃣ Credentials anlegen
    $stmt = $conn->prepare("INSERT INTO Credentials (PersonalNr, PIN) VALUES (?, ?)");
    $stmt->bind_param("is", $personalNr, $pin);
    $ok = $stmt->execute();
    $stmt->close();

    $msg = $ok ? "✅ Benutzer erfolgreich angelegt!" : "❌ Fehler beim Anlegen!";
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Benutzer anlegen – Sentinel</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="form-container">
    <h2>👤 Neuen Benutzer anlegen</h2>
    <?php if ($msg): ?><div class="info"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <form method="post">
        <label>Vorname</label>
        <input type="text" name="vorname" required>
        <label>Nachname</label>
        <input type="text" name="name" required>
        <label>PIN</label>
        <input type="text" name="pin" required>
        <button type="submit">Benutzer anlegen</button>
    </form>
    <a href="dashboard.php">⬅ Zurück</a>
</div>
</body>
</html>
