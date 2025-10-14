<?php
session_start();
require_once __DIR__ . '/db.php';

// Nur Admin darf auf das Dashboard
if (!isset($_SESSION['PersonalNr']) || $_SESSION['Rolle'] !== 'adm') {
    header("Location: index.php");
    exit;
}

// Event Log abrufen
$eventLogs = $conn->query("
    SELECT l.LogNr, l.Datum, l.Uhrzeit, l.Ergebnis, l.RaumNr, b.Vorname, b.Name, b.Rolle
    FROM Logs l
    LEFT JOIN Benutzer b ON b.PersonalNr = l.PersonalNr
    ORDER BY l.Datum DESC, l.Uhrzeit DESC
");

// Benutzer abrufen
$benutzer = $conn->query("SELECT PersonalNr, Vorname, Name, Rolle FROM Benutzer ORDER BY Name ASC");

// Räume abrufen
$raeume = $conn->query("SELECT RaumNr FROM Räume ORDER BY RaumNr ASC");

// Name des eingeloggten Benutzers
$vollerName = $_SESSION['Vorname'] . ' ' . ($_SESSION['Name'] ?? '');
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Dashboard – Sentinel</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .table-filter-input { width: 130px; padding: 5px 6px; margin-left: 4px; font-size: 13px; border: 1px solid #000; border-radius: 4px; background-color: #f9f9f9; transition: all 0.2s ease; }
        .table-filter-input:focus { background-color: #fff; border-color: #245eeb; outline: none; }
        table th { border-right: 1px solid #000; white-space: nowrap; }
        table th:last-child { border-right: none; }
        th label { display: inline-flex; align-items: center; gap: 4px; }
        /* Modal Styles */
        .modal { display: none; position: fixed; top: 0; left: 0; width:100%; height:100%; background: rgba(0,0,0,0.5); justify-content:center; align-items:center; }
        .modal-content { background:#fff; padding:20px; border-radius:12px; width:80%; max-width:600px; max-height:80%; overflow:auto; }
        .close-btn { float:right; cursor:pointer; font-size:18px; }
    </style>
</head>
<body>
<div class="dashboard-container">

    <!-- Sidebar -->
    <div class="sidebar">
        <button class="tab-btn" onclick="showContent('benutzer')">👥 Benutzer</button>
        <button class="tab-btn" onclick="showContent('eventlog')">📋 Event Log</button>
        <button class="tab-btn" onclick="showContent('raeume')">🚪 Räume</button>
        <form action="logout.php" method="post">
            <button type="submit" class="logout-btn">Abmelden</button>
        </form>
        <div class="user-info"><?= htmlspecialchars($vollerName) ?></div>
    </div>

    <!-- Content -->
    <div class="content">

        <!-- Benutzer -->
        <div id="benutzer">
            <h2>👥 Benutzer</h2>
            <table id="benutzer-table">
                <thead>
                <tr>
                    <th><label>PersonalNr <input type="text" class="table-filter-input" placeholder="Suchen…" onkeyup="filterColumn(this, 0, 'benutzer-table')"></label></th>
                    <th><label>Vorname <input type="text" class="table-filter-input" placeholder="Suchen…" onkeyup="filterColumn(this, 1, 'benutzer-table')"></label></th>
                    <th><label>Name <input type="text" class="table-filter-input" placeholder="Suchen…" onkeyup="filterColumn(this, 2, 'benutzer-table')"></label></th>
                    <th><label>Rolle <input type="text" class="table-filter-input" placeholder="Suchen…" onkeyup="filterColumn(this, 3, 'benutzer-table')"></label></th>
                </tr>
                </thead>
                <tbody>
                <?php if ($benutzer && $benutzer->num_rows > 0): ?>
                    <?php while ($row = $benutzer->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['PersonalNr']) ?></td>
                            <td><?= htmlspecialchars($row['Vorname']) ?></td>
                            <td><?= htmlspecialchars($row['Name']) ?></td>
                            <td><?= htmlspecialchars($row['Rolle']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="4">Keine Benutzer gefunden.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Event Log -->
        <div id="eventlog" class="hidden">
            <h2>📋 Event Log</h2>
            <table id="eventlog-table">
                <thead>
                <tr>
                    <th><label>LogNr <input type="text" class="table-filter-input" placeholder="Suchen…" onkeyup="filterColumn(this, 0, 'eventlog-table')"></label></th>
                    <th><label>Datum <input type="text" class="table-filter-input" placeholder="Suchen…" onkeyup="filterColumn(this, 1, 'eventlog-table')"></label></th>
                    <th><label>Uhrzeit <input type="text" class="table-filter-input" placeholder="Suchen…" onkeyup="filterColumn(this, 2, 'eventlog-table')"></label></th>
                    <th><label>Benutzer <input type="text" class="table-filter-input" placeholder="Suchen…" onkeyup="filterColumn(this, 3, 'eventlog-table')"></label></th>
                    <th><label>Raum <input type="text" class="table-filter-input" placeholder="Suchen…" onkeyup="filterColumn(this, 4, 'eventlog-table')"></label></th>
                    <th><label>Ergebnis <input type="text" class="table-filter-input" placeholder="Suchen…" onkeyup="filterColumn(this, 5, 'eventlog-table')"></label></th>
                </tr>
                </thead>
                <tbody>
                <?php if ($eventLogs && $eventLogs->num_rows > 0): ?>
                    <?php while ($row = $eventLogs->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['LogNr']) ?></td>
                            <td><?= htmlspecialchars(date('d.m.Y', strtotime($row['Datum']))) ?></td>
                            <td><?= htmlspecialchars($row['Uhrzeit']) ?></td>
                            <td><?= htmlspecialchars($row['Vorname'] . ' ' . $row['Name']) ?></td>
                            <td>
                                <?php
                                if (empty($row['RaumNr']) && $row['Rolle'] === 'adm') {
                                    echo "GUI";
                                } else {
                                    echo htmlspecialchars($row['RaumNr'] ?? '-');
                                }
                                ?>
                            </td>
                            <td class="<?= $row['Ergebnis']==='SUCCESS'?'success':'fail' ?>">
                                <?= htmlspecialchars($row['Ergebnis']) ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6">Keine Logs gefunden.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Räume -->
        <div id="raeume" class="hidden">
            <h2>🚪 Räume</h2>
            <table id="raeume-table">
                <thead>
                <tr>
                    <th>RaumNr</th>
                    <th>Zugang anzeigen</th>
                </tr>
                </thead>
                <tbody>
                <?php if ($raeume && $raeume->num_rows > 0): ?>
                    <?php while ($row = $raeume->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['RaumNr']) ?></td>
                            <td>
                                <button onclick="showRoomDetails(<?= $row['RaumNr'] ?>)">🖊️</button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="2">Keine Räume gefunden.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<!-- Modal -->
<div class="modal" id="roomModal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal()">✖</span>
        <h3 id="modalTitle">Raum Details</h3>
        <div id="modalBody">Lade...</div>
    </div>
</div>

<script>
function showContent(id) {
    const sections = ['benutzer','eventlog','raeume'];
    sections.forEach(sec => document.getElementById(sec).classList.add('hidden'));
    document.getElementById(id).classList.remove('hidden');
}

function filterColumn(input, colIndex, tableId) {
    const filter = input.value.toLowerCase();
    const table = document.getElementById(tableId);
    const rows = table.querySelectorAll('tbody tr');
    rows.forEach(row => {
        const cell = row.getElementsByTagName('td')[colIndex];
        if (!cell) return;
        row.style.display = cell.textContent.toLowerCase().includes(filter) ? '' : 'none';
    });
}

// Modal Funktionen
function showRoomDetails(raumNr){
    const modal = document.getElementById('roomModal');
    modal.style.display = 'flex';
    document.getElementById('modalTitle').innerText = 'Raum ' + raumNr + ' Details';
    document.getElementById('modalBody').innerHTML = 'Lade...';

    fetch('get_room_details.php?raum=' + raumNr)
        .then(res => res.text())
        .then(html => document.getElementById('modalBody').innerHTML = html);
}

function closeModal(){
    document.getElementById('roomModal').style.display = 'none';
}

showContent('benutzer'); // Standard
</script>
</body>
</html>
