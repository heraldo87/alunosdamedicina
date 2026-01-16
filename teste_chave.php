<?php
$arquivo = '/www/wwwroot/alunosdamedicina.com/includes/keys/credentials.json';

echo "<h2>Diagnóstico de Arquivo</h2>";
echo "Caminho testado: <strong>$arquivo</strong><br><br>";

if (file_exists($arquivo)) {
    echo "<span style='color:green'>✅ O arquivo EXISTE.</span><br>";
    
    if (is_readable($arquivo)) {
        echo "<span style='color:green'>✅ O PHP tem PERMISSÃO de leitura.</span><br>";
        $conteudo = file_get_contents($arquivo);
        $json = json_decode($conteudo, true);
        if(isset($json['type']) && $json['type'] === 'service_account') {
             echo "<span style='color:green'>✅ O JSON parece válido (Service Account).</span>";
        } else {
             echo "<span style='color:red'>❌ O arquivo existe mas o conteúdo JSON é inválido.</span>";
        }
    } else {
        echo "<span style='color:red'>❌ O PHP NÃO consegue ler (Erro de Permissão - rode o chown).</span>";
    }
} else {
    echo "<span style='color:red'>❌ O PHP diz que o arquivo NÃO EXISTE. Verifique o nome/caminho.</span>";
}
?>