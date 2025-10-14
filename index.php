<?php
session_start();
require_once __DIR__.'/db.php';

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vorname = trim($_POST['vorname']);
    $pin = trim($_POST['pin']);

    // Prüfen, ob Benutzer + PIN stimmen
    $stmt = $conn->prepare("
        SELECT b.PersonalNr, b.Rolle, b.Vorname, b.Name
        FROM Benutzer b
        JOIN Credentials c ON b.PersonalNr = c.PersonalNr
        WHERE b.Vorname=? AND c.PIN=?
    ");
    $stmt->bind_param("ss", $vorname, $pin);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    $uhrzeit = date('H:i:s');
    $datum = date('Y-m-d');
    $raumNr = 0; // Web-UI

    if ($user) {
        if ($user['Rolle'] === 'adm') {
            // Admin darf einloggen
            $_SESSION['PersonalNr'] = $user['PersonalNr'];
            $_SESSION['Rolle'] = $user['Rolle'];
            $_SESSION['Vorname'] = $user['Vorname'];
            $_SESSION['Name'] = $user['Name'];

            // Logeintrag SUCCESS
            $stmtLog = $conn->prepare("INSERT INTO Logs (PersonalNr, RaumNr, Datum, Uhrzeit, Ergebnis) VALUES (?, ?, ?, ?, 'SUCCESS')");
            $stmtLog->bind_param("iiss", $user['PersonalNr'], $raumNr, $datum, $uhrzeit);
            $stmtLog->execute();
            $stmtLog->close();

            header("Location: dashboard.php");
            exit;
        } else {
            // Benutzer hat kein Admin-Recht
            $stmtLog = $conn->prepare("INSERT INTO Logs (PersonalNr, RaumNr, Datum, Uhrzeit, Ergebnis) VALUES (?, ?, ?, ?, 'FAILED: NO_ACCESS')");
            $stmtLog->bind_param("iiss", $user['PersonalNr'], $raumNr, $datum, $uhrzeit);
            $stmtLog->execute();
            $stmtLog->close();

            $msg = "❌ Sie haben keinen Zugriff auf das Dashboard.";
        }
    } else {
        // Falscher Vorname oder PIN
        $stmtLog = $conn->prepare("INSERT INTO Logs (PersonalNr, RaumNr, Datum, Uhrzeit, Ergebnis) VALUES (NULL, ?, ?, ?, 'FAILED: WRONG_CREDENTIALS')");
        $stmtLog->bind_param("iss", $raumNr, $datum, $uhrzeit);
        $stmtLog->execute();
        $stmtLog->close();

        $msg = "❌ Vorname oder PIN falsch.";
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
    <h2>🔐 Sentinel Login</h2>
    <?php if ($msg): ?>
        <div class="error-msg"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>
    <form method="post">
        <label>Vorname</label>
        <input type="text" name="vorname" required>
        <label>PIN</label>
        <input type="password" name="pin" required>
        <button type="submit">Login</button>
    </form>
</div>
</body>
</html>
