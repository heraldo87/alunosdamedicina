<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'MedinFocus - Painel do Aluno'; ?></title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        /* Estilo para o overlay de fundo em mobile */
        .sidebar-overlay-hidden {
            opacity: 0;
            pointer-events: none;
        }
        /* Estilo para gradientes nos botões */
        .app-card-gradient {
            background-image: linear-gradient(135deg, var(--tw-gradient-stops));
        }
    </style>
</head>
<body class="bg-gray-100 flex flex-col min-h-screen lg:flex-row">
    <div id="sidebar-overlay" class="fixed inset-0 bg-gray-900 bg-opacity-50 z-40 transition-opacity duration-300 sidebar-overlay-hidden lg:hidden"></div>