<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Download de Relatório</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      text-align: center;
      padding: 50px;
    }
    button {
      background: #4CAF50;
      color: white;
      padding: 15px 25px;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      font-size: 16px;
    }
    button:hover {
      background: #45a049;
    }
  </style>
</head>
<body>
  <h1>Gerar Relatório</h1>
  <p>Clique no botão para baixar o relatório em XLSX.</p>
  <button id="baixar">📥 Baixar Relatório</button>

  <script>
    document.getElementById("baixar").addEventListener("click", async () => {
      try {
        // 🚨 Troque esta URL pelo seu Webhook de PRODUÇÃO (sem "-test")
        const url = "https://n8n.alunosdamedicina.com/webhook/3115875d-c214-4c77-b6e6-0b5c7ac3b5bd";

        const response = await fetch(url, { method: "POST" });
        if (!response.ok) throw new Error("Erro ao gerar relatório");

        const blob = await response.blob();
        const link = document.createElement("a");
        link.href = URL.createObjectURL(blob);
        link.download = "relatorio.xlsx"; // nome do arquivo que será baixado
        link.click();
      } catch (err) {
        alert("Falha ao baixar relatório: " + err.message);
      }
    });
  </script>
</body>
</html>
