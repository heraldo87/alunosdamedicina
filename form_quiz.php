<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Fórum – MedinFocus</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <style>
    body {
      font-family: 'Inter', sans-serif;
      background-color: #f8f9fa;
    }
    .forum-card {
      border-radius: 0.75rem;
      transition: transform 0.3s, box-shadow 0.3s;
    }
    .forum-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15);
    }
    .badge-new {
      background-color: #28a745;
      color: #fff;
      font-size: 0.75rem;
      transition: transform 0.2s;
    }
    .badge-new:hover {
      transform: scale(1.1);
    }
    .stats-bar {
      background-color: #ffffff;
      padding: 1rem;
      border-radius: 0.75rem;
      box-shadow: 0 0.2rem 0.6rem rgba(0,0,0,0.1);
      margin-bottom: 1rem;
    }
    .navbar-brand {
      font-weight: 700;
    }
  </style>
</head>
<body>

  <!-- NAVBAR -->
  <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
    <div class="container">
      <a class="navbar-brand" href="#">MedinFocus</a>
      <button class="btn btn-primary btn-sm">Criar Novo Tópico</button>
    </div>
  </nav>

  <div class="container my-4">

    <!-- STATS BAR -->
    <div class="stats-bar text-center">
      <div class="row">
        <div class="col-md">
          <strong>23 Fóruns</strong><br/><small>Disponíveis</small>
        </div>
        <div class="col-md">
          <strong>1.248 Tópicos</strong><br/><small>Ativos</small>
        </div>
        <div class="col-md">
          <strong>27 Usuários</strong><br/><small>Online</small>
        </div>
      </div>
    </div>

    <h2 class="mb-4">Fóruns Disponíveis</h2>
    <div class="row g-4">
      <!-- Card de Exemplo -->
      <div class="col-md-6 col-lg-4">
        <div class="card forum-card h-100">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
              <h5 class="card-title">Anatomia Humana</h5>
              <span class="badge badge-new">Novo</span>
            </div>
            <p class="card-text text-muted">Discussões sobre anatomia, atlas e recursos para estudo.</p>
          </div>
          <div class="card-footer d-flex justify-content-between small text-muted">
            <span>32 tópicos</span>
            <span>Última atividade: 2 h atrás</span>
          </div>
        </div>
      </div>
      <!-- Adicionar mais cards conforme necessário... -->
    </div>
  </div>

  <footer class="bg-white border-top py-3 mt-5">
    <div class="container text-center text-muted small">
      © 2025 MedinFocus – Todos os direitos reservados
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
