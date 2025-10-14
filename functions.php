<?php
require_once __DIR__ . '/db.php';

// Loggen von Loginversuchen (Raum wird nicht mehr berücksichtigt)
function log_access($PersonalNr, $Ergebnis = 'FAIL') {
    global $conn;
    $datum = date('Y-m-d');
    $uhrzeit = date('H:i:s');
    $stmt = $conn->prepare("
        INSERT INTO Logs (PersonalNr, Datum, Uhrzeit, Ergebnis)
        VALUES (?, ?, ?, ?)
    ");
    if (!$stmt) die("Prepare fehlgeschlagen: " . $conn->error);
    $stmt->bind_param("isss", $PersonalNr, $datum, $uhrzeit, $Ergebnis);
    $stmt->execute();
    $stmt->close();
}

function attempt_login($vorname, $pin) {
    global $conn;

    $stmt = $conn->prepare("
        SELECT b.PersonalNr, b.Vorname, b.Name, b.Rolle, c.PIN
        FROM Benutzer b
        INNER JOIN Credentials c ON b.PersonalNr = c.PersonalNr
        WHERE b.Vorname = ?
    ");
    $stmt->bind_param("s", $vorname);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // PIN prüfen
        if ($pin !== $row['PIN']) {
            log_access($row['PersonalNr'], 'FAIL');
            return false;
        }

        // Rolle prüfen: nur Admin = SUCCESS
        if ($row['Rolle'] !== 'adm') {
            log_access($row['PersonalNr'], 'FAIL');
            return false;
        }

        // Alles ok → Admin darf einloggen
        log_access($row['PersonalNr'], 'SUCCESS');
        return $row;
    }

    // Benutzer nicht gefunden
    log_access(null, 'FAIL');
    return false;
}


