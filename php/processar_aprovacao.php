<?php
include_once 'auth_check.php'; 
include_once 'conn.php'; // <-- CORRIGIDO

// Segurança: Apenas representantes (2) e desenvolvedores (3) podem processar
if ($_SESSION['access_level'] < 2) {
    header("Location: ../index.php?error=unauthorized");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['user_id'])) {
    $user_id = intval($_POST['user_id']);
    $action = $_POST['action'];

    switch ($action) {
        case 'aprovar':
            $sql = "UPDATE usuarios SET status_aluno = 1 WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $user_id);
            break;

        case 'mudar_nivel':
            if (isset($_POST['novo_nivel'])) {
                $novo_nivel = intval($_POST['novo_nivel']);
                // Segurança extra: um representante não pode criar um dev
                if ($_SESSION['access_level'] == 2 && $novo_nivel > 2) {
                    $novo_nivel = 1; // Reseta para aluno se houver tentativa de escalada
                }
                $sql = "UPDATE usuarios SET access_level = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ii", $novo_nivel, $user_id);
            }
            break;
    }

    if (isset($stmt)) {
        $stmt->execute();
        $stmt->close();
    }
}

$conn->close();
// Redireciona de volta para a página de aprovações com uma mensagem de sucesso
header("Location: ../aprovacoes.php?status=success");
exit();

?>