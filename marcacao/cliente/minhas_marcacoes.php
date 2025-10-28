<?php
// 1. Incluir a conexão
require_once '../includes/db_connect.php';

// 2. Verificação de Acesso (Segurança)
if (!isset($_SESSION['cliente_id'])) {
    // Se não estiver logado, redireciona para a página de login
    header('Location: ../index.php');
    exit();
}

$cliente_id = $_SESSION['cliente_id'];
$cliente_nome = $_SESSION['cliente_nome'];
$mensagem = '';
$marcacoes = [];

// 3. Lógica para Criar Nova Marcação (Formulário Simples)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nova_marcacao'])) {
    $data_hora_str = trim($_POST['data_hora'] ?? '');
    $morada = trim($_POST['morada'] ?? '');

    if (empty($data_hora_str) || empty($morada)) {
        $mensagem = '<div class="alerta erro">Por favor, preencha a data/hora e a morada para a marcação.</div>';
    } else {
        try {
            // Verifica se a data é futura (Validação JS no frontend é ideal, mas PHP é a segurança)
            $data_marcacao = new DateTime($data_hora_str);
            if ($data_marcacao < new DateTime()) {
                $mensagem = '<div class="alerta erro">Não é possível agendar no passado.</div>';
            } else {
                $sql = "INSERT INTO marcacoes (id_cliente, data_hora, morada_completa, status) VALUES (:id_cliente, :data_hora, :morada, 'Pendente')";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':id_cliente' => $cliente_id,
                    ':data_hora' => $data_hora_str,
                    ':morada' => $morada
                ]);
                $mensagem = '<div class="alerta sucesso">Marcação solicitada com sucesso! A equipa irá confirmar em breve.</div>';
            }
        } catch (\Exception $e) {
            $mensagem = '<div class="alerta erro">Erro ao criar a marcação. Verifique o formato da data/hora.</div>';
        }
    }
}

// 4. Buscar as marcações do cliente
try {
    $sql_marcacoes = "SELECT id_marcacao, DATE_FORMAT(data_hora, '%d-%m-%Y às %H:%i') as data_formatada, morada_completa, status 
                      FROM marcacoes 
                      WHERE id_cliente = :id_cliente 
                      ORDER BY data_hora DESC";
    $stmt_marcacoes = $pdo->prepare($sql_marcacoes);
    $stmt_marcacoes->execute([':id_cliente' => $cliente_id]);
    $marcacoes = $stmt_marcacoes->fetchAll();

} catch (\PDOException $e) {
    $mensagem_bd = '<div class="alerta erro">Erro ao carregar as suas marcações.</div>';
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Minhas Marcações - Área do Cliente</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
        .header { background-color: #333; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; }
        .header a { color: #f4f4f4; text-decoration: none; margin-left: 20px; }
        .header a:hover { text-decoration: underline; }
        .content { width: 90%; max-width: 1200px; margin: 20px auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); }
        h1 { color: #007bff; }
        h2 { border-bottom: 2px solid #ccc; padding-bottom: 10px; margin-top: 30px; }
        .alerta { padding: 10px; margin-bottom: 15px; border-radius: 4px; text-align: center; }
        .sucesso { background-color: #dff0d8; color: #3c763d; border: 1px solid #d6e9c6; }
        .erro { background-color: #f2dede; color: #a94442; border: 1px solid #ebccd1; }
        /* Estilos da Tabela */
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #f2f2f2; color: #333; }
        .status-Pendente { color: #ffc107; font-weight: bold; }
        .status-Confirmada { color: #28a745; font-weight: bold; }
        .status-Concluída { color: #6c757d; }
        .status-Cancelada { color: #dc3545; }
        /* Estilos do Formulário */
        .form-marcacao input[type="datetime-local"], .form-marcacao input[type="text"] { padding: 10px; margin: 5px 0 15px; width: 100%; box-sizing: border-box; }
        .form-marcacao button { background-color: #007bff; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; }
    </style>
</head>
<body>

<div class="header">
    <span>Bem-vindo(a), **<?php echo htmlspecialchars($cliente_nome); ?>**</span>
    <nav>
        <a href="minhas_marcacoes.php">Minhas Marcações</a>
        <a href="../logout.php">Sair</a>
    </nav>
</div>

<div class="content">
    <h1>Área do Cliente</h1>

    <?php echo $mensagem; ?>

    <h2>Solicitar Nova Marcação de Medidas</h2>
    <form method="POST" action="minhas_marcacoes.php" class="form-marcacao">
        
        <label for="data_hora">Data e Hora da Visita (Ex: 2025-12-31T14:30)</label>
        <input type="datetime-local" id="data_hora" name="data_hora" required>

        <label for="morada">Morada para a Medição</label>
        <input type="text" id="morada" name="morada" placeholder="Rua, Número, Código Postal, Cidade" required>

        <button type="submit" name="nova_marcacao">Solicitar Marcação</button>
    </form>


    <h2>Histórico e Marcações Futuras</h2>

    <?php if (empty($marcacoes)): ?>
        <p>Ainda não tem marcações registadas.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Data e Hora</th>
                    <th>Morada</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($marcacoes as $marcacao): ?>
                <tr>
                    <td><?php echo htmlspecialchars($marcacao['id_marcacao']); ?></td>
                    <td><?php echo htmlspecialchars($marcacao['data_formatada']); ?></td>
                    <td><?php echo htmlspecialchars($marcacao['morada_completa']); ?></td>
                    <td class="status-<?php echo $marcacao['status']; ?>">
                        <?php echo htmlspecialchars($marcacao['status']); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

</div>

</body>
</html>