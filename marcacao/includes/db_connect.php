<?php
// Configuração da Base de Dados
define('DB_HOST', 'localhost');
define('DB_USER', 'root'); // O utilizador padrão do XAMPP/WAMP
define('DB_PASS', '');     // A password padrão do XAMPP/WAMP
define('DB_NAME', 'gestao_marcacoes_db'); // O nome que você definiu

/**
 * Função para estabelecer a conexão PDO
 * @return PDO|null O objeto de conexão ou null em caso de erro.
 */
function connectDB() {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (\PDOException $e) {
        // Em um sistema real, você registraria o erro em um log,
        // mas por enquanto, vamos apenas exibi-lo.
        die("Erro na conexão com a base de dados: " . $e->getMessage());
        return null;
    }
}

// Iniciar a conexão
$pdo = connectDB();

// Opcional: Iniciar a sessão (necessário para login/cadastro)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}