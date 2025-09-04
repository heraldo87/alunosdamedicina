<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Upload de Planilha → n8n</title>
  <style>
    body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial; margin: 2rem; }
    form { max-width: 540px; padding: 1.25rem; border: 1px solid #ddd; border-radius: 12px; }
    label { display:block; margin:.5rem 0 .25rem; font-weight:600; }
    input, button { width:100%; padding:.75rem; margin:.25rem 0 .75rem; }
    .hint { color:#555; font-size:.9rem; margin-top:.25rem; }
  </style>
</head>
<body>
  <h1>Enviar planilha para o n8n</h1>

  <!-- IMPORTANTÍSSIMO: action = Production URL do Webhook -->
  <form
    action="https://n8n.alunosdamedicina.com/webhook/upload-xlsx"
    method="POST"
    enctype="multipart/form-data"
    target="_blank"
  >
    <!-- O campo do arquivo deve se chamar EXATAMENTE "data" -->
    <label for="data">Planilha (.xlsx ou .csv)</label>
    <input id="data" name="data" type="file" accept=".xlsx,.csv" required />

    <!-- Campos extras opcionais, se seu fluxo usa -->
    <label for="tabela">Tabela (opcional)</label>
    <input id="tabela" name="tabela" type="text" value="colaboradores" />

    <!-- Se você validou token no fluxo (nó IF), mantenha este hidden -->
    <input type="hidden" name="token" value="MEU_TOKEN_SEGURO_OPCIONAL" />

    <button type="submit">Enviar</button>
    <div class="hint">A resposta do Webhook abrirá em nova guia.</div>
  </form>
</body>
</html>
