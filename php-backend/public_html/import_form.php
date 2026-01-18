<?php
// import_form.php - formulário simples para upload do dump SQL
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Importar SQL</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    body{font-family:Arial,sans-serif;padding:16px;background:#f6f7fb}
    .card{max-width:720px;margin:0 auto;background:#fff;padding:16px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.06)}
    label{display:block;margin:8px 0 4px;font-weight:600}
    input[type=text],input[type=file]{width:100%;padding:8px;border-radius:6px;border:1px solid #ddd}
    button{display:inline-block;margin-top:12px;background:#2E7D6E;color:#fff;padding:10px 14px;border-radius:8px;border:0}
    .warn{color:#b33;margin-top:10px}
    pre{background:#111;color:#dff;padding:10px;border-radius:6px;overflow:auto}
  </style>
</head>
<body>
  <div class="card">
    <h2>Importar ficheiro SQL</h2>
    <p>Use este formulário para carregar um ficheiro <code>.sql</code> pequeno (teste/dev). Define o <strong>segredo</strong> que configuraste no servidor antes de enviar.</p>

    <form action="import_sql.php" method="post" enctype="multipart/form-data">
      <label for="secret">Segredo (IMPORT_SECRET)</label>
      <input type="text" id="secret" name="secret" required placeholder="Cole o segredo aqui">

      <label for="sqlfile">Ficheiro .sql</label>
      <input type="file" id="sqlfile" name="sqlfile" accept=".sql,text/plain" required>

      <label for="mode">Modo</label>
      <select id="mode" name="mode">
        <option value="execute" selected>Executar (aplica statements)</option>
        <option value="dry">Dry-run (não executa; apenas mostra início do ficheiro)</option>
      </select>

      <button type="submit">Enviar e importar</button>
    </form>

    <p class="warn"><strong>Atenção:</strong> Este endpoint executa SQL diretamente na tua base de dados. Usa apenas com dumps confiáveis e apaga o ficheiro depois do uso.</p>
    <p>Depois do envio vais ver uma página com o resultado (sucesso/erros). Guarda logs apenas temporariamente.</p>
  </div>
</body>
</html>
