<?php
// =============================
// Conexão (inclua seu arquivo de conexão se preferir)
// =============================
$servername = "localhost";
$username   = "MedinFocus";
$password   = "Her@ldoAlves963#";
$dbname     = "medinfocus";
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("A conexão falhou: " . $conn->connect_error);
}

// =============================
// Paginação e Busca
// =============================
$records_per_page = 10;
$page   = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// =============================
// Contagem de registros (últimos 30 dias)
// =============================
$count_sql = "SELECT COUNT(*) 
              FROM `acompanhamento_atividades`
              WHERE `data_registro` >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
if (!empty($search_query)) {
    $count_sql .= " AND (`enviar_por_email` LIKE ? OR `cpf` LIKE ? OR `id_colaborador` LIKE ? OR `atividades_realizadas` LIKE ?)";
    $stmt_count = $conn->prepare($count_sql);
    $search_term = "%".$search_query."%";
    $stmt_count->bind_param("ssss", $search_term, $search_term, $search_term, $search_term);
    $stmt_count->execute();
    $result_count = $stmt_count->get_result();
} else {
    $result_count = $conn->query($count_sql);
}
$total_records = $result_count ? (int)$result_count->fetch_row()[0] : 0;
$total_pages   = max(1, (int)ceil($total_records / $records_per_page));

// =============================
// Query principal (últimos 30 dias)
// =============================
$sql = "SELECT `id`, `enviar_por_email`, `cpf`, `id_colaborador`, 
               `data_inicial`, `data_final`, `atividades_realizadas`, 
               `atividades_previstas`, `pontos_relevantes`, `data_registro` 
        FROM `acompanhamento_atividades`
        WHERE `data_registro` >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";

if (!empty($search_query)) {
    $sql .= " AND (`enviar_por_email` LIKE ? OR `cpf` LIKE ? OR `id_colaborador` LIKE ? OR `atividades_realizadas` LIKE ?)";
}
$sql .= " ORDER BY `id` DESC LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);
if (!empty($search_query)) {
    $stmt->bind_param("ssssii", $search_term, $search_term, $search_term, $search_term, $records_per_page, $offset);
} else {
    $stmt->bind_param("ii", $records_per_page, $offset);
}
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Acompanhamento - Últimos 30 dias</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f9f9f9;
            margin: 20px;
        }
        h2 {
            color: #333;
        }
        .search-box {
            margin-bottom: 15px;
        }
        .search-box input[type=text] {
            padding: 6px;
            width: 250px;
        }
        .search-box button {
            padding: 6px 12px;
            background: #007BFF;
            border: none;
            color: #fff;
            cursor: pointer;
        }
        .search-box button:hover {
            background: #0056b3;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            box-shadow: 0px 0px 5px #ccc;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            font-size: 14px;
        }
        th {
            background: #007BFF;
            color: white;
        }
        tr:nth-child(even) {
            background: #f2f2f2;
        }
        .pagination {
            margin-top: 15px;
            text-align: center;
        }
        .pagination a {
            padding: 8px 12px;
            margin: 0 3px;
            background: #007BFF;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
        .pagination a.active {
            background: #0056b3;
        }
        .pagination a:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <h2>Acompanhamento de Atividades (Últimos 30 dias)</h2>

    <div class="search-box">
        <form method="get">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="Buscar...">
            <button type="submit">Buscar</button>
        </form>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>E-mail</th>
                <th>CPF</th>
                <th>ID Colaborador</th>
                <th>Data Inicial</th>
                <th>Data Final</th>
                <th>Atividades Realizadas</th>
                <th>Atividades Previstas</th>
                <th>Pontos Relevantes</th>
                <th>Data Registro</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo htmlspecialchars($row['enviar_por_email']); ?></td>
                    <td><?php echo htmlspecialchars($row['cpf']); ?></td>
                    <td><?php echo htmlspecialchars($row['id_colaborador']); ?></td>
                    <td><?php echo htmlspecialchars($row['data_inicial']); ?></td>
                    <td><?php echo htmlspecialchars($row['data_final']); ?></td>
                    <td><?php echo nl2br(htmlspecialchars($row['atividades_realizadas'])); ?></td>
                    <td><?php echo nl2br(htmlspecialchars($row['atividades_previstas'])); ?></td>
                    <td><?php echo nl2br(htmlspecialchars($row['pontos_relevantes'])); ?></td>
                    <td><?php echo htmlspecialchars($row['data_registro']); ?></td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="10">Nenhum registro encontrado nos últimos 30 dias.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search_query); ?>">« Anterior</a>
        <?php endif; ?>

        <?php for ($i=1; $i <= $total_pages; $i++): ?>
            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search_query); ?>" class="<?php echo ($i == $page ? 'active' : ''); ?>">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>

        <?php if ($page < $total_pages): ?>
            <a href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search_query); ?>">Próxima »</a>
        <?php endif; ?>
    </div>
</body>
</html>
