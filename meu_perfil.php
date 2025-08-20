<?php
// Inicia a sessão e verifica se o usuário está autenticado
require_once 'php/auth_check.php';
// Inclui o arquivo de conexão com o banco de dados (mysqli em $conn)
require_once 'php/conn.php';

// Pega o ID do usuário da sessão (garante inteiro)
$user_id = (int)($_SESSION['user_id'] ?? 0);
$user_data = null;

if ($user_id > 0) {
    // Consulta usando apenas colunas existentes na sua tabela
    $stmt = $conn->prepare("
        SELECT 
            id, full_name, phone, ru, turma, turno, email, 
            terms_accepted, nivel_acesso, data_insercao
        FROM usuarios
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $user_data = $result->fetch_assoc();

        // --- TRATAMENTO PARA EXIBIÇÃO ---

        // Mapeia nível de acesso numérico para texto amigável
        $nivel_map = [1 => 'Aluno', 2 => 'Moderador', 3 => 'Administrador'];
        $nivel_num = (int)($user_data['nivel_acesso'] ?? 0);
        $user_data['nivel_acesso_nome'] = $nivel_map[$nivel_num] ?? 'Desconhecido';

        // Usa terms_accepted como "status" da conta (apenas para badge informativa)
        $terms_ok = (int)($user_data['terms_accepted'] ?? 0) === 1;
        $user_data['status_texto'] = $terms_ok ? 'Termos Aceitos' : 'Termos Pendentes';
        $status_class = $terms_ok ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800';

        // Data de cadastro
        $data_cad = '—';
        if (!empty($user_data['data_insercao'])) {
            try {
                $dt = new DateTime($user_data['data_insercao']);
                $data_cad = $dt->format('d/m/Y');
            } catch (Exception $e) {
                $data_cad = '—';
            }
        }
        $user_data['data_cadastro_formatada'] = $data_cad;

        // Foto de perfil: tenta por arquivo {id}.{ext} em uploads/profile_pics/
        $profile_pic = 'assets/img/default-avatar.png';
        $baseDir = __DIR__ . '/uploads/profile_pics';
        $webDir  = 'uploads/profile_pics';
        $exts = ['jpg', 'jpeg', 'png', 'webp'];
        foreach ($exts as $ext) {
            $diskPath = $baseDir . '/' . $user_id . '.' . $ext;
            if (file_exists($diskPath)) {
                $profile_pic = $webDir . '/' . $user_id . '.' . $ext;
                break;
            }
        }
    }

    $stmt->close();
}
$conn->close();

$pageTitle = "Meu Perfil";
require_once 'includes/header.php';
?>

<div class="flex h-screen bg-gray-100 font-sans">
    <?php require_once 'includes/sidebar_nav.php'; ?>

    <div class="flex-1 flex flex-col overflow-hidden">
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100">
            <div class="container mx-auto px-6 py-8">
                
                <h3 class="text-gray-700 text-3xl font-medium mb-8">Visão Geral do Perfil</h3>

                <?php if ($user_data): ?>
                <div class="bg-white rounded-2xl shadow-xl p-8 max-w-5xl mx-auto">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        
                        <div class="lg:col-span-1 flex flex-col items-center text-center border-r-0 lg:border-r lg:pr-8">
                            <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="Foto do Perfil" class="w-32 h-32 rounded-full object-cover mb-4 ring-4 ring-blue-100">
                            
                            <h2 class="text-3xl font-bold text-gray-900"><?php echo htmlspecialchars($user_data['full_name'] ?? ''); ?></h2>
                            <p class="text-gray-500 mt-1"><?php echo htmlspecialchars($user_data['email'] ?? ''); ?></p>
                            
                            <span class="mt-4 px-3 py-1 text-sm font-semibold rounded-full <?php echo $status_class; ?>">
                                <?php echo htmlspecialchars($user_data['status_texto']); ?>
                            </span>

                            <div class="mt-6 w-full">
                                <!-- Upload mantém o mesmo endpoint; salve o arquivo como uploads/profile_pics/{id}.ext -->
                                <form action="php/upload_profile_pic.php" method="post" enctype="multipart/form-data">
                                    <input type="hidden" name="user_id" value="<?php echo (int)$user_id; ?>">
                                    <label for="profile_pic_upload" class="w-full cursor-pointer bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-2 px-4 rounded-lg inline-flex items-center justify-center">
                                        <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        <span>Trocar Foto</span>
                                    </label>
                                    <input id="profile_pic_upload" name="profile_pic" type="file" accept=".jpg,.jpeg,.png,.webp" class="hidden" onchange="this.form.submit()">
                                </form>
                            </div>
                        </div>

                        <div class="lg:col-span-2 mt-6 lg:mt-0">
                            <h4 class="text-xl font-semibold text-gray-800 mb-6">Detalhes do Aluno</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
                                
                                <div class="flex items-center space-x-3">
                                    <div class="bg-gray-100 p-2 rounded-lg"><svg class="h-6 w-6 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg></div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-500">Telefone</p>
                                        <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($user_data['phone'] ?? '—'); ?></p>
                                    </div>
                                </div>

                                <div class="flex items-center space-x-3">
                                    <div class="bg-gray-100 p-2 rounded-lg"><svg class="h-6 w-6 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 012-2h2a2 2 0 012 2v1m-4 0h4"/></svg></div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-500">R.U. (Registro Único)</p>
                                        <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($user_data['ru'] ?? '—'); ?></p>
                                    </div>
                                </div>
                                
                                <div class="flex items-center space-x-3">
                                    <div class="bg-gray-100 p-2 rounded-lg"><svg class="h-6 w-6 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg></div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-500">Turma</p>
                                        <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($user_data['turma'] ?? '—'); ?></p>
                                    </div>
                                </div>
                                
                                <div class="flex items-center space-x-3">
                                    <div class="bg-gray-100 p-2 rounded-lg"><svg class="h-6 w-6 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-500">Turno</p>
                                        <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($user_data['turno'] ?? '—'); ?></p>
                                    </div>
                                </div>

                                <div class="flex items-center space-x-3">
                                    <div class="bg-gray-100 p-2 rounded-lg"><svg class="h-6 w-6 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 20.944a11.955 11.955 0 0118 0 12.02 12.02 0 00-2.382-8.984z"/></svg></div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-500">Nível de Acesso</p>
                                        <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($user_data['nivel_acesso_nome']); ?></p>
                                    </div>
                                </div>
                                
                                <div class="flex items-center space-x-3">
                                    <div class="bg-gray-100 p-2 rounded-lg"><svg class="h-6 w-6 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg></div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-500">Membro Desde</p>
                                        <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($user_data['data_cadastro_formatada']); ?></p>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-10 pt-6 border-t border-gray-200 flex justify-end">
                                <button class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-300 transition-colors duration-200 font-semibold flex items-center space-x-2">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.5L16.732 3.732z"/></svg>
                                    <span>Editar Perfil</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="bg-white p-8 rounded-lg shadow-lg text-center max-w-3xl mx-auto">
                    <h2 class="text-2xl font-bold text-red-600">Erro ao Carregar Perfil</h2>
                    <p class="text-gray-600 mt-2">Não foi possível encontrar os dados do seu perfil.</p>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>
