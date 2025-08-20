<?php
// =============================
// Configurações do Banco
// =============================
$servername = "localhost";
$username   = "MedinFocus";
$password   = "Her@ldoAlves963#";
$dbname     = "medinfocus";

// Cria a conexão
$conn = new mysqli($servername, $username, $password, $dbname);

// Verifica a conexão
if ($conn->connect_error) {
    die("A conexão falhou: " . $conn->connect_error);
}

// =============================
// Exportação CSV (abre no Excel)
// =============================
// Observação: este bloco vem ANTES da paginação/listagem.
// Ele respeita o mesmo filtro de busca da página.
if (isset($_GET['action']) && $_GET['action'] === 'export') {
    $search_query = isset($_GET['search']) ? $_GET['search'] : '';

    $export_sql = "SELECT `id`, `enviar_por_email`, `cpf`, `id_colaborador`, `data_inicial`, `data_final`, 
                          `atividades_realizadas`, `atividades_previstas`, `pontos_relevantes`, `data_registro`
                   FROM `acompanhamento_atividades`";
    $has_search = !empty($search_query);
    if ($has_search) {
        $export_sql .= " WHERE `enviar_por_email` LIKE ? OR `cpf` LIKE ? OR `id_colaborador` LIKE ? OR `atividades_realizadas` LIKE ?";
    }
    $export_sql .= " ORDER BY `id` DESC";

    if ($has_search) {
        $stmt_export = $conn->prepare($export_sql);
        $search_term = "%".$search_query."%";
        $stmt_export->bind_param("ssss", $search_term, $search_term, $search_term, $search_term);
        $stmt_export->execute();
        $export_result = $stmt_export->get_result();
    } else {
        $export_result = $conn->query($export_sql);
    }

    // Headers para download
    $filename = "acompanhamento_atividades_" . date("Y-m-d_H-i-s") . ".csv";
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="'.$filename.'"');

    // BOM UTF-8 (para acentuação correta no Excel)
    echo "\xEF\xBB\xBF";

    $out = fopen('php://output', 'w');

    // Cabeçalhos das colunas
    fputcsv($out, [
        'ID', 'E-mail', 'CPF', 'ID Colaborador', 'Data Inicial', 'Data Final',
        'Atividades Realizadas', 'Atividades Previstas', 'Pontos Relevantes', 'Data Registro'
    ]);

    // Linhas
    if ($export_result && $export_result->num_rows > 0) {
        while ($r = $export_result->fetch_assoc()) {
            // Normaliza quebras de linha para não quebrar células no CSV
            $ar = str_replace(["\r\n", "\n", "\r"], ' | ', $r['atividades_realizadas'] ?? '');
            $ap = str_replace(["\r\n", "\n", "\r"], ' | ', $r['atividades_previstas'] ?? '');
            $pr = str_replace(["\r\n", "\n", "\r"], ' | ', $r['pontos_relevantes'] ?? '');

            fputcsv($out, [
                $r['id'],
                $r['enviar_por_email'],
                $r['cpf'],
                $r['id_colaborador'],
                $r['data_inicial'],
                $r['data_final'],
                $ar,
                $ap,
                $pr,
                $r['data_registro'],
            ]);
        }
    }

    if (isset($stmt_export)) { $stmt_export->close(); }
    fclose($out);
    // Importante: encerra a execução para não renderizar o HTML
    exit;
}

// =============================
// Lógica de Ações (Deletar, Editar)
// =============================
$message = "";

// Deletar
if (isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = $_POST['id'];
    $stmt = $conn->prepare("DELETE FROM `acompanhamento_atividades` WHERE `id` = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $message = "Registro deletado com sucesso!";
    } else {
        $message = "Erro ao deletar registro: " . $stmt->error;
    }
    $stmt->close();
}

// Editar
if (isset($_POST['action']) && $_POST['action'] === 'update') {
    $id                    = $_POST['id'];
    $email                 = $_POST['enviar_por_email'];
    $cpf                   = $_POST['cpf'];
    $id_colaborador        = $_POST['id_colaborador'];
    $data_inicial          = $_POST['data_inicial'];
    $data_final            = $_POST['data_final'];
    $atividades_realizadas = $_POST['atividades_realizadas'];
    $atividades_previstas  = $_POST['atividades_previstas'];
    $pontos_relevantes     = $_POST['pontos_relevantes'];

    $sql = "UPDATE `acompanhamento_atividades` SET 
            `enviar_por_email` = ?, 
            `cpf` = ?, 
            `id_colaborador` = ?, 
            `data_inicial` = ?, 
            `data_final` = ?, 
            `atividades_realizadas` = ?, 
            `atividades_previstas` = ?, 
            `pontos_relevantes` = ? 
            WHERE `id` = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "ssisssssi",
        $email, $cpf, $id_colaborador, $data_inicial, $data_final,
        $atividades_realizadas, $atividades_previstas, $pontos_relevantes, $id
    );
    
    if ($stmt->execute()) {
        $message = "Registro atualizado com sucesso!";
    } else {
        $message = "Erro ao atualizar registro: " . $stmt->error;
    }
    $stmt->close();
}

// =============================
// Paginação e Busca
// =============================
$records_per_page = 10;
$page   = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;
$search_query = isset($_GET['search']) ? $_GET['search'] : '';

// Conta total
$count_sql = "SELECT COUNT(*) FROM `acompanhamento_atividades`";
if (!empty($search_query)) {
    $count_sql .= " WHERE `enviar_por_email` LIKE ? OR `cpf` LIKE ? OR `id_colaborador` LIKE ? OR `atividades_realizadas` LIKE ?";
    $stmt_count = $conn->prepare($count_sql);
    $search_term = "%" . $search_query . "%";
    $stmt_count->bind_param("ssss", $search_term, $search_term, $search_term, $search_term);
    $stmt_count->execute();
    $result_count = $stmt_count->get_result();
} else {
    $result_count = $conn->query($count_sql);
}
$total_records = $result_count ? (int)$result_count->fetch_row()[0] : 0;
$total_pages   = max(1, (int)ceil($total_records / $records_per_page));

// Query principal
$sql = "SELECT `id`, `enviar_por_email`, `cpf`, `id_colaborador`, `data_inicial`, `data_final`, 
               `atividades_realizadas`, `atividades_previstas`, `pontos_relevantes`, `data_registro` 
        FROM `acompanhamento_atividades`";
$has_search = !empty($search_query);
if ($has_search) {
    $sql .= " WHERE `enviar_por_email` LIKE ? OR `cpf` LIKE ? OR `id_colaborador` LIKE ? OR `atividades_realizadas` LIKE ?";
}
$sql .= " ORDER BY `id` DESC LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);
if ($has_search) {
    $search_term = "%" . $search_query . "%";
    $stmt->bind_param("ssssii", $search_term, $search_term, $search_term, $search_term, $records_per_page, $offset);
} else {
    $stmt->bind_param("ii", $records_per_page, $offset);
}
$stmt->execute();
$result = $stmt->get_result();
?>