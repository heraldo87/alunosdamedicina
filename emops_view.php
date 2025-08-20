<?php
include 'phpview.php';
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

    /* Estilo para todas as células de dados e cabeçalhos */
    .min-w-full th, .min-w-full td {
        padding: 12px 16px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        vertical-align: top;
    }

    /* Larguras específicas para cada coluna (ajustadas para o head atual) */
    .min-w-full th:nth-child(1), .min-w-full td:nth-child(1) { width: 50px; }   /* ID */
    .min-w-full th:nth-child(2), .min-w-full td:nth-child(2) { width: 150px; } /* E-mail */
    .min-w-full th:nth-child(3), .min-w-full td:nth-child(3) { width: 120px; } /* CPF */
    .min-w-full th:nth-child(4), .min-w-full td:nth-child(4) { width: 110px; } /* Data Inicial */
    .min-w-full th:nth-child(5), .min-w-full td:nth-child(5) { width: 110px; } /* Data Final */

    .min-w-full th:nth-child(6), .min-w-full td:nth-child(6) { width: 140px; } /* Ativ. Realizadas */
    .min-w-full th:nth-child(7), .min-w-full td:nth-child(7) { width: 140px; } /* Ativ. Previstas */
    .min-w-full th:nth-child(8), .min-w-full td:nth-child(8) { width: 140px; } /* Pontos Relevantes */
    .min-w-full th:nth-child(9), .min-w-full td:nth-child(9) { width: 160px; } /* Ações */
</style>
    
