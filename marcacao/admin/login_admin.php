<?php
// Incluir a conexão à base de dados e iniciar a sessão
require_once '../includes/db_connect.php';

// Se o Admin já estiver logado, redirecionar para o Dashboard
if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit();
}

$mensagem = '';

// 1. Processamento do Formulário de Login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $mensagem = '<div class="alerta erro">Por favor, insira o Nome de Utilizador e a Password.</div>';
    } else {
        try {
            // 2. Procurar o utilizador Admin pelo username na base de dados
            $sql = "SELECT id_admin, username, password FROM admin WHERE username = :username";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':username' => $username]);
            $admin = $stmt->fetch();

            // 3. Verificar se o utilizador existe e a password
            if ($admin && password_verify($password, $admin['password'])) {
                
                // Login bem-sucedido!
                // 4. Configurar a sessão ADMIN
                $_SESSION['admin_id'] = $admin['id_admin'];
                $_SESSION['admin_username'] = $admin['username'];
                
                // 5. Redirecionar para o Dashboard
                header('Location: dashboard.php');
                exit();

            } else {
                $mensagem = '<div class="alerta erro">Nome de Utilizador ou Password incorretos.</div>';
            }

        } catch (\PDOException $e) {
            $mensagem = '<div class="alerta erro">Ocorreu um erro no login. Tente novamente.</div>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Login Administrativo</title>
    <link rel="stylesheet" href="../css/style.css"> 
    <style>
        body { font-family: Arial, sans-serif; background-color: #3f51b5; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .container { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2); width: 350px; }
        h2 { text-align: center; color: #3f51b5; margin-bottom: 25px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        input[type="text"], input[type="password"] { width: 100%; padding: 12px; margin-bottom: 20px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { background-color: #3f51b5; color: white; padding: 12px 15px; border: none; border-radius: 4px; cursor: pointer; width: 100%; font-size: 16px; transition: background-color 0.3s; }
        button:hover { background-color: #303f9f; }
        .alerta { padding: 10px; margin-bottom: 15px; border-radius: 4px; text-align: center; font-weight: bold; }
        .erro { background-color: #f2dede; color: #a94442; border: 1px solid #ebccd1; }
    </style>
</head>
<body>

<div class="container">
    <h2>Login Administrativo</h2>
    
    <?php echo $mensagem; // Exibe a mensagem de erro ?>

    <form method="POST" action="login_admin.php">
        
        <label for="username">Nome de Utilizador</label>
        <input type="text" id="username" name="username" required>

        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>

        <button type="submit">Aceder ao Dashboard</button>
    </form>
</div>

</body>
</html>