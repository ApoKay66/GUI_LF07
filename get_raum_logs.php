<?php
session_start();
require_once __DIR__ . '/db.php';

// Nur Admin darf auf die Daten zugreifen
if (!isset($_SESSION['PersonalNr']) || $_SESSION['Rolle'] !== 'adm') {
    http_response_code(403);
    echo json_encode([]);
    exit;
}

$raumNr = intval($_GET['raum'] ?? 0);

$stmt = $conn->prepare("
    SELECT l.Datum, l.Uhrzeit, l.Ergebnis, b.Vorname, b.Name
    FROM Logs l
    LEFT JOIN Benutzer b ON b.PersonalNr = l.PersonalNr
    WHERE l.RaumNr = ?
    ORDER BY l.Datum DESC, l.Uhrzeit DESC
");
$stmt->bind_param("i", $raumNr);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

header('Content-Type: application/json');
echo json_encode($data);
