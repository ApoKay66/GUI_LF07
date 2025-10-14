<?php
// rfid_login.php
// Erwartet JSON mit { "rfid_id": "UID", "raum": 1001 }
// Antwort: JSON { status: "ok" } oder { status: "fail", message: "..." }

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['status' => 'fail', 'message' => 'Ungültige Anfrage']);
    exit;
}

$rfid_id = trim($input['rfid_id'] ?? '');
$raumNr = isset($input['raum']) ? intval($input['raum']) : null;

if ($rfid_id === '' || $raumNr === null) {
    echo json_encode(['status' => 'fail', 'message' => 'rfid_id und raum sind erforderlich']);
    exit;
}

// Wir suchen Credentials.PIN = rfid_id (du kannst ändern, wenn du UID anders speicherst)
$stmt = $conn->prepare("
    SELECT b.PersonalNr, b.Name, b.Vorname, b.Rolle
    FROM Benutzer b
    JOIN Credentials c ON b.PersonalNr = c.PersonalNr
    WHERE c.PIN = ?
    LIMIT 1
");
$stmt->bind_param("s", $rfid_id);
$stmt->execute();
$res = $stmt->get_result();

if ($user = $res->fetch_assoc()) {
    // Optional: prüfen, ob Benutzer für diesen Raum zugelassen ist (Raumbenutzer)
    // Wenn du das möchtest, uncomment die folgenden Zeilen:
    /*
    $chk = $conn->prepare("SELECT * FROM Raumbenutzer WHERE PersonalNr = ? AND RaumNr = ?");
    $chk->bind_param("ii", $user['PersonalNr'], $raumNr);
    $chk->execute();
    $chkres = $chk->get_result();
    if (!$chkres->fetch_assoc()) {
        log_access($user['PersonalNr'], 'FAIL', $raumNr);
        echo json_encode(['status' => 'fail', 'message' => 'Nicht für Raum zugelassen']);
        exit;
    }
    $chk->close();
    */

    // Erfolgreiches Raum-Login loggen
    log_access($user['PersonalNr'], 'SUCCESS', $raumNr);
    echo json_encode(['status' => 'ok']);
} else {
    // Kein Benutzer gefunden -> Fehlversuch loggen (PersonalNr NULL)
    log_access(null, 'FAIL', $raumNr);
    echo json_encode(['status' => 'fail', 'message' => 'Unknown RFID']);
}
$stmt->close();
