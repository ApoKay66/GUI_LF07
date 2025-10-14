<?php
session_start();
require_once __DIR__ . '/db.php';
header('Content-Type: text/plain; charset=utf-8');

// Nur Admin darf zugreifen
if (!isset($_SESSION['PersonalNr']) || $_SESSION['Rolle'] !== 'adm') {
    echo "❌ Zugriff verweigert!";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "❌ Ungültige Anfrage!";
    exit;
}

$personalNr = trim($_POST['personalnr'] ?? '');
$vorname = trim($_POST['vorname'] ?? '');
$name = trim($_POST['name'] ?? '');
$pin = trim($_POST['pin'] ?? '');
$rolle = trim($_POST['rolle'] ?? '');
$fileloc = trim($_POST['fileloc'] ?? '');

if ($personalNr === '' || !is_numeric($personalNr)) {
    echo "❌ Ungültige Personalnummer!";
    exit;
}

$personalNr = intval($personalNr);

// Prüfen, ob Benutzer bereits existiert
$check = $conn->prepare("SELECT COUNT(*) AS cnt FROM Benutzer WHERE PersonalNr = ?");
$check->bind_param("i", $personalNr);
$check->execute();
$res = $check->get_result()->fetch_assoc();
$check->close();

if ($res['cnt'] > 0) {
    echo "❌ Diese Personalnummer existiert bereits!";
    exit;
}

// Benutzer anlegen
$stmt = $conn->prepare("INSERT INTO Benutzer (PersonalNr, Name, Vorname, Rolle) VALUES (?, ?, ?, ?)");
$stmt->bind_param("isss", $personalNr, $name, $vorname, $rolle);
$ok = $stmt->execute();
$stmt->close();

if (!$ok) {
    echo "❌ Fehler beim Anlegen des Benutzers.";
    exit;
}

// Zugangsdaten (PIN) anlegen
$stmt = $conn->prepare("INSERT INTO Credentials (PersonalNr, PIN) VALUES (?, ?)");
$stmt->bind_param("is", $personalNr, $pin);
$ok2 = $stmt->execute();
$stmt->close();

if (!$ok2) {
    echo "⚠️ Benutzer angelegt, aber Fehler beim Anlegen der Zugangsdaten.";
    exit;
}

echo "✅ Benutzer erfolgreich angelegt!";
exit;
