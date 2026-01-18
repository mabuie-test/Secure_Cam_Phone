<?php
// dashboard.php - dashboard enriquecido: lista dispositivos e visualizador em tempo real
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/config.php';

// verifica sessão (adapta conforme a tua implementação)
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$uid = (int)$_SESSION['user_id'];
$pdo = getPDO();

// obtem dispositivos do utilizador
$stmt = $pdo->prepare("SELECT id, device_name, device_token, last_seen, status FROM devices WHERE user_id = :uid ORDER BY id DESC");
$stmt->execute([':uid' => $uid]);
$devices = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Dashboard — Dispositivos</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    body{font-family:Arial,Helvetica,sans-serif;padding:12px;background:#f4f6f8}
    table{width:100%;border-collapse:collapse;background:#fff}
    th,td{padding:10px;border-bottom:1px solid #eee;text-align:left}
    th{background:#fafafa}
    button{padding:8px 12px;border-radius:6px;border:0;background:#2E7D6E;color:#fff;cursor:pointer}
    .modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.6);align-items:center;justify-content:center}
    .card{background:#fff;padding:12px;border-radius:8px;max-width:900px;width:95%}
    #viewer{width:100%;max-height:70vh;border-radius:6px;background:#000}
  </style>
</head>
<body>
  <h2>Meus Dispositivos</h2>

  <?php if (empty($devices)): ?>
    <p>Não tem dispositivos registados. Registe um dispositivo no app para começar.</p>
  <?php else: ?>
    <table>
      <thead>
        <tr><th>Id</th><th>Nome</th><th>Último contacto</th><th>Estado</th><th>Ações</th></tr>
      </thead>
      <tbody>
      <?php foreach ($devices as $d): ?>
        <tr>
          <td><?= htmlspecialchars($d['id']) ?></td>
          <td><?= htmlspecialchars($d['device_name'] ?: '—') ?></td>
          <td><?= htmlspecialchars($d['last_seen']) ?></td>
          <td><?= htmlspecialchars($d['status']) ?></td>
          <td>
            <button data-device-id="<?= (int)$d['id'] ?>" data-device-token="<?= htmlspecialchars($d['device_token']) ?>" class="view-btn">Ver</button>
            &nbsp;
            <form style="display:inline" method="post" action="api/delete_device.php" onsubmit="return confirm('Eliminar este dispositivo?');">
              <input type="hidden" name="device_id" value="<?= (int)$d['id'] ?>">
              <button type="submit" style="background:#c0392b">Eliminar</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>

  <!-- Modal / Visualizador -->
  <div id="modal" class="modal" role="dialog" aria-hidden="true">
    <div class="card">
      <button id="close" style="float:right;background:#999">Fechar</button>
      <h3 id="modal-title">Visualizador</h3>
      <img id="viewer" src="" alt="video stream">
      <div style="margin-top:8px;">
        <label>FPS aproximado: <span id="fps">—</span></label>
        <label style="margin-left:16px">Última actualização: <span id="last">—</span></label>
      </div>
    </div>
  </div>

<script>
(function(){
  let modal = document.getElementById('modal');
  let viewer = document.getElementById('viewer');
  let closeBtn = document.getElementById('close');
  let modalTitle = document.getElementById('modal-title');
  let fpsEl = document.getElementById('fps');
  let lastEl = document.getElementById('last');

  let pollInterval = 700; // ms - ajusta para 400-1000
  let pollTimer = null;
  let lastTs = 0;
  let frames = 0;
  let fpsTimer = null;

  function startFpsCounter(){
    frames = 0;
    if (fpsTimer) clearInterval(fpsTimer);
    fpsTimer = setInterval(()=> {
      fpsEl.textContent = frames + " fps (aprox)";
      frames = 0;
    }, 1000);
  }

  function startPolling(deviceId){
    // cria url para obter frame
    function fetchFrame(){
      const url = 'api/get_frame.php?device_id=' + encodeURIComponent(deviceId) + '&_t=' + Date.now();
      // apenas muda src do img (força reload)
      viewer.src = url;
      frames++;
      lastEl.textContent = new Date().toLocaleTimeString();
    }
    fetchFrame();
    pollTimer = setInterval(fetchFrame, pollInterval);
    startFpsCounter();
  }

  function stopPolling(){
    if (pollTimer) clearInterval(pollTimer);
    pollTimer = null;
    if (fpsTimer) clearInterval(fpsTimer);
    fpsTimer = null;
    fpsEl.textContent = '—';
    lastEl.textContent = '—';
  }

  document.querySelectorAll('.view-btn').forEach(btn=>{
    btn.addEventListener('click', (e)=>{
      const deviceId = btn.getAttribute('data-device-id');
      const name = btn.closest('tr').querySelector('td:nth-child(2)').textContent;
      modalTitle.textContent = 'Dispositivo: ' + name + ' (id ' + deviceId + ')';
      modal.style.display = 'flex';
      startPolling(deviceId);
    });
  });

  closeBtn.addEventListener('click', ()=>{
    modal.style.display = 'none';
    stopPolling();
    viewer.src = '';
  });

  // fechar ao clicar fora
  modal.addEventListener('click', (ev)=> {
    if (ev.target === modal) {
      modal.style.display = 'none';
      stopPolling();
      viewer.src = '';
    }
  });
})();
</script>
</body>
</html>
