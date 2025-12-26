<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MEDINFOCUS - Guia de Estilo</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts: Inter (Interface) e Merriweather (Leitura longa/Médica) -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Merriweather:ital,wght@0,300;0,400;0,700;1,400&display=swap" rel="stylesheet">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            dark: '#0f172a',    // Slate 900 - Textos principais e fundo escuro
                            primary: '#0284c7', // Sky 600 - Cor primária (Ação, Confiança)
                            secondary: '#0ea5e9', // Sky 500 - Cor secundária
                            accent: '#10b981',  // Emerald 500 - Sucesso, Saúde
                            surface: '#f8fafc', // Slate 50 - Fundo de telas claras
                            light: '#ffffff',
                            danger: '#ef4444',
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        serif: ['Merriweather', 'serif'],
                    }
                }
            }
        }
    </script>
    <style>
        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
</head>
<body class="bg-slate-100 text-brand-dark font-sans antialiased">

    <!-- Navbar simulada do ambiente de desenvolvimento -->
    <nav class="bg-white border-b border-slate-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0 flex items-center gap-2">
                        <!-- Logo Icon Concept -->
                        <div class="w-8 h-8 bg-brand-primary rounded-lg flex items-center justify-center text-white">
                            <i class="fa-solid fa-heart-pulse"></i>
                        </div>
                        <span class="font-bold text-xl tracking-tight text-slate-800">MED<span class="text-brand-primary">INFOCUS</span></span>
                    </div>
                    <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                        <a href="#branding" class="border-brand-primary text-slate-900 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">Branding</a>
                        <a href="#components" class="border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">Componentes</a>
                        <a href="#preview" class="border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">Prévia Login</a>
                    </div>
                </div>
                <div class="flex items-center">
                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-brand-primary/10 text-brand-primary border border-brand-primary/20">v1.0.0 Alpha</span>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8 space-y-16">

        <!-- HEADER DE INTRODUÇÃO -->
        <div class="text-center">
            <h1 class="text-3xl font-bold text-slate-900 sm:text-4xl">Sistema de Design & Identidade</h1>
            <p class="mt-3 max-w-2xl mx-auto text-xl text-slate-500 sm:mt-4">
                Definição visual para o projeto <span class="font-semibold text-brand-primary">alunosdamedicina.com</span>. 
                Foco em clareza, profissionalismo e foco acadêmico.
            </p>
        </div>

        <!-- SEÇÃO 1: CORES -->
        <section id="branding" class="scroll-mt-20">
            <div class="border-b border-slate-200 pb-5 mb-8">
                <h3 class="text-lg leading-6 font-medium text-slate-900">1. Paleta de Cores</h3>
                <p class="mt-2 text-sm text-slate-500">Cores escolhidas para transmitir limpeza, tecnologia e foco.</p>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-6">
                <!-- Primary -->
                <div class="space-y-2">
                    <div class="h-24 w-full bg-brand-primary rounded-lg shadow-sm flex items-center justify-center text-white font-mono text-sm">#0284c7</div>
                    <div class="px-1">
                        <h4 class="text-sm font-semibold text-slate-900">Primary Blue</h4>
                        <p class="text-xs text-slate-500">Ações principais, Links, Logo</p>
                    </div>
                </div>
                <!-- Secondary -->
                <div class="space-y-2">
                    <div class="h-24 w-full bg-brand-secondary rounded-lg shadow-sm flex items-center justify-center text-white font-mono text-sm">#0ea5e9</div>
                    <div class="px-1">
                        <h4 class="text-sm font-semibold text-slate-900">Secondary Sky</h4>
                        <p class="text-xs text-slate-500">Hover, Detalhes, Ícones</p>
                    </div>
                </div>
                <!-- Dark -->
                <div class="space-y-2">
                    <div class="h-24 w-full bg-brand-dark rounded-lg shadow-sm flex items-center justify-center text-white font-mono text-sm">#0f172a</div>
                    <div class="px-1">
                        <h4 class="text-sm font-semibold text-slate-900">Slate Dark</h4>
                        <p class="text-xs text-slate-500">Títulos, Texto Corpo, Sidebar</p>
                    </div>
                </div>
                <!-- Accent -->
                <div class="space-y-2">
                    <div class="h-24 w-full bg-brand-accent rounded-lg shadow-sm flex items-center justify-center text-white font-mono text-sm">#10b981</div>
                    <div class="px-1">
                        <h4 class="text-sm font-semibold text-slate-900">Medical Green</h4>
                        <p class="text-xs text-slate-500">Sucesso, Aprovação, Saúde</p>
                    </div>
                </div>
                <!-- Surface -->
                <div class="space-y-2">
                    <div class="h-24 w-full bg-brand-surface border border-slate-200 rounded-lg shadow-sm flex items-center justify-center text-slate-400 font-mono text-sm">#f8fafc</div>
                    <div class="px-1">
                        <h4 class="text-sm font-semibold text-slate-900">Surface Gray</h4>
                        <p class="text-xs text-slate-500">Fundo geral da aplicação</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- SEÇÃO 2: TIPOGRAFIA -->
        <section class="scroll-mt-20">
            <div class="border-b border-slate-200 pb-5 mb-8">
                <h3 class="text-lg leading-6 font-medium text-slate-900">2. Tipografia</h3>
                <p class="mt-2 text-sm text-slate-500">Combinação de <span class="font-semibold">Inter</span> (UI) e <span class="font-serif">Merriweather</span> (Conteúdo denso).</p>
            </div>

            <div class="bg-white p-8 rounded-xl shadow-sm border border-slate-200 space-y-8">
                <div>
                    <span class="text-xs font-mono text-slate-400 uppercase tracking-widest">Headings (Inter)</span>
                    <h1 class="text-4xl font-bold text-slate-900 mt-2">Título H1 - Bem-vindo ao MedInFocus</h1>
                    <h2 class="text-2xl font-semibold text-slate-800 mt-2">Título H2 - Painel do Aluno</h2>
                    <h3 class="text-xl font-medium text-slate-700 mt-2">Título H3 - Módulo de Anatomia</h3>
                </div>
                <div class="grid md:grid-cols-2 gap-8">
                    <div>
                        <span class="text-xs font-mono text-slate-400 uppercase tracking-widest">Body Text UI (Inter)</span>
                        <p class="mt-2 text-slate-600 leading-relaxed">
                            Utilizamos a fonte Inter para elementos de interface, menus, botões e textos curtos. É altamente legível em telas e transmite modernidade.
                        </p>
                    </div>
                    <div>
                        <span class="text-xs font-mono text-slate-400 uppercase tracking-widest">Academic Text (Merriweather)</span>
                        <p class="mt-2 font-serif text-slate-700 leading-relaxed">
                            Para artigos médicos, resumos de aulas e conteúdos longos, usamos a Merriweather. Serifas ajudam na leitura contínua e reduzem a fadiga ocular do estudante.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- SEÇÃO 3: COMPONENTES DE UI -->
        <section id="components" class="scroll-mt-20">
            <div class="border-b border-slate-200 pb-5 mb-8">
                <h3 class="text-lg leading-6 font-medium text-slate-900">3. Componentes de UI</h3>
                <p class="mt-2 text-sm text-slate-500">Elementos reutilizáveis para consistência.</p>
            </div>

            <div class="grid lg:grid-cols-2 gap-8">
                <!-- Botões -->
                <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                    <h4 class="text-sm font-semibold text-slate-900 mb-4">Botões</h4>
                    <div class="flex flex-wrap gap-4 items-center">
                        <button class="px-5 py-2.5 bg-brand-primary text-white text-sm font-medium rounded-lg shadow-md hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sky-500 transition-all">
                            Primário
                        </button>
                        <button class="px-5 py-2.5 bg-white text-slate-700 border border-slate-300 text-sm font-medium rounded-lg hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sky-500 transition-all">
                            Secundário
                        </button>
                        <button class="px-5 py-2.5 bg-brand-accent text-white text-sm font-medium rounded-lg shadow-md hover:bg-emerald-600 transition-all">
                            <i class="fa-solid fa-check mr-2"></i> Sucesso
                        </button>
                    </div>
                </div>

                <!-- Inputs -->
                <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                    <h4 class="text-sm font-semibold text-slate-900 mb-4">Campos de Formulário</h4>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Email Acadêmico</label>
                            <input type="email" placeholder="aluno@medicina.com" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-primary focus:border-brand-primary outline-none transition-all text-slate-900">
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- SEÇÃO 4: PRÉVIA DA PÁGINA DE LOGIN -->
        <section id="preview" class="scroll-mt-20 pb-20">
            <div class="border-b border-slate-200 pb-5 mb-8">
                <h3 class="text-lg leading-6 font-medium text-slate-900">4. Prévia: Página de Login</h3>
                <p class="mt-2 text-sm text-slate-500">Aplicação dos conceitos acima no layout final.</p>
            </div>

            <div class="relative bg-slate-200 p-4 md:p-10 rounded-xl overflow-hidden border border-slate-300">
                <!-- Mockup Window -->
                <div class="bg-white rounded-2xl shadow-2xl overflow-hidden max-w-5xl mx-auto flex flex-col md:flex-row min-h-[600px]">
                    
                    <!-- Lado Esquerdo (Branding/Imagem) -->
                    <div class="w-full md:w-1/2 bg-gradient-to-br from-brand-primary to-blue-900 p-12 text-white flex flex-col justify-between relative overflow-hidden">
                        <!-- Padrão de fundo decorativo -->
                        <div class="absolute top-0 left-0 w-full h-full opacity-10 pointer-events-none">
                            <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                                <path d="M0 100 C 20 0 50 0 100 100 Z" fill="white" />
                            </svg>
                        </div>

                        <div class="relative z-10">
                            <div class="flex items-center gap-2 mb-2">
                                <div class="w-10 h-10 bg-white/20 backdrop-blur-sm rounded-lg flex items-center justify-center text-white">
                                    <i class="fa-solid fa-heart-pulse text-xl"></i>
                                </div>
                                <span class="font-bold text-2xl tracking-tight">MED<span class="text-sky-300">INFOCUS</span></span>
                            </div>
                            <p class="text-sky-100 text-sm">Plataforma de Gestão Acadêmica</p>
                        </div>

                        <div class="relative z-10">
                            <h2 class="text-3xl font-bold mb-4 leading-tight">O futuro da sua carreira médica começa aqui.</h2>
                            <p class="text-sky-100 mb-8">Acesse materiais, acompanhe notas e gerencie seus estudos em um único lugar.</p>
                            
                            <div class="flex items-center gap-4 text-sm text-sky-200">
                                <div class="flex -space-x-2">
                                    <div class="w-8 h-8 rounded-full bg-slate-300 border-2 border-brand-primary"></div>
                                    <div class="w-8 h-8 rounded-full bg-slate-400 border-2 border-brand-primary"></div>
                                    <div class="w-8 h-8 rounded-full bg-slate-500 border-2 border-brand-primary"></div>
                                </div>
                                <span>+2.000 Alunos ativos</span>
                            </div>
                        </div>
                    </div>

                    <!-- Lado Direito (Formulário) -->
                    <div class="w-full md:w-1/2 p-8 md:p-12 flex flex-col justify-center bg-white">
                        <div class="max-w-md w-full mx-auto">
                            <h2 class="text-2xl font-bold text-slate-900 mb-1">Bem-vindo de volta</h2>
                            <p class="text-slate-500 mb-8 text-sm">Por favor, insira seus dados para entrar.</p>

                            <form onsubmit="event.preventDefault(); alert('Este é apenas um protótipo visual. O backend PHP será integrado na próxima etapa.');" class="space-y-5">
                                <div>
                                    <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <i class="fa-regular fa-envelope text-slate-400"></i>
                                        </div>
                                        <input type="email" id="email" class="w-full pl-10 pr-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-primary focus:border-brand-primary outline-none transition-all text-slate-900 placeholder-slate-400" placeholder="voce@alunosdamedicina.com">
                                    </div>
                                </div>

                                <div>
                                    <div class="flex justify-between mb-1">
                                        <label for="password" class="block text-sm font-medium text-slate-700">Senha</label>
                                        <a href="#" class="text-sm text-brand-primary hover:text-brand-secondary font-medium">Esqueceu a senha?</a>
                                    </div>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <i class="fa-solid fa-lock text-slate-400"></i>
                                        </div>
                                        <input type="password" id="password" class="w-full pl-10 pr-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-primary focus:border-brand-primary outline-none transition-all text-slate-900 placeholder-slate-400" placeholder="••••••••">
                                    </div>
                                </div>

                                <button type="submit" class="w-full bg-brand-primary text-white font-semibold py-2.5 rounded-lg shadow-md hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-primary transition-all duration-200 flex items-center justify-center gap-2">
                                    Entrar na Plataforma <i class="fa-solid fa-arrow-right text-sm"></i>
                                </button>
                            </form>

                            <div class="mt-8 pt-6 border-t border-slate-100 text-center">
                                <p class="text-sm text-slate-500">
                                    Ainda não tem conta? 
                                    <a href="#" class="text-brand-primary font-semibold hover:text-brand-secondary">Criar cadastro</a>
                                </p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </section>

    </main>
    
    <footer class="bg-slate-900 text-slate-400 py-12 text-center">
        <p class="mb-4 text-slate-500 text-sm">Desenvolvimento PROJETO MEDINFOCUS</p>
        <p>&copy; 2024 alunosdamedicina.com - Todos os direitos reservados.</p>
    </footer>

</body>
</html>