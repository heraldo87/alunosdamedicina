<?php
// Arquivo de Conexão com o Banco de Dados (conn.php)

// Definições de credenciais do banco de dados
// ** ATENÇÃO: SUBSTITUA AS CREDENCIAIS ABAIXO PELAS SUAS! **
// É uma boa prática não usar "root" e criar um usuário dedicado para a aplicação.
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'MedinFocus');
define('DB_PASSWORD', 'Her@ldoAlves963#');
define('DB_NAME', 'medinfocus');

// Tentativa de conexão com o banco de dados MySQL
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Verifica a conexão
if ($conn->connect_error) {
    die("Erro na conexão com o banco de dados: " . $conn->connect_error);
}

// Configura o charset para UTF-8 (para evitar problemas com acentuação)
$conn->set_charset("utf8mb4");

?>
