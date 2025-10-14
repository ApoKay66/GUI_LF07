<?php
require_once __DIR__ . '/db.php';

/**
 * log_access
 * Loggt einen Zugang (GUI oder Raum). Wenn $raumNr null, wird Raum nicht in INSERT geschrieben.
 *
 * @param int|null $PersonalNr
 * @param string $Ergebnis
 * @param int|null $RaumNr
 */
function log_access($PersonalNr, $Ergebnis = 'FAIL', $RaumNr = null) {
    global $conn;

    $datum = date('Y-m-d');
    $uhrzeit = date('H:i:s');

    if ($RaumNr === null) {
        $stmt = $conn->prepare("
            INSERT INTO Logs (PersonalNr, Datum, Uhrzeit, Ergebnis)
            VALUES (?, ?, ?, ?)
        ");
        if (!$stmt) die("Prepare fehlgeschlagen: " . $conn->error);
        $stmt->bind_param("isss", $PersonalNr, $datum, $uhrzeit, $Ergebnis);
    } else {
        $stmt = $conn->prepare("
            INSERT INTO Logs (PersonalNr, RaumNr, Datum, Uhrzeit, Ergebnis)
            VALUES (?, ?, ?, ?, ?)
        ");
        if (!$stmt) die("Prepare fehlgeschlagen: " . $conn->error);
        $stmt->bind_param("iisss", $PersonalNr, $RaumNr, $datum, $uhrzeit, $Ergebnis);
    }

    $stmt->execute();
    $stmt->close();
}

/**
 * attempt_login
 * Prüft Login anhand Vorname + PIN (PIN liegt in Credentials).
 * Liefert Benutzerdaten zurück oder false.
 *
 * @param string $vorname
 * @param string $pin
 * @return array|false
 */
function attempt_login($vorname, $pin) {
    global $conn;

    // Wir suchen Benutzer nach Vorname und prüfen PIN in Credentials
    $stmt = $conn->prepare("
        SELECT b.PersonalNr, b.Vorname, b.Name, b.Rolle
        FROM Benutzer b
        JOIN Credentials c ON b.PersonalNr = c.PersonalNr
        WHERE b.Vorname = ? AND c.PIN = ?
        LIMIT 1
    ");
    if (!$stmt) die("Prepare fehlgeschlagen: " . $conn->error);

    $stmt->bind_param("ss", $vorname, $pin);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($row = $res->fetch_assoc()) {
        // erfolgreichen Login loggen (GUI-Login wird in index.php mit RaumNr=0 geloggt)
        // Wir loggen hier nicht automatisch, da Aufrufer entscheiden soll (z.B. GUI vs RFID).
        $stmt->close();
        return $row;
    }

    $stmt->close();
    return false;
}
?>
