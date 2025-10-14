<?php
session_start();
require_once __DIR__ . '/db.php';

// Zugriff nur für angemeldete Admins
if (!isset($_SESSION['PersonalNr']) || $_SESSION['Rolle'] !== 'adm') {
    header("Location: index.php");
    exit;
}

$vollerName = $_SESSION['Vorname'] . ' ' . $_SESSION['Name'];

// Event Logs
$eventLogs = $conn->query("
    SELECT l.LogNr, l.Datum, l.Uhrzeit, l.Ergebnis, b.Vorname, b.Name, r.Name AS RaumName
    FROM Logs l
    LEFT JOIN Benutzer b ON b.PersonalNr = l.PersonalNr
    LEFT JOIN Räume r ON r.RaumNr = l.RaumNr
    ORDER BY l.Datum DESC, l.Uhrzeit DESC
");

// Benutzer
$benutzer = $conn->query("SELECT PersonalNr, Vorname, Name, Rolle FROM Benutzer ORDER BY Name ASC");

// Räume
$raeume = $conn->query("
    SELECT r.RaumNr, r.Name AS RaumName
    FROM Räume r
    ORDER BY r.RaumNr
");
?>

<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<title>Dashboard – Sentinel</title>
<link rel="stylesheet" href="assets/style.css">
<style>
.table-filter-input { width: 130px; padding: 5px 6px; margin-left: 4px; font-size: 13px; border: 1px solid #000000ff; border-radius: 4px; background-color: #f9f9f9; color: #333; transition: all 0.2s ease; }
.table-filter-input:focus { background-color: #fff; border-color: #245eeb; outline: none; }
table th { border-right: 1px solid #000000ff; white-space: nowrap; }
table th:last-child { border-right: none; }
th label { display: inline-flex; flex-direction: column; align-items: flex-start; gap: 4px; }

.add-user-container { display: flex; justify-content: center; align-items: center; margin-top: 15px; }
#add-user-btn { width:50px; height:50px; border-radius:50%; background-color:#245eeb; color:#fff; border:none; cursor:pointer; display:flex; align-items:center; justify-content:center; padding:0; font-size:28px; line-height:1; box-shadow:0 2px 6px rgba(0,0,0,0.18); box-sizing:border-box; }
#add-user-btn:hover { background-color:#1747c0; transform:translateY(-1px); }

.modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); justify-content:center; align-items:center; z-index:1000; }
.modal-content { background:white; padding:25px; border-radius:12px; width:90%; max-width:500px; box-shadow:0 0 10px rgba(0,0,0,0.3); position:relative; }
.modal h3 { margin-top:0; }
.close-modal { position:absolute; top:10px; right:15px; cursor:pointer; color:#888; font-size:22px; font-weight:bold; }
.close-modal:hover { color:#000; }
.modal form { display:flex; flex-direction:column; gap:10px; }
.modal input, .modal select { padding:8px; border:1px solid #ccc; border-radius:6px; }
.modal button { background:#245eeb; color:#fff; padding:10px; border:none; border-radius:6px; cursor:pointer; }
.modal button:hover { background:#1747c0; }

.dashboard-container { display:flex; min-height:100vh; }
.sidebar { background:#1f2937; color:white; width:220px; padding:20px; display:flex; flex-direction:column; justify-content:space-between; }
.tab-btn { background:#374151; color:white; border:none; padding:12px; margin-bottom:8px; border-radius:8px; cursor:pointer; text-align:left; font-size:15px; }
.tab-btn:hover { background:#4b5563; }
.logout-btn { background:#dc2626; color:white; border:none; padding:10px; border-radius:8px; cursor:pointer; }
.logout-btn:hover { background:#b91c1c; }
.user-info { margin-top:15px; font-size:14px; text-align:center; color:#cbd5e1; }
.content { flex:1; padding:20px; overflow-x:auto; }
.hidden { display:none; }
.success-msg { color:green; font-weight:bold; margin-bottom:10px; text-align:center; }
.error-msg { color:red; font-weight:bold; margin-bottom:10px; text-align:center; }

.result-success { color:green; font-weight:bold; }
.result-fail { color:red; font-weight:bold; }
</style>
</head>
<body>
<div class="dashboard-container">
    <!-- Sidebar -->
    <div class="sidebar">
        <div>
            <button class="tab-btn" onclick="showContent('benutzer')">👥 Benutzer</button>
            <button class="tab-btn" onclick="showContent('eventlog')">📋 Event Log</button>
            <button class="tab-btn" onclick="showContent('raeume')">🚪 Räume</button>
        </div>
        <div>
            <form action="logout.php" method="post"><button type="submit" class="logout-btn">Abmelden</button></form>
            <div class="user-info"><?= htmlspecialchars($vollerName) ?></div>
        </div>
    </div>

    <!-- Hauptinhalt -->
    <div class="content">
        <!-- Benutzer -->
        <div id="benutzer">
            <h2>👥 Benutzer</h2>
            <table id="benutzer-table">
                <thead>
                    <tr>
                        <th><label>PersonalNr<input class="table-filter-input" placeholder="suchen..." oninput="filterTable('benutzer-table',0)"></label></th>
                        <th><label>Vorname<input class="table-filter-input" placeholder="suchen..." oninput="filterTable('benutzer-table',1)"></label></th>
                        <th><label>Name<input class="table-filter-input" placeholder="suchen..." oninput="filterTable('benutzer-table',2)"></label></th>
                        <th><label>Rolle<input class="table-filter-input" placeholder="suchen..." oninput="filterTable('benutzer-table',3)"></label></th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $benutzer->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['PersonalNr'] ?></td>
                            <td><?= htmlspecialchars($row['Vorname']) ?></td>
                            <td><?= htmlspecialchars($row['Name']) ?></td>
                            <td><?= htmlspecialchars($row['Rolle']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <div class="add-user-container"><button id="add-user-btn">+</button></div>
        </div>

        <!-- Event Log -->
        <div id="eventlog" class="hidden">
            <h2>📋 Event Log</h2>
            <table id="eventlog-table">
                <thead>
                    <tr>
                        <th><label>LogNr<input class="table-filter-input" placeholder="suchen..." oninput="filterTable('eventlog-table',0)"></label></th>
                        <th><label>Datum<input class="table-filter-input" placeholder="suchen..." oninput="filterTable('eventlog-table',1)"></label></th>
                        <th><label>Uhrzeit<input class="table-filter-input" placeholder="suchen..." oninput="filterTable('eventlog-table',2)"></label></th>
                        <th><label>Benutzer<input class="table-filter-input" placeholder="suchen..." oninput="filterTable('eventlog-table',3)"></label></th>
                        <th><label>Raum<input class="table-filter-input" placeholder="suchen..." oninput="filterTable('eventlog-table',4)"></label></th>
                        <th><label>Ergebnis<input class="table-filter-input" placeholder="suchen..." oninput="filterTable('eventlog-table',5)"></label></th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $eventLogs->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['LogNr'] ?></td>
                            <td><?= $row['Datum'] ?></td>
                            <td><?= $row['Uhrzeit'] ?></td>
                            <td><?= htmlspecialchars($row['Vorname'].' '.$row['Name']) ?></td>
                            <td><?= htmlspecialchars($row['RaumName']) ?></td>
                            <td class="<?= $row['Ergebnis']==='SUCCESS'?'result-success':'result-fail' ?>"><?= htmlspecialchars($row['Ergebnis']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Räume -->
        <div id="raeume" class="hidden">
            <h2>🚪 Räume</h2>
            <table id="raeume-table">
                <thead>
                    <tr>
                        <th><label>RaumNr<input class="table-filter-input" placeholder="suchen..." oninput="filterTable('raeume-table',0)"></label></th>
                        <th><label>Raumname<input class="table-filter-input" placeholder="suchen..." oninput="filterTable('raeume-table',1)"></label></th>
                        <th>Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($r = $raeume->fetch_assoc()): ?>
                        <tr>
                            <td><?= $r['RaumNr'] ?></td>
                            <td><?= htmlspecialchars($r['RaumName']) ?></td>
                            <td><button class="show-access-btn" data-raumnr="<?= $r['RaumNr'] ?>">Zugänge anzeigen</button></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal für neuen Benutzer -->
<div id="userModal" class="modal">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <h3>➕ Neuen Benutzer anlegen</h3>
        <div id="formMsg"></div>
        <form id="newUserForm">
            <label>Personalnummer</label><input type="number" name="personalnr" required>
            <label>Vorname</label><input type="text" name="vorname" required>
            <label>Nachname</label><input type="text" name="name" required>
            <label>PIN</label><input type="password" name="pin" required>
            <label>Rolle</label>
            <select name="rolle" required>
                <option value="usr">Benutzer (usr)</option>
                <option value="adm">Administrator (adm)</option>
            </select>
            <button type="submit">Benutzer anlegen</button>
        </form>
    </div>
</div>

<!-- Modal für Raumzugänge -->
<div id="accessModal" class="modal">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <h3>Zugänge für Raum <span id="modalRaumNr"></span></h3>
        <table id="accessTable">
            <thead>
                <tr>
                    <th>PersonalNr</th><th>Name</th><th>Vorname</th><th>Zugang ab</th><th>Zugang bis</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<script>
function showContent(id){
    ['benutzer','eventlog','raeume'].forEach(sec=>document.getElementById(sec).classList.add('hidden'));
    document.getElementById(id).classList.remove('hidden');
}
showContent('benutzer');

// Tabellenfilter
function filterTable(tableId,col){
    const inputElems=document.querySelectorAll(`#${tableId} thead input`);
    const filters=Array.from(inputElems).map(i=>i.value.toLowerCase());
    document.querySelectorAll(`#${tableId} tbody tr`).forEach(row=>{
        const cells=row.querySelectorAll('td');
        row.style.display=filters.every((f,i)=>cells[i]?.textContent.toLowerCase().includes(f))?'':'none';
    });
}

// Modal Benutzer
const userModal=document.getElementById('userModal');
document.getElementById('add-user-btn').onclick=()=>userModal.style.display='flex';
document.querySelector('#userModal .close-modal').onclick=()=>userModal.style.display='none';
window.onclick=e=>{if(e.target===userModal)userModal.style.display='none';};

// AJAX Benutzer hinzufügen
document.getElementById('newUserForm').addEventListener('submit',async e=>{
    e.preventDefault();
    const form=new FormData(e.target);
    const msgBox=document.getElementById('formMsg');
    msgBox.innerHTML='';
    const res=await fetch('add_user.php',{method:'POST',body:form});
    const text=await res.text();
    if(text.includes('✅')){
        msgBox.innerHTML="<div class='success-msg'>✅ Benutzer erfolgreich angelegt!</div>";
        e.target.reset();
        setTimeout(()=>location.reload(),1000);
    }else{
        msgBox.innerHTML="<div class='error-msg'>❌ "+text.replace(/(<([^>]+)>)/gi,"")+"</div>";
    }
});

// Modal Raumzugänge
const accessModal=document.getElementById('accessModal');
const accessTableBody=document.querySelector('#accessTable tbody');
document.querySelectorAll('.show-access-btn').forEach(btn=>{
    btn.addEventListener('click',async ()=>{
        const raumNr=btn.getAttribute('data-raumnr');
        document.getElementById('modalRaumNr').textContent=raumNr;
        const res=await fetch(`get_room_access.php?raumNr=${raumNr}`);
        const data=await res.json();
        accessTableBody.innerHTML='';
        data.forEach(user=>{
            const tr=document.createElement('tr');
            tr.innerHTML=`<td>${user.PersonalNr}</td><td>${user.Name}</td><td>${user.Vorname}</td><td>${user.Zugelassen_Ab}</td><td>${user.Zugelassen_Bis}</td>`;
            accessTableBody.appendChild(tr);
        });
        accessModal.style.display='flex';
    });
});
document.querySelector('#accessModal .close-modal').onclick=()=>accessModal.style.display='none';
window.onclick=e=>{if(e.target===accessModal)accessModal.style.display='none';};
</script>
</body>
</html>
