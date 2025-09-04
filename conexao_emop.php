<?php
$servername = "localhost";
$username   = "usuario_banco";
$password   = "senha_banco";
$dbname     = "meu_banco";

// Criar conexão
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}
?>