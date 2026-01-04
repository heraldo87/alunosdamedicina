<?php
session_start();
// Verifica login
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit;
}
include 'php/config.php'; // Sua conexão se precisar
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerenciar Workspaces - Alunos da Medicina</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        /* Identidade Visual Amarela */
        .btn-brand-yellow {
            background-color: #FFC107; /* Amarelo Padrão */
            color: #000;
            font-weight: bold;
            border: none;
            transition: 0.3s;
        }
        .btn-brand-yellow:hover {
            background-color: #e0a800;
        }
        
        /* Loading Overlay */
        #loadingOverlay {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(255,255,255,0.8);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            flex-direction: column;
        }
    </style>
</head>
<body class="bg-light">

    <div class="d-flex">
        <?php include 'includes/sidebar.php'; ?>

        <div class="container-fluid p-4">
            <h2 class="mb-4">Minhas Workspaces</h2>

            <div class="card shadow-sm border-0">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">Criar Nova Área de Arquivos</h5>
                        <p class="card-text text-muted">Crie uma nova pasta sincronizada no drive para seus livros e documentos.</p>
                    </div>
                    <button class="btn btn-brand-yellow px-4 py-2" data-bs-toggle="modal" data-bs-target="#modalNovaWorkspace">
                        <i class="fas fa-plus-circle me-2"></i> Nova Workspace
                    </button>
                </div>
            </div>

            <div id="alertArea" class="mt-3"></div>

        </div>
    </div>

    <div class="modal fade" id="modalNovaWorkspace" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">Nova Workspace</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="formWorkspace">
                        <div class="mb-3">
                            <label for="nomePasta" class="form-label">Nome da Workspace (Pasta)</label>
                            <input type="text" class="form-control form-control-lg" id="nomePasta" placeholder="Ex: Anatomia 2024" required>
                            <div class="form-text">Isso criará uma pasta segura vinculada à sua conta.</div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-brand-yellow" onclick="criarWorkspace()">
                        Criar Agora
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div id="loadingOverlay">
        <div class="spinner-border text-warning" role="status" style="width: 3rem; height: 3rem;"></div>
        <div class="mt-3 fw-bold">Sincronizando com a Nuvem...</div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        async function criarWorkspace() {
            const nomePasta = document.getElementById('nomePasta').value;
            const modalEl = document.getElementById('modalNovaWorkspace');
            const modalInstance = bootstrap.Modal.getInstance(modalEl);
            const loading = document.getElementById('loadingOverlay');
            const alertArea = document.getElementById('alertArea');

            // Validação simples
            if (!nomePasta) {
                alert("Por favor, digite um nome para a pasta.");
                return;
            }

            // UX: Fecha modal e mostra loader
            modalInstance.hide();
            loading.style.display = 'flex';
            alertArea.innerHTML = ''; // Limpa alertas anteriores

            try {
                const response = await fetch('api/criar_workspace.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ nome_pasta: nomePasta })
                });

                const data = await response.json();

                if (response.ok) {
                    // Sucesso
                    alertArea.innerHTML = `
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <strong>Sucesso!</strong> A workspace "${nomePasta}" está sendo criada. Aguarde a sincronização.
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    `;
                    document.getElementById('nomePasta').value = ''; // Limpa input
                } else {
                    throw new Error(data.message || 'Erro desconhecido');
                }

            } catch (error) {
                // Erro
                alertArea.innerHTML = `
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>Erro:</strong> ${error.message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                `;
                // Reabre o modal se deu erro (opcional)
                modalInstance.show();
            } finally {
                loading.style.display = 'none';
            }
        }
    </script>
</body>
</html>