<?php
require_once __DIR__ . '/db.php';

if (!isset($_GET['raum'])) exit('Kein Raum ausgewählt.');

$raumNr = intval($_GET['raum']);

// 1️⃣ Benutzer, die Zugang haben
$zugang = $conn->prepare("
    SELECT b.Vorname, b.Name, rb.Zugelassen_Ab, rb.Zugelassen_Bis
    FROM Raumbenutzer rb
    INNER JOIN Benutzer b ON rb.PersonalNr = b.PersonalNr
    WHERE rb.RaumNr = ?
");
$zugang->bind_param("i", $raumNr);
$zugang->execute();
$resultZugang = $zugang->get_result();

// 2️⃣ Logs für diesen Raum
$logs = $conn->prepare("
    SELECT l.Datum, l.Uhrzeit, b.Vorname, b.Name, l.Ergebnis
    FROM Logs l
    LEFT JOIN Benutzer b ON l.PersonalNr = b.PersonalNr
    WHERE l.RaumNr = ?
    ORDER BY l.Datum DESC, l.Uhrzeit DESC
");
$logs->bind_param("i", $raumNr);
$logs->execute();
$resultLogs = $logs->get_result();

?>

<h4>Zugelassene Benutzer:</h4>
<?php if($resultZugang->num_rows>0): ?>
<table border="1" width="100%" cellpadding="5">
    <tr><th>Benutzer</th><th>Zugelassen von</th><th>Zugelassen bis</th></tr>
    <?php while($row=$resultZugang->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($row['Vorname'] . ' ' . $row['Name']) ?></td>
            <td><?= htmlspecialchars($row['Zugelassen_Ab']) ?></td>
            <td><?= htmlspecialchars($row['Zugelassen_Bis']) ?></td>
        </tr>
    <?php endwhile; ?>
</table>
<?php else: ?>
<p>Keine Benutzer mit Zugang gefunden.</p>
<?php endif; ?>

<h4>Logs für Raum:</h4>
<?php if($resultLogs->num_rows>0): ?>
<table border="1" width="100%" cellpadding="5">
    <tr><th>Datum</th><th>Uhrzeit</th><th>Benutzer</th><th>Ergebnis</th></tr>
    <?php while($row=$resultLogs->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($row['Datum']) ?></td>
            <td><?= htmlspecialchars($row['Uhrzeit']) ?></td>
            <td><?= htmlspecialchars($row['Vorname'] . ' ' . $row['Name']) ?></td>
            <td class="<?= $row['Ergebnis']==='SUCCESS'?'success':'fail' ?>"><?= htmlspecialchars($row['Ergebnis']) ?></td>
        </tr>
    <?php endwhile; ?>
</table>
<?php else: ?>
<p>Keine Log-Einträge für diesen Raum.</p>
<?php endif; ?>
