<?php
include 'phpview.php';
require_once 'php/conn.php';

// Helpers
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function nl2br_h($s){ return nl2br(h($s ?? '')); }

// ===== AÇÃO: DOWNLOAD (SEM CONSULTA AO BANCO) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'download') {

    // Recebe todos os campos da própria linha (enviados como hidden)
    $aluno = [
        'id'                     => $_POST['id'] ?? '',
        'enviar_por_email'       => $_POST['enviar_por_email'] ?? ($_POST['email'] ?? ''),
        'cpf'                    => $_POST['cpf'] ?? '',
        'data_inicial'           => $_POST['data_inicial'] ?? '',
        'data_final'             => $_POST['data_final'] ?? '',
        'atividades_realizadas'  => $_POST['atividades_realizadas'] ?? '',
        'atividades_previstas'   => $_POST['atividades_previstas'] ?? '',
        'pontos_relevantes'      => $_POST['pontos_relevantes'] ?? '',
    ];

    if ($aluno['id'] === '' && $aluno['cpf'] === '') {
        http_response_code(400);
        die("ID ou CPF não informado.");
    }

    $html = "<!DOCTYPE html>
<html lang='pt-br'>
<head>
  <meta charset='UTF-8'>
  <title>Relatório de Atividades.".h($aluno['cpf'] ?: $aluno['id'])."</title>
  <style>
    body { font-family: Arial, sans-serif; padding: 20px; color:#2c3e50; }
    h1 { color: #2c3e50; margin-bottom: 8px; }
    h2 { margin-top: 24px; }
    p  { line-height: 1.4; }
    .kv p { margin: 4px 0; }
    .box { border:1px solid #ddd; padding:12px; border-radius:8px; background:#fafafa; }
  </style>
</head>
<body>
  <h1>Dados do Calaborador</h1>
  <div class='kv box'>
    <p><strong>ID:</strong> ".h($aluno['id'])."</p>
    <p><strong>E-mail:</strong> ".h($aluno['enviar_por_email'])."</p>
    <p><strong>CPF:</strong> ".h($aluno['cpf'])."</p>
    <p><strong>Data Inicial:</strong> ".h($aluno['data_inicial'])."</p>
    <p><strong>Data Final:</strong> ".h($aluno['data_final'])."</p>
  </div>

  <h2>Atividades</h2>
  <div class='box'>
    <p><strong>Realizadas:</strong><br>".nl2br_h($aluno['atividades_realizadas'])."</p>
    <p><strong>Previstas:</strong><br>".nl2br_h($aluno['atividades_previstas'])."</p>
    <p><strong>Pontos Relevantes:</strong><br>".nl2br_h($aluno['pontos_relevantes'])."</p>
  </div>
</body>
</html>";

    $slug = $aluno['cpf'] ?: ('id_'.$aluno['id']);
    $filename = "relatorio_".preg_replace('/[^0-9A-Za-z_-]+/', '', $slug).".html";

    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    header('Content-Length: '.strlen($html));
    echo $html;
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciamento de Dados</title>
    <script src="https://cdn.tailwindcss.com"></script>
<style>
    body { font-family: 'Inter', sans-serif; }
    .table-container { overflow-x: auto; -webkit-overflow-scrolling: touch; }
    .min-w-full th, .min-w-full td { padding: 12px 16px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; vertical-align: top; }
    .min-w-full th:nth-child(1), .min-w-full td:nth-child(1) { width: 60px; }   /* ID */
    .min-w-full th:nth-child(2), .min-w-full td:nth-child(2) { width: 180px; } /* E-mail */
    .min-w-full th:nth-child(3), .min-w-full td:nth-child(3) { width: 140px; } /* CPF */
    .min-w-full th:nth-child(4), .min-w-full td:nth-child(4) { width: 120px; } /* Data Inicial */
    .min-w-full th:nth-child(5), .min-w-full td:nth-child(5) { width: 120px; } /* Data Final */
    .min-w-full th:nth-child(6), .min-w-full td:nth-child(6) { width: 220px; } /* Ativ. Realizadas */
    .min-w-full th:nth-child(7), .min-w-full td:nth-child(7) { width: 220px; } /* Ativ. Previstas */
    .min-w-full th:nth-child(8), .min-w-full td:nth-child(8) { width: 220px; } /* Pontos Relevantes */
    .min-w-full th:nth-child(9), .min-w-full td:nth-child(9) { width: 180px; } /* Ações */
</style>
</head>
<body class="bg-gray-100 flex flex-col items-center min-h-screen p-4">

<div class="bg-white p-8 rounded-lg w-full max-w-full">
    <h2 class="text-3xl font-bold text-center text-gray-800 mb-8">Gerenciamento de Dados de Atividades</h2>

    <div class="table-container">
        <table class="min-w-full bg-white rounded-lg shadow-md overflow-hidden">
            <thead class="bg-blue-600 text-white">
                <tr>
                    <th class="py-3 px-4 text-left font-semibold text-sm">ID</th>
                    <th class="py-3 px-4 text-left font-semibold text-sm">E-mail</th>
                    <th class="py-3 px-4 text-left font-semibold text-sm">CPF</th>
                    <th class="py-3 px-4 text-left font-semibold text-sm">Data Inicial</th>
                    <th class="py-3 px-4 text-left font-semibold text-sm">Data Final</th>
                    <th class="py-3 px-4 text-left font-semibold text-sm">Ativ. Realizadas</th>
                    <th class="py-3 px-4 text-left font-semibold text-sm">Ativ. Previstas</th>
                    <th class="py-3 px-4 text-left font-semibold text-sm">Pontos Relevantes</th>
                    <th class="py-3 px-4 text-left font-semibold text-sm">Ações</th>
                </tr>
            </thead>
            <tbody class="text-gray-700">
                <?php
                if (isset($result) && $result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo '<tr class="border-b border-gray-200 hover:bg-gray-100 transition duration-300">';
                        echo '<td class="py-3 px-4">'.h($row['id']).'</td>';
                        echo '<td class="py-3 px-4">'.h($row['enviar_por_email']).'</td>';
                        echo '<td class="py-3 px-4">'.h($row['cpf']).'</td>';
                        echo '<td class="py-3 px-4">'.h($row['data_inicial']).'</td>';
                        echo '<td class="py-3 px-4">'.h($row['data_final']).'</td>';
                        echo '<td class="py-3 px-4">'.nl2br_h($row['atividades_realizadas']).'</td>';
                        echo '<td class="py-3 px-4">'.nl2br_h($row['atividades_previstas']).'</td>';
                        echo '<td class="py-3 px-4">'.nl2br_h($row['pontos_relevantes']).'</td>';
                        
                        // Consolida todos os botões de ação em uma única célula
                        echo '<td class="py-3 px-4 flex gap-2 flex-wrap">';
                        
                        // Formulário de download HTML
                        echo '<form method="POST" action="" target="_blank" class="inline">';
                        echo '  <input type="hidden" name="action" value="download">';
                        echo '  <input type="hidden" name="id" value="'.h($row['id']).'">';
                        echo '  <input type="hidden" name="enviar_por_email" value="'.h($row['enviar_por_email']).'">';
                        echo '  <input type="hidden" name="cpf" value="'.h($row['cpf']).'">';
                        echo '  <input type="hidden" name="data_inicial" value="'.h($row['data_inicial']).'">';
                        echo '  <input type="hidden" name="data_final" value="'.h($row['data_final']).'">';
                        echo '  <input type="hidden" name="atividades_realizadas" value="'.h($row['atividades_realizadas']).'">';
                        echo '  <input type="hidden" name="atividades_previstas" value="'.h($row['atividades_previstas']).'">';
                        echo '  <input type="hidden" name="pontos_relevantes" value="'.h($row['pontos_relevantes']).'">';
                        echo '  <button type="submit" class="bg-green-600 text-white font-semibold text-xs px-3 py-2 rounded hover:bg-green-700 transition">Gerar Relatório</button>';
                        echo '</form>';
                        
                        // Botão de editar
                        $json_row = htmlspecialchars(json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8');
                        echo '<button type="button" onclick="openEditModal(' . $json_row . ')" class="text-blue-600 hover:text-blue-800 transition font-semibold text-sm">Editar</button>';

                        // Formulário de deletar
                        echo '<form method="POST" action="emops_view.php" onsubmit="return confirmDelete();" class="inline">';
                        echo '<input type="hidden" name="action" value="delete">';
                        echo '<input type="hidden" name="id" value="' . h($row['id']) . '">';
                        echo '<button type="submit" class="text-red-600 hover:text-red-800 transition font-semibold text-sm">Deletar</button>';
                        echo '</form>';
                        
                        echo '</td>';

                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="9" class="py-3 px-4 text-center text-gray-500">Nenhum dado encontrado.</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>