<?php
session_start();
require_once __DIR__ . '/db.php';

// Nur Admin darf zugreifen
if ($_SESSION['Rolle'] !== 'adm') {
    header("Location: dashboard.php");
    exit;
}

// Logs + Benutzerinformationen abrufen
$result = $conn->query("
    SELECT l.LogNr, l.Datum, l.Uhrzeit, l.Ergebnis, b.Vorname, b.Name, l.RaumNr
    FROM Logs l
    LEFT JOIN Benutzer b ON b.PersonalNr = l.PersonalNr
    ORDER BY l.Datum DESC, l.Uhrzeit DESC
");
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Access Log – Sentinel</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="log-container">
    <h2>📋 Zugriff-Log</h2>
    <table>
        <thead>
            <tr>
                <th>LogNr</th>
                <th>Datum</th>
                <th>Uhrzeit</th>
                <th>Benutzer</th>
                <th>Raum</th>
                <th>Ergebnis</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['LogNr']) ?></td>
                    <td><?= htmlspecialchars(date('d.m.Y', strtotime($row['Datum']))) ?></td>
                    <td><?= htmlspecialchars(date('H:i:s', strtotime($row['Uhrzeit']))) ?></td>
                    <td><?= htmlspecialchars($row['Vorname'] . ' ' . $row['Name']) ?></td>
                    <td><?= htmlspecialchars($row['RaumNr'] ?? '-') ?></td>
                    <td class="<?= $row['Ergebnis'] === 'SUCCESS' ? 'success' : 'fail' ?>">
                        <?= htmlspecialchars($row['Ergebnis']) ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="6">Keine Log-Einträge gefunden.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    <a href="dashboard.php" class="btn">⬅ Zurück</a>
</div>
</body>
</html>
