<?php
// Reutilizamos a lógica do ficheiro de cliente, mas ajustamos o redirecionamento.
require_once '../includes/db_connect.php';

// Limpar e destruir a sessão
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// Redirecionar para a página de login do Admin
header('Location: login_admin.php');
exit();
?>