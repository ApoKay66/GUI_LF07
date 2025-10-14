<?php
session_start();
require_once __DIR__ . '/db.php';

// Zugriff nur für Admins
if (!isset($_SESSION['PersonalNr']) || $_SESSION['Rolle'] !== 'adm') {
    http_response_code(403);
    echo json_encode([]);
    exit;
}

$raumNr = isset($_GET['raumNr']) ? intval($_GET['raumNr']) : -1;

// Raum 0 = Web-UI: alle Admins ganztägig
if ($raumNr === 0) {
    $admins = $conn->query("SELECT PersonalNr, Vorname, Name FROM Benutzer WHERE Rolle='adm'");
    $result = [];
    while ($row = $admins->fetch_assoc()) {
        $result[] = [
            'PersonalNr' => $row['PersonalNr'],
            'Vorname' => $row['Vorname'],
            'Name' => $row['Name'],
            'Zugelassen_Ab' => '00:00:00',
            'Zugelassen_Bis' => '23:59:59'
        ];
    }
    echo json_encode($result);
    exit;
}

// Andere Räume: Daten aus Raumbenutzer + Benutzer
$stmt = $conn->prepare("
    SELECT b.PersonalNr, b.Vorname, b.Name, rb.Zugelassen_Ab, rb.Zugelassen_Bis
    FROM Raumbenutzer rb
    INNER JOIN Benutzer b ON b.PersonalNr = rb.PersonalNr
    WHERE rb.RaumNr = ?
    ORDER BY b.Name
");
$stmt->bind_param("i", $raumNr);
$stmt->execute();
$res = $stmt->get_result();
$result = [];
while ($row = $res->fetch_assoc()) {
    $result[] = $row;
}
$stmt->close();

header('Content-Type: application/json');
echo json_encode($result);
