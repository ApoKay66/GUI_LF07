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

if ($raumNr === 0) {
    // Raum 0: Alle Admins ganztägig
    $stmt = $conn->prepare("
        SELECT PersonalNr, Name, Vorname, '00:00:00' AS Zugelassen_Ab, '23:59:59' AS Zugelassen_Bis
        FROM Benutzer
        WHERE Rolle = 'adm'
        ORDER BY Name ASC
    ");
} else {
    // Normale Räume
    $stmt = $conn->prepare("
        SELECT rb.PersonalNr, b.Name, b.Vorname, rb.Zugelassen_Ab, rb.Zugelassen_Bis
        FROM Raumbenutzer rb
        JOIN Benutzer b ON b.PersonalNr = rb.PersonalNr
        WHERE rb.RaumNr = ?
        ORDER BY b.Name ASC
    ");
    $stmt->bind_param("i", $raumNr);
}

$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

header('Content-Type: application/json');
echo json_encode($data);
