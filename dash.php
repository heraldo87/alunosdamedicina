<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Sistema COHIDRO</title>
  <style>
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background: #f5f6fa;
    }

    header {
      background: #2c3e50;
      color: white;
      padding: 1rem;
      text-align: center;
    }

    .container {
      display: flex;
      flex-wrap: wrap;
      padding: 1rem;
      gap: 1rem;
    }

    .card {
      background: white;
      padding: 1rem;
      border-radius: 8px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
      flex: 1;
      min-width: 250px;
    }

    .iframe-container {
      width: 100%;
      height: 500px;
      border: 2px solid #ddd;
      border-radius: 8px;
      overflow: hidden;
      margin-bottom: 1rem;
    }

    iframe {
      width: 100%;
      height: 100%;
      border: none;
    }

    footer {
      background: #2c3e50;
      color: white;
      text-align: center;
      padding: 0.5rem;
      position: fixed;
      bottom: 0;
      width: 100%;
    }
  </style>
</head>
<body>
  <header>
    <h1>Sistema COHIDRO</h1>
  </header>

  <div class="container">
    <div class="card">
      <h2>Informações</h2>
      <p>Alguns dados ou texto descritivo aqui.</p>
    </div>
    <div class="card">
      <h2>Resumo</h2>
      <p>Resumo de atividades ou relatórios.</p>
    </div>
  </div>

  <div class="container">
    <!-- Iframe para dashboard dinâmico (Streamlit) -->
    <div class="iframe-container">
      <iframe src="http://181.215.135.63:8501" title="Dashboard COHIDRO"></iframe>
    </div>

    <!-- Iframe para gráfico estático exportado em HTML -->
    <div class="iframe-container">
      <iframe src="grafico.html" title="Gráfico de Registros"></iframe>
    </div>
  </div>

  <footer>
    &copy; 2025 COHIDRO - Sistema Interno
  </footer>
</body>
</html>
