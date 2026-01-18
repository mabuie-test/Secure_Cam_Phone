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
?><!doctype html>

<html>
<head>
  <meta charset="utf-8">
  <title>Dashboard — Dispositivos</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    :root{--accent:#2E7D6E;--muted:#6b7280}
    body{font-family:Arial,Helvetica,sans-serif;padding:12px;background:#f4f6f8;margin:0}
    .container{max-width:1200px;margin:0 auto;padding:18px}
    h2{margin:0 0 12px}
    .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:12px}
    .card{background:#fff;border-radius:10px;padding:12px;box-shadow:0 6px 18px rgba(12,18,30,0.04);display:flex;flex-direction:column}
    .thumb{width:100%;height:180px;object-fit:cover;border-radius:8px;background:#000}
    .meta{display:flex;justify-content:space-between;align-items:center;margin-top:8px}
    .btn{background:var(--accent);color:#fff;padding:8px 10px;border-radius:8px;border:0;cursor:pointer}
    .btn.ghost{background:transparent;color:var(--accent);border:1px solid var(--accent)}
    .small{font-size:13px;color:var(--muted)}/* Modal / viewer */
.modal{position:fixed;inset:0;background:rgba(10,12,16,0.6);display:none;align-items:center;justify-content:center;z-index:9999;padding:18px}
.viewer-card{background:#000;border-radius:12px;padding:12px;max-width:1400px;width:98%;max-height:92vh;display:flex;flex-direction:column;box-shadow:0 12px 40px rgba(2,6,23,0.6)}
.viewer-header{display:flex;justify-content:space-between;align-items:center;color:#fff;margin-bottom:8px}
.viewer-stage{flex:1;display:flex;align-items:center;justify-content:center;overflow:hidden;background:#000;border-radius:8px}
.viewer-img{max-width:100%;max-height:100%;object-fit:contain}
.controls{display:flex;gap:8px;align-items:center;margin-top:10px}
.range{appearance:none;height:6px;background:#222;border-radius:6px}

@media (max-width:600px){.thumb{height:140px}.viewer-card{padding:8px}}

  </style>
</head>
<body>
  <div class="container">
    <h2>Meus Dispositivos</h2><?php if (empty($devices)): ?>
  <div class="card">Não tem dispositivos registados. Registe um dispositivo no app para começar.</div>
<?php else: ?>
  <div class="grid" id="devicesGrid">
    <?php foreach ($devices as $d):
      $did = (int)$d['id'];
      $name = htmlspecialchars($d['device_name'] ?: "Dispositivo {$did}");
      $last = htmlspecialchars($d['last_seen'] ?: '—');
      $status = htmlspecialchars($d['status'] ?: '—');
      $thumbSrc = 'api/get_frame.php?device_id=' . $did . '&_t=' . time();
    ?>
    <div class="card" data-device-id="<?= $did ?>">
      <img class="thumb" src="<?= $thumbSrc ?>" alt="thumb" onerror="this.src='data:image/svg+xml;utf8,<?= rawurlencode('<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'400\' height=\'150\' viewBox=\'0 0 400 150\'><rect width=\'100%\' height=\'100%\' fill=\'#222\'/><text x=\'50%\' y=\'50%\' fill=\'#ddd\' font-family=\'Arial,Helvetica,sans-serif\' font-size=\'20\' dominant-baseline=\'middle\' text-anchor=\'middle\'>sem imagem</text></svg>') ?>'"/>
      <div style="display:flex;justify-content:space-between;align-items:center;margin-top:8px">
        <div>
          <div style="font-weight:700"><?= $name ?></div>
          <div class="small">Último contacto: <?= $last ?></div>
        </div>
        <div style="text-align:right">
          <div class="small">Estado</div>
          <div style="font-weight:700"><?= $status ?></div>
        </div>
      </div>
      <div style="display:flex;gap:8px;margin-top:10px">
        <button class="btn viewBtn" data-device-id="<?= $did ?>">Ver</button>
        <button class="btn ghost" onclick="downloadSnapshot(<?= $did ?>)">Snapshot</button>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

  </div>  <!-- Modal viewer -->  <div id="modal" class="modal" role="dialog" aria-hidden="true">
    <div class="viewer-card">
      <div class="viewer-header">
        <div style="display:flex;flex-direction:column">
          <div id="vTitle" style="font-weight:700;color:#fff">Visualizador</div>
          <div id="vSub" class="small" style="color:#ddd">fps: <span id="vFps">—</span> • atualizado: <span id="vLast">—</span></div>
        </div>
        <div style="display:flex;gap:8px;align-items:center">
          <button class="btn ghost" id="pauseBtn">Pausar</button>
          <button class="btn" id="fullBtn">Fullscreen</button>
          <button class="btn" id="closeBtn">Fechar</button>
        </div>
      </div>
      <div class="viewer-stage">
        <img id="viewerImg" class="viewer-img" src="" alt="viewer" onerror="this.src='data:image/svg+xml;utf8,<?= rawurlencode('<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'800\' height=\'600\' viewBox=\'0 0 800 600\'><rect width=\'100%\' height=\'100%\' fill=\'#222\'/><text x=\'50%\' y=\'50%\' fill=\'#ddd\' font-family=\'Arial,Helvetica,sans-serif\' font-size=\'24\' dominant-baseline=\'middle\' text-anchor=\'middle\'>sem imagem</text></svg>') ?>'"/>
      </div>
      <div class="controls">
        <label class="small" style="color:#ddd">Intervalo (ms)</label>
        <input id="intervalRange" class="range" type="range" min="300" max="3000" step="100" value="700" style="width:220px">
        <label class="small" style="margin-left:6px;color:#ddd" id="intervalLabel">700 ms</label>
        <div style="flex:1;text-align:right"><button class="btn" id="snapBtn">Guardar frame</button></div>
      </div>
    </div>
  </div><script>
(function(){
  const modal = document.getElementById('modal');
  const viewerImg = document.getElementById('viewerImg');
  const vTitle = document.getElementById('vTitle');
  const vFps = document.getElementById('vFps');
  const vLast = document.getElementById('vLast');
  const pauseBtn = document.getElementById('pauseBtn');
  const fullBtn = document.getElementById('fullBtn');
  const closeBtn = document.getElementById('closeBtn');
  const intervalRange = document.getElementById('intervalRange');
  const intervalLabel = document.getElementById('intervalLabel');
  const snapBtn = document.getElementById('snapBtn');

  let pollTimer = null; let playing = true; let frames = 0; let fpsTimer = null; let currentDevice = null;

  function startFpsCounter(){ frames = 0; if (fpsTimer) clearInterval(fpsTimer); fpsTimer = setInterval(()=>{ vFps.textContent = frames; frames=0; }, 1000); }
  function stopFpsCounter(){ if (fpsTimer) clearInterval(fpsTimer); fpsTimer=null; vFps.textContent='—'; }

  function fetchFrame(deviceId){
    const url = 'api/get_frame.php?device_id=' + encodeURIComponent(deviceId) + '&_t=' + Date.now();
    viewerImg.src = url;
    frames++;
    vLast.textContent = new Date().toLocaleTimeString();
  }

  function startPolling(deviceId){
    if (pollTimer) clearInterval(pollTimer);
    const interval = parseInt(intervalRange.value,10);
    pollTimer = setInterval(()=>{ if (playing) fetchFrame(deviceId); }, interval);
    fetchFrame(deviceId);
    startFpsCounter();
  }
  function stopPolling(){ if (pollTimer) clearInterval(pollTimer); pollTimer=null; stopFpsCounter(); }

  document.querySelectorAll('.viewBtn').forEach(b=>b.addEventListener('click', e=>{
    const did = b.getAttribute('data-device-id');
    currentDevice = did;
    vTitle.textContent = 'Dispositivo ' + did;
    modal.style.display = 'flex';
    playing = true; pauseBtn.textContent='Pausar';
    startPolling(did);
  }));

  closeBtn.addEventListener('click', ()=>{ modal.style.display='none'; stopPolling(); viewerImg.src=''; });
  pauseBtn.addEventListener('click', ()=>{ playing = !playing; pauseBtn.textContent = playing? 'Pausar':'Retomar'; });
  fullBtn.addEventListener('click', async ()=>{ const el = document.querySelector('.viewer-card'); if (!document.fullscreenElement) { try { await el.requestFullscreen(); } catch(e){ console.warn(e); } } else { document.exitFullscreen(); } });

  intervalRange.addEventListener('input', ()=>{ intervalLabel.textContent = intervalRange.value + ' ms'; if (currentDevice) { stopPolling(); startPolling(currentDevice); } });

  window.downloadBlob = function(data, filename){ const a = document.createElement('a'); a.href = data; a.download = filename; document.body.appendChild(a); a.click(); a.remove(); }

  window.downloadSnapshot = function(did){ const url = 'api/get_frame.php?device_id='+encodeURIComponent(did)+'&_t='+Date.now(); fetch(url).then(r=>{ if (!r.ok) throw new Error('no snapshot'); return r.blob(); }).then(blob=>{ const url2 = URL.createObjectURL(blob); downloadBlob(url2,'snapshot_device_'+did+'_'+Date.now()+'.jpg'); setTimeout(()=>URL.revokeObjectURL(url2), 5000); }).catch(err=>alert('Falha ao obter snapshot: '+err.message)); }

  snapBtn.addEventListener('click', ()=>{ if (currentDevice) downloadSnapshot(currentDevice); });

  document.addEventListener('keydown', e=>{ if (e.key==='Escape') { modal.style.display='none'; stopPolling(); viewerImg.src=''; } });

})();
</script></body>
</html>
