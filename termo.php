<?php
/**
 * MEDINFOCUS - Termos de Uso e Privacidade
 * Ação: O botão redireciona o usuário para a página cadastro.php
 */
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Termos de Uso - MEDINFOCUS</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Merriweather:ital,wght@0,300;0,400;0,700;1,400&display=swap" rel="stylesheet">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            dark: '#0f172a',
                            primary: '#0284c7',
                            secondary: '#0ea5e9',
                            surface: '#f8fafc',
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
</head>
<body class="bg-brand-surface text-slate-700 font-sans antialiased">

    <nav class="bg-white border-b border-slate-200 sticky top-0 z-50 shadow-sm">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-brand-primary rounded-lg flex items-center justify-center text-white">
                        <i class="fa-solid fa-heart-pulse"></i>
                    </div>
                    <span class="font-bold text-xl tracking-tight text-slate-800">MED<span class="text-brand-primary">INFOCUS</span></span>
                </div>
                <div class="flex items-center">
                    <button onclick="window.close()" class="text-sm font-medium text-slate-500 hover:text-brand-primary transition-colors">
                        Fechar Janela <i class="fa-solid fa-times ml-1"></i>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-4xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
        
        <div class="bg-white shadow-sm rounded-xl border border-slate-200 overflow-hidden">
            
            <div class="bg-slate-50 border-b border-slate-100 p-8 text-center">
                <h1 class="text-3xl font-bold text-slate-900 mb-2">Termos de Uso e Política de Privacidade</h1>
                <p class="text-slate-500 text-sm">Última atualização: <?php echo date('d/m/Y'); ?></p>
            </div>

            <div class="p-8 md:p-12 prose prose-slate max-w-none font-serif text-slate-600 leading-relaxed">
                
                <h3 class="font-sans text-xl font-bold text-slate-900 mt-0 mb-4">1. Aceitação dos Termos</h3>
                <p class="mb-6">
                    Ao acessar e utilizar a plataforma <strong>MEDINFOCUS</strong>, você concorda integralmente com estes termos. Esta plataforma é destinada exclusivamente a estudantes de medicina e profissionais da saúde para fins acadêmicos e de gestão de estudos.
                </p>

                <h3 class="font-sans text-xl font-bold text-slate-900 mb-4">2. Uso Responsável</h3>
                <p class="mb-6">
                    O usuário compromete-se a utilizar os conteúdos disponibilizados apenas para fins de aprendizado pessoal. É estritamente proibido:
                </p>
                <ul class="list-disc pl-5 mb-6 space-y-2">
                    <li>Compartilhar suas credenciais de acesso com terceiros.</li>
                    <li>Reproduzir ou comercializar material didático exclusivo da plataforma.</li>
                    <li>Utilizar o sistema para armazenar dados sensíveis de pacientes reais (violação da LGPD/HIPAA).</li>
                </ul>

                <h3 class="font-sans text-xl font-bold text-slate-900 mb-4">3. Propriedade Intelectual</h3>
                <p class="mb-6">
                    Todo o design, código fonte, logotipos e materiais didáticos presentes no alunosdamedicina.com são propriedade exclusiva do Projeto MEDINFOCUS ou de seus parceiros licenciados.
                </p>

                <h3 class="font-sans text-xl font-bold text-slate-900 mb-4">4. Privacidade de Dados</h3>
                <p class="mb-6">
                    Respeitamos sua privacidade. Seus dados cadastrais (nome, email) são utilizados unicamente para controle de acesso e personalização da sua experiência de estudo. Não vendemos seus dados para terceiros.
                </p>

                <hr class="my-8 border-slate-200">

                <p class="text-sm italic text-slate-400 text-center">
                    Em caso de dúvidas, entre em contato com o suporte através do email: suporte@alunosdamedicina.com
                </p>

            </div>
            
            <div class="bg-slate-50 p-6 flex justify-center border-t border-slate-100">
                <button onclick="window.print()" class="px-4 py-2 bg-white border border-slate-300 rounded-lg text-slate-700 text-sm font-medium hover:bg-slate-50 transition-all mr-3">
                    <i class="fa-solid fa-print mr-2"></i> Imprimir
                </button>
                
                <button onclick="window.location.href='cadastro.php'" class="px-6 py-2 bg-brand-primary text-white rounded-lg text-sm font-medium hover:bg-sky-700 transition-all shadow-md active:transform active:scale-95">
                    Li, Entendi e Concordo
                </button>
            </div>

        </div>
    </main>

</body>
</html>