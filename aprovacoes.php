<?php
include_once 'php/auth_check.php';
include_once 'php/conn.php'; // <-- CORRIGIDO

// Apenas representantes (2) e desenvolvedores (3) podem ver esta página
if ($_SESSION['access_level'] < 2) {
    header("Location: index.php?error=unauthorized");
    exit();
}

// Lógica de busca dos alunos pendentes
$sql = "SELECT id, full_name, email, turma, turno, access_level FROM usuarios WHERE status_aluno = 0";
$params = [];
$types = '';

// Se o usuário for um representante (nível 2), filtre por sua turma e turno
if ($_SESSION['access_level'] == 2) {
    $sql .= " AND turma = ? AND turno = ?";
    $params[] = $_SESSION['turma'];
    $params[] = $_SESSION['turno'];
    $types .= 'ss'; // 's' para string (turma), 's' para string (turno)
}

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<?php include 'includes/header.php'; ?>

<body class="bg-gray-100">
    <div class="flex">
        <?php include 'includes/sidebar_nav.php'; // Inclui a barra de navegação lateral ?>
        
        <main class="flex-1 p-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-6">Aprovação de Alunos</h1>

            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <table class="min-w-full leading-normal">
                    <thead>
                        <tr class="bg-gray-200 text-gray-600 uppercase text-sm">
                            <th class="px-5 py-3 border-b-2 border-gray-300 text-left">Nome Completo</th>
                            <th class="px-5 py-3 border-b-2 border-gray-300 text-left">Email</th>
                            <th class="px-5 py-3 border-b-2 border-gray-300 text-left">Turma/Turno</th>
                            <th class="px-5 py-3 border-b-2 border-gray-300 text-center">Nível de Acesso</th>
                            <th class="px-5 py-3 border-b-2 border-gray-300 text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-5 py-4 border-b border-gray-200"><?php echo htmlspecialchars($row['full_name']); ?></td>
                                    <td class="px-5 py-4 border-b border-gray-200"><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td class="px-5 py-4 border-b border-gray-200"><?php echo htmlspecialchars($row['turma'] . ' - ' . $row['turno']); ?></td>
                                    
                                    <td class="px-5 py-4 border-b border-gray-200 text-center">
                                        <form action="php/processar_aprovacao.php" method="POST" class="inline-flex items-center">
                                            <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                            <input type="hidden" name="action" value="mudar_nivel">
                                            <select name="novo_nivel" class="rounded-md border-gray-300 shadow-sm">
                                                <option value="1" <?php if($row['access_level'] == 1) echo 'selected'; ?>>Aluno</option>
                                                <option value="2" <?php if($row['access_level'] == 2) echo 'selected'; ?>>Representante</option>
                                                <?php if ($_SESSION['access_level'] == 3): // Apenas dev pode criar outro dev ?>
                                                    <option value="3" <?php if($row['access_level'] == 3) echo 'selected'; ?>>Desenvolvedor</option>
                                                <?php endif; ?>
                                            </select>
                                            <button type="submit" class="ml-2 px-3 py-1 bg-blue-500 text-white rounded-md hover:bg-blue-600 text-xs">Salvar</button>
                                        </form>
                                    </td>

                                    <td class="px-5 py-4 border-b border-gray-200 text-center">
                                        <form action="php/processar_aprovacao.php" method="POST" class="inline-block">
                                            <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                            <input type="hidden" name="action" value="aprovar">
                                            <button type="submit" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded-lg">
                                                Aprovar
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-10 text-gray-500">Nenhum aluno aguardando aprovação.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>

<?php 
$stmt->close();
$conn->close();
include 'includes/footer.php'; 
?>