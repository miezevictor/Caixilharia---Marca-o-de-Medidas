<?php
// Incluir a conexão à base de dados e iniciar a sessão
require_once 'includes/db_connect.php';

// Se o cliente já estiver logado, redirecionar para a área de cliente
if (isset($_SESSION['cliente_id'])) {
    header('Location: cliente/minhas_marcacoes.php');
    exit();
}

$mensagem = '';

// 1. Processamento do Formulário de Login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validação básica
    if (empty($email) || empty($password)) {
        $mensagem = '<div class="alerta erro">Por favor, insira o email e a password.</div>';
    } else {
        try {
            // 2. Procurar o utilizador pelo email na base de dados
            $sql = "SELECT id_cliente, nome, password FROM utilizadores WHERE email = :email";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':email' => $email]);
            $cliente = $stmt->fetch();

            // 3. Verificar se o utilizador existe e a password
            if ($cliente && password_verify($password, $cliente['password'])) {
                
                // Login bem-sucedido!
                // 4. Configurar a sessão
                $_SESSION['cliente_id'] = $cliente['id_cliente'];
                $_SESSION['cliente_nome'] = $cliente['nome'];
                
                // 5. Redirecionar para a área restrita
                header('Location: cliente/minhas_marcacoes.php');
                exit();

            } else {
                $mensagem = '<div class="alerta erro">Email ou Password incorretos.</div>';
            }

        } catch (\PDOException $e) {
            $mensagem = '<div class="alerta erro">Ocorreu um erro no login. Tente novamente.</div>';
            // Para debug: $mensagem .= " Erro: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Login de Cliente - Sistema de Marcações</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .container { background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); width: 350px; }
        h2 { text-align: center; color: #333; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="email"], input[type="password"] { width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { background-color: #007bff; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; width: 100%; font-size: 16px; }
        button:hover { background-color: #0056b3; }
        .alerta { padding: 10px; margin-bottom: 15px; border-radius: 4px; text-align: center; }
        .sucesso { background-color: #dff0d8; color: #3c763d; border: 1px solid #d6e9c6; }
        .erro { background-color: #f2dede; color: #a94442; border: 1px solid #ebccd1; }
        .link-cadastro { text-align: center; margin-top: 15px; }
    </style>
</head>
<body>

<div class="container">
    <h2>Login de Cliente</h2>
    
    <?php echo $mensagem; // Exibe a mensagem de erro ?>

    <form method="POST" action="index.php">
        
        <label for="email">Email</label>
        <input type="email" id="email" name="email" required>

        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>

        <button type="submit">Iniciar Sessão</button>
    </form>
    
    <p class="link-cadastro">Ainda não tem conta? <a href="cadastro.php">Registe-se aqui</a></p>
</div>

</body>
</html>