</head>
<body class="bg-gray-100 flex flex-col items-center min-h-screen p-4">
    
    <div class="bg-white p-8 rounded-lg w-full max-w-full">
        <div class="flex justify-center mb-6">
            <img src="logo-cohidro-emop-cabecalho-form.jpg" alt="COHIDRO / EMOP" class="h-20">
        </div>
    
    <div class="bg-white p-8 rounded-lg shadow-xl w-full max-w-full">
        <h2 class="text-3xl font-bold text-center text-gray-800 mb-8">Gerenciamento de Dados de Atividades</h2>

        <?php if (!empty($message)) : ?>
            <div id="message-box" class="p-4 mb-4 text-sm text-green-700 bg-green-100 rounded-lg text-center" role="alert">
                <span id="message-text"><?= htmlspecialchars($message) ?></span>
            </div>
        <?php endif; ?>

        <!-- Barra de ações: Busca + Exportação -->
        <div class="mb-6 flex flex-col sm:flex-row gap-4 items-center">
            <form action="emops_view.php" method="GET" class="flex flex-grow w-full gap-2">
                <input type="text" name="search" placeholder="Buscar por e-mail, CPF ou ID..." value="<?= htmlspecialchars($search_query) ?>" class="flex-grow p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-300">
                <button type="submit" class="bg-blue-600 text-white font-bold py-3 px-6 rounded-lg hover:bg-blue-700 transition duration-300 shadow-lg">Buscar</button>
            </form>

            <!-- Exportar respeitando o filtro atual -->
            <a href="emops_view.php?action=export&search=<?= urlencode($search_query) ?>"
               class="bg-green-600 text-white font-bold py-3 px-6 rounded-lg hover:bg-green-700 transition duration-300 shadow-lg w-full sm:w-auto text-center">
               Exportar para Excel
            </a>

            <a href="emops_view.php" class="text-blue-600 hover:text-blue-800 transition duration-300 font-semibold mt-2 sm:mt-0">Limpar Busca</a>
        </div>

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
                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo '<tr class="border-b border-gray-200 hover:bg-gray-100 transition duration-300">';
                            echo '<td class="py-3 px-4">' . htmlspecialchars($row['id']) . '</td>';
                            echo '<td class="py-3 px-4">' . htmlspecialchars($row['enviar_por_email']) . '</td>';
                            echo '<td class="py-3 px-4">' . htmlspecialchars($row['cpf']) . '</td>';
                            echo '<td class="py-3 px-4">' . htmlspecialchars($row['data_inicial']) . '</td>';
                            echo '<td class="py-3 px-4">' . htmlspecialchars($row['data_final']) . '</td>';
                            echo '<td class="py-3 px-4">' . nl2br(htmlspecialchars($row['atividades_realizadas'])) . '</td>';
                            echo '<td class="py-3 px-4">' . nl2br(htmlspecialchars($row['atividades_previstas'])) . '</td>';
                            echo '<td class="py-3 px-4">' . nl2br(htmlspecialchars($row['pontos_relevantes'])) . '</td>';
                            echo '<td class="py-3 px-4 flex flex-wrap gap-3 items-center">';

                            // JSON seguro para JS (usado em Editar e Gerar)
                            $json_row = htmlspecialchars(json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8');

                            // Botão Gerar (novo)
                            echo '<button type="button" onclick="gerarArquivo(' . $json_row . ', this)" class="bg-indigo-600 text-white font-semibold text-xs px-3 py-2 rounded hover:bg-indigo-700 transition">Gerar</button>';

                            // Editar
                            echo '<button type="button" onclick="openEditModal(' . $json_row . ')" class="text-blue-600 hover:text-blue-800 transition font-semibold text-sm">Editar</button>';

                            // Deletar
                            echo '<form method="POST" action="emops_view.php" onsubmit="return confirmDelete();" class="inline">';
                            echo '<input type="hidden" name="action" value="delete">';
                            echo '<input type="hidden" name="id" value="' . htmlspecialchars($row['id']) . '">';
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

        <!-- Paginação -->
        <div class="flex justify-center items-center mt-6 flex-wrap gap-2">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="emops_view.php?page=<?= $i ?>&search=<?= urlencode($search_query) ?>" class="mx-1 px-4 py-2 border rounded-lg <?= $page === $i ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    </div>

    <!-- Modal de Edição -->
    <div id="edit-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center p-4">
        <div class="bg-white p-8 rounded-lg shadow-2xl w-full max-w-2xl">
            <h3 class="text-2xl font-bold text-gray-800 mb-6">Editar Registro</h3>
            <form id="edit-form" action="emops_view.php" method="POST">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit-id">

                <div class="mb-4">
                    <label for="edit-email" class="block text-gray-700 text-sm font-semibold mb-2">E-mail:</label>
                    <input type="email" name="enviar_por_email" id="edit-email" class="w-full p-3 border border-gray-300 rounded-lg">
                </div>
                <div class="mb-4">
                    <label for="edit-cpf" class="block text-gray-700 text-sm font-semibold mb-2">CPF:</label>
                    <input type="text" name="cpf" id="edit-cpf" class="w-full p-3 border border-gray-300 rounded-lg">
                </div>
                <div class="mb-4">
                    <label for="edit-data-inicial" class="block text-gray-700 text-sm font-semibold mb-2">Data Inicial:</label>
                    <input type="date" name="data_inicial" id="edit-data-inicial" class="w-full p-3 border border-gray-300 rounded-lg">
                </div>
                <div class="mb-4">
                    <label for="edit-data-final" class="block text-gray-700 text-sm font-semibold mb-2">Data Final:</label>
                    <input type="date" name="data_final" id="edit-data-final" class="w-full p-3 border border-gray-300 rounded-lg">
                </div>
                <div class="mb-4">
                    <label for="edit-atividades-realizadas" class="block text-gray-700 text-sm font-semibold mb-2">Atividades Realizadas:</label>
                    <textarea name="atividades_realizadas" id="edit-atividades-realizadas" rows="4" class="w-full p-3 border border-gray-300 rounded-lg"></textarea>
                </div>
                <div class="mb-4">
                    <label for="edit-atividades-previstas" class="block text-gray-700 text-sm font-semibold mb-2">Atividades Previstas:</label>
                    <textarea name="atividades_previstas" id="edit-atividades-previstas" rows="4" class="w-full p-3 border border-gray-300 rounded-lg"></textarea>
                </div>
                <div class="mb-6">
                    <label for="edit-pontos-relevantes" class="block text-gray-700 text-sm font-semibold mb-2">Pontos Relevantes:</label>
                    <textarea name="pontos_relevantes" id="edit-pontos-relevantes" rows="4" class="w-full p-3 border border-gray-300 rounded-lg"></textarea>
                </div>

                <div class="flex justify-end gap-4">
                    <button type="button" onclick="closeEditModal()" class="bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded-lg hover:bg-gray-400 transition">Cancelar</button>
                    <button type="submit" class="bg-blue-600 text-white font-bold py-2 px-4 rounded-lg hover:bg-blue-700 transition">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>

    <?php
    // Fecha a conexão
    $conn->close();
    ?>

    <script>
        // ====== CONFIG ======
        // Use URL relativa para evitar mixed content (https/http).
        const WEBHOOK_URL = "/webhook/3115875d-c214-4c77-b6e6-0b5c7ac3b5bd";
        // Se quiser forçar a URL absoluta (pode causar bloqueio em páginas https):
        // const WEBHOOK_URL = "http://alunosdamedicina.com/webhook/3115875d-c214-4c77-b6e6-0b5c7ac3b5bd";

        // Abre o modal e preenche os campos
        function openEditModal(data) {
            document.getElementById('edit-id').value = data.id ?? '';
            document.getElementById('edit-email').value = data.enviar_por_email ?? '';
            document.getElementById('edit-cpf').value = data.cpf ?? '';
            document.getElementById('edit-data-inicial').value = data.data_inicial ?? '';
            document.getElementById('edit-data-final').value = data.data_final ?? '';
            document.getElementById('edit-atividades-realizadas').value = (data.atividades_realizadas ?? '').toString().replaceAll('<br />', '\n');
            document.getElementById('edit-atividades-previstas').value = (data.atividades_previstas ?? '').toString().replaceAll('<br />', '\n');
            document.getElementById('edit-pontos-relevantes').value = (data.pontos_relevantes ?? '').toString().replaceAll('<br />', '\n');

            document.getElementById('edit-modal').classList.remove('hidden');
            document.getElementById('edit-modal').classList.add('flex');
        }

        // Fecha o modal
        function closeEditModal() {
            const modal = document.getElementById('edit-modal');
            modal.classList.remove('flex');
            modal.classList.add('hidden');
        }

        // Confirmação de exclusão
        function confirmDelete() {
            return window.confirm("Você tem certeza que deseja deletar este registro?");
        }

        // Esconde mensagem de feedback após 5s
        setTimeout(() => {
            const messageBox = document.getElementById('message-box');
            if (messageBox) { messageBox.classList.add('hidden'); }
        }, 5000);

        // ====== NOVO: Gerar e baixar arquivo a partir do n8n ======
        async function gerarArquivo(rowData, btnEl) {
            // Payload que você quiser enviar ao n8n
            const payload = {
                id: rowData.id ?? null,
                email: rowData.enviar_por_email ?? null,
                cpf: rowData.cpf ?? null,
                data_inicial: rowData.data_inicial ?? null,
                data_final: rowData.data_final ?? null,
                atividades_realizadas: rowData.atividades_realizadas ?? null,
                atividades_previstas: rowData.atividades_previstas ?? null,
                pontos_relevantes: rowData.pontos_relevantes ?? null,
                _source: "emops_view.php"
            };

            const originalLabel = btnEl.textContent;
            btnEl.disabled = true;
            btnEl.textContent = "Gerando…";

            const controller = new AbortController();
            const timer = setTimeout(() => controller.abort(), 120000); // 120s

            try {
                const resp = await fetch(WEBHOOK_URL, {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify(payload),
                    signal: controller.signal
                });

                if (!resp.ok) {
                    // Tenta ler erro como texto/JSON para depuração
                    let msg = `Falha na geração (HTTP ${resp.status})`;
                    try {
                        const t = await resp.text();
                        if (t) msg += `\n${t.substring(0, 500)}`;
                    } catch (_) {}
                    throw new Error(msg);
                }

                // Se o fluxo devolver JSON com link, trate aqui:
                const contentType = resp.headers.get("content-type") || "";
                if (contentType.includes("application/json")) {
                    const data = await resp.json();
                    if (data && data.download_url) {
                        // Abre o link ou força download
                        window.open(data.download_url, "_blank");
                        showToast("Arquivo gerado com sucesso (link).");
                        return;
                    } else {
                        // Pode ser que o fluxo esteja retornando um JSON de erro
                        throw new Error("Resposta JSON sem arquivo. Ajuste o fluxo do n8n para retornar o binário ou um 'download_url'.");
                    }
                }

                // Caso o fluxo já retorne o binário (PDF/XLSX/ZIP etc)
                const blob = await resp.blob();

                // Tenta inferir o nome do arquivo
                const disp = resp.headers.get("content-disposition") || "";
                let filename = getFilenameFromDisposition(disp);
                if (!filename) {
                    const ext = guessExtFromMime(contentType);
                    filename = `arquivo_${(rowData.id ?? "gerado")}.${ext}`;
                }

                triggerDownload(blob, filename);
                showToast("Arquivo gerado e baixado com sucesso!");
            } catch (err) {
                console.error(err);
                alert("Não foi possível gerar o arquivo.\n\n" + (err?.message || err));
            } finally {
                clearTimeout(timer);
                btnEl.disabled = false;
                btnEl.textContent = originalLabel;
            }
        }

        function getFilenameFromDisposition(disposition) {
            // Procura filename*=UTF-8''... OU filename="..."
            const utf8 = /filename\*\s*=\s*UTF-8''([^;]+)/i.exec(disposition);
            if (utf8 && utf8[1]) {
                try { return decodeURIComponent(utf8[1]); } catch (_) { return utf8[1]; }
            }
            const simple = /filename\s*=\s*"([^"]+)"/i.exec(disposition) || /filename\s*=\s*([^;]+)/i.exec(disposition);
            if (simple && simple[1]) return simple[1].trim().replace(/^["']|["']$/g, "");
            return "";
        }

        function guessExtFromMime(mime) {
            if (mime.includes("pdf")) return "pdf";
            if (mime.includes("spreadsheet") || mime.includes("excel")) return "xlsx";
            if (mime.includes("zip")) return "zip";
            if (mime.includes("msword") || mime.includes("wordprocessingml")) return "docx";
            if (mime.includes("json")) return "json";
            return "bin";
        }

        function triggerDownload(blob, filename) {
            const url = URL.createObjectURL(blob);
            const a = document.createElement("a");
            a.href = url;
            a.download = filename || "download";
            document.body.appendChild(a);
            a.click();
            a.remove();
            URL.revokeObjectURL(url);
        }

        function showToast(text) {
            // Reutiliza a caixa de mensagem existente
            let box = document.getElementById('message-box');
            let span = document.getElementById('message-text');
            if (!box) {
                box = document.createElement('div');
                box.id = 'message-box';
                box.className = 'p-4 mb-4 text-sm text-green-700 bg-green-100 rounded-lg text-center';
                span = document.createElement('span');
                span.id = 'message-text';
                box.appendChild(span);
                const container = document.querySelector('.bg-white.p-8.rounded-lg.shadow-xl');
                container?.insertBefore(box, container.firstChild);
            }
            span.textContent = text;
            box.classList.remove('hidden');
            setTimeout(() => box.classList.add('hidden'), 5000);
        }
    </script>
</body>
</html>
