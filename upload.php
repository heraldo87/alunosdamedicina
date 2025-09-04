<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Upload de Planilha para n8n</title>
  <script>
    async function enviarArquivo(event) {
      event.preventDefault();

      const input = document.getElementById("arquivo");
      if (input.files.length === 0) {
        alert("Por favor, selecione uma planilha .xlsx");
        return;
      }

      const formData = new FormData();
      formData.append("file", input.files[0]);

      try {
        const response = await fetch("http://n8n.alunosdamedicina.com/webhook/fa00be73-a83f-4f0e-ab68-81599522c306", {
          method: "POST",
          body: formData
        });

        if (response.ok) {
          const resultado = await response.text();
          alert("Upload realizado com sucesso! Resposta do servidor:\n" + resultado);
        } else {
          alert("Erro ao enviar o arquivo: " + response.statusText);
        }
      } catch (error) {
        alert("Falha na conexão: " + error);
      }
    }
  </script>
  <style>
    body {
      font-family: Arial, sans-serif;
      text-align: center;
      padding: 50px;
      background-color: #f4f4f9;
    }
    form {
      background: #fff;
      padding: 20px;
      border-radius: 12px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
      display: inline-block;
    }
    input[type="file"] {
      margin: 10px 0;
    }
    button {
      background: #4CAF50;
      color: white;
      padding: 10px 20px;
      border: none;
      border-radius: 8px;
      cursor: pointer;
    }
    button:hover {
      background: #45a049;
    }
  </style>
</head>
<body>
  <h2>Upload de Planilha (.xlsx)</h2>
  <form onsubmit="enviarArquivo(event)">
    <input type="file" id="arquivo" accept=".xlsx" required><br>
    <button type="submit">Enviar para n8n</button>
  </form>
</body>
</html>
