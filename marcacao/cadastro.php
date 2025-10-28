<?php
// Incluir o ficheiro de conexão à base de dados
require_once 'includes/db_connect.php';

$mensagem = '';

// 1. Processamento do Formulário (se for submetido)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $telefone = trim($_POST['telefone'] ?? '');

    // Validação básica
    if (empty($nome) || empty($email) || empty($password)) {
        $mensagem = '<div class="alerta erro">Por favor, preencha todos os campos obrigatórios.</div>';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensagem = '<div class="alerta erro">O formato do email é inválido.</div>';
    } else {
        // 2. Criptografar a password antes de guardar
        $password_hashed = password_hash($password, PASSWORD_DEFAULT);

        // 3. Tentar inserir o novo utilizador na base de dados
        try {
            $sql = "INSERT INTO utilizadores (nome, email, password, telefone) VALUES (:nome, :email, :password, :telefone)";
            $stmt = $pdo->prepare($sql);
            
            $stmt->execute([
                ':nome' => $nome,
                ':email' => $email,
                ':password' => $password_hashed,
                ':telefone' => $telefone
            ]);

            $mensagem = '<div class="alerta sucesso">Registo efetuado com sucesso! Já pode <a href="index.php">iniciar sessão</a>.</div>';
            // Opcional: Limpar os campos após o sucesso
            $nome = $email = $telefone = ''; 

        } catch (\PDOException $e) {
            if ($e->getCode() == '23000') { // Código para erro de duplicidade (email UNIQUE)
                $mensagem = '<div class="alerta erro">Este email já está registado. Por favor, tente iniciar sessão.</div>';
            } else {
                $mensagem = '<div class="alerta erro">Ocorreu um erro no registo. Tente novamente.</div>';
                // Para debug: $mensagem .= " Erro: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Registo de Cliente - Sistema de Marcações</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .container { background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); width: 350px; }
        h2 { text-align: center; color: #333; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="email"], input[type="password"] { width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { background-color: #5cb85c; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; width: 100%; font-size: 16px; }
        button:hover { background-color: #4cae4c; }
        .alerta { padding: 10px; margin-bottom: 15px; border-radius: 4px; text-align: center; }
        .sucesso { background-color: #dff0d8; color: #3c763d; border: 1px solid #d6e9c6; }
        .erro { background-color: #f2dede; color: #a94442; border: 1px solid #ebccd1; }
        .link-login { text-align: center; margin-top: 15px; }
    </style>
</head>
<body>

<div class="container">
    <h2>Registo de Cliente</h2>
    
    <?php echo $mensagem; // Exibe a mensagem de sucesso ou erro ?>

    <form method="POST" action="cadastro.php">
        
        <label for="nome">Nome Completo</label>
        <input type="text" id="nome" name="nome" required value="<?php echo htmlspecialchars($nome ?? ''); ?>">

        <label for="email">Email</label>
        <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($email ?? ''); ?>">

        <label for="telefone">Telefone</label>
        <input type="text" id="telefone" name="telefone" value="<?php echo htmlspecialchars($telefone ?? ''); ?>">

        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>

        <button type="submit">Registar</button>
    </form>
    
    <p class="link-login">Já tem conta? <a href="index.php">Iniciar Sessão</a></p>
</div>

</body>
</html>