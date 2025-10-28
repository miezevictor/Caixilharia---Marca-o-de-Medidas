<?php
// 1. Incluir a conexão
require_once '../includes/db_connect.php';

// 2. Verificação de Acesso (Segurança)
if (!isset($_SESSION['admin_id'])) {
    // Se não for admin logado, redireciona para o login admin
    header('Location: login_admin.php');
    exit();
}

$admin_username = $_SESSION['admin_username'];
$mensagem = '';
$marcacoes = [];

// --- LÓGICA DE ATUALIZAÇÃO DE STATUS E DATA DE FIM ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao_status'])) {
    $id_marcacao = $_POST['id_marcacao'] ?? 0;
    $novo_status = $_POST['novo_status'] ?? '';
    $data_fim_prevista = $_POST['data_fim_prevista'] ?? null; // Novo campo

    // Lista de status válidos (para segurança)
    $status_validos = ['Pendente', 'Confirmada', 'Concluída', 'Cancelada'];

    if ($id_marcacao > 0 && in_array($novo_status, $status_validos)) {
        
        // Tratar a data de fim (garantir que é NULL se vier vazia)
        $data_fim_db = !empty($data_fim_prevista) ? $data_fim_prevista : null;

        try {
            // A query SQL agora inclui a atualização da data_fim_prevista
            $sql = "UPDATE marcacoes 
                    SET status = :novo_status, data_fim_prevista = :data_fim 
                    WHERE id_marcacao = :id";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':novo_status' => $novo_status,
                ':data_fim' => $data_fim_db,
                ':id' => $id_marcacao
            ]);
            $mensagem = '<div class="alerta sucesso">Marcação ID **' . htmlspecialchars($id_marcacao) . '** atualizada com sucesso.</div>';
        } catch (\PDOException $e) {
            $mensagem = '<div class="alerta erro">Erro ao atualizar a marcação.</div>';
            // Para debug: $mensagem .= " Erro: " . $e->getMessage();
        }
    } else {
        $mensagem = '<div class="alerta erro">Dados de atualização inválidos.</div>';
    }
}

// --- BUSCA DE DADOS ---

// Buscar todas as marcações (com o nome do cliente)
try {
    $sql_marcacoes = "SELECT 
                        m.id_marcacao, 
                        DATE_FORMAT(m.data_hora, '%d-%m-%Y às %H:%i') as data_formatada, 
                        m.morada_completa, 
                        m.status,
                        m.latitude,  -- <--- ADICIONADO
                        m.longitude, -- <--- ADICIONADO
                        m.data_fim_prevista, -- <--- ADICIONADO AQUI
                        u.nome as nome_cliente,
                        u.telefone as tel_cliente
                      FROM marcacoes m
                      JOIN utilizadores u ON m.id_cliente = u.id_cliente
                      ORDER BY m.data_hora DESC";
    
    $stmt_marcacoes = $pdo->query($sql_marcacoes);
    $marcacoes = $stmt_marcacoes->fetchAll();

} catch (\PDOException $e) {
    $mensagem_bd = '<div class="alerta erro">Erro ao carregar os dados das marcações.</div>';
}

// Converter os dados de marcações (com coordenadas) para JSON que o JavaScript irá ler
$marcacoes_json = json_encode($marcacoes);

// Contagem de Pendentes para Notificação
$pendentes_count = array_sum(array_map(function($m){ return $m['status'] == 'Pendente'; }, $marcacoes));

?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin - Gestão de Marcações</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        body { font-family: Arial, sans-serif; background-color: #f8f9fa; margin: 0; padding: 0; }
        .header { background-color: #3f51b5; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); }
        .header a { color: #f4f4f4; text-decoration: none; margin-left: 20px; }
        .header a:hover { text-decoration: underline; }
        .content { width: 95%; max-width: 1400px; margin: 30px auto; background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 0 15px rgba(0, 0, 0, 0.05); }
        h1 { color: #3f51b5; margin-bottom: 20px; }
        h2 { border-bottom: 1px solid #eee; padding-bottom: 10px; margin-top: 30px; color: #555; }
        /* Notificação */
        .notificacao-box { background-color: #ffc107; color: #333; padding: 15px; border-radius: 5px; margin-bottom: 20px; font-size: 1.1em; font-weight: bold; text-align: center; }
        .notificacao-box.sucesso { background-color: #28a745; color: white; }
        /* Tabela */
        table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 0.9em; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; vertical-align: middle; }
        th { background-color: #e9ecef; color: #333; }
        /* Formulário de Ação Rápida */
        .form-status { display: flex; gap: 5px; }
        .status-select { padding: 5px; border-radius: 4px; }
        .status-btn { padding: 5px 10px; background-color: #3f51b5; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .status-btn:hover { background-color: #303f9f; }
        .alerta { padding: 10px; margin-bottom: 15px; border-radius: 4px; text-align: center; font-weight: bold; }
        .sucesso { background-color: #dff0d8; color: #3c763d; border: 1px solid #d6e9c6; }
        .erro { background-color: #f2dede; color: #a94442; border: 1px solid #ebccd1; }
    </style>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

    <script src="../assets/js/mapa.js" defer></script>
</head>
<body>

<div class="header">
    <span>Administrador: **<?php echo htmlspecialchars($admin_username); ?>**</span>
    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="logout_admin.php">Sair (Logout)</a>
    </nav>
</div>

<div class="content">
    <h1>Gestão de Marcações de Medidas</h1>
    <div id="mini-mapa" style="height: 400px; margin-bottom: 25px; border: 1px solid #ccc; border-radius: 4px;"></div>

    <script>
        // Esta variável global será lida pelo ficheiro mapa.js
        const MARCACIONES_DATA = <?php echo $marcacoes_json; ?>;
    </script>

    <?php echo $mensagem; // Mensagens de sucesso/erro após a atualização ?>
    <?php echo $mensagem_bd ?? ''; // Mensagens de erro da base de dados ?>

    <?php if ($pendentes_count > 0): ?>
        <div class="notificacao-box">
            Tem **<?php echo $pendentes_count; ?>** marcação(ões) **Pendente(s)** a aguardar confirmação!
        </div>
    <?php else: ?>
        <div class="notificacao-box sucesso">
            Não há marcações pendentes no momento. Tudo em dia!
        </div>
    <?php endif; ?>

    <h2>Todas as Marcações Registadas</h2>
    
    <?php if (empty($marcacoes)): ?>
        <p>Ainda não há marcações de clientes registadas no sistema.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Cliente</th>
                    <th>Contacto</th>
                    <th>Data e Hora</th>
                    <th>Morada</th>
                    <th>Status Atual</th>
                    <th>Fim Previsto</th> <th>Ação</th> 
                </tr>
            </thead>
            <tbody>
                <?php foreach ($marcacoes as $marcacao): ?>
                <tr>
                    <td><?php echo htmlspecialchars($marcacao['id_marcacao']); ?></td>
                    <td><?php echo htmlspecialchars($marcacao['nome_cliente']); ?></td>
                    <td><?php echo htmlspecialchars($marcacao['tel_cliente']); ?></td>
                    <td><?php echo htmlspecialchars($marcacao['data_formatada']); ?></td>
                    <td><?php echo htmlspecialchars($marcacao['morada_completa']); ?></td>
                    <td class="status-<?php echo $marcacao['status']; ?>">
                        **<?php echo htmlspecialchars($marcacao['status']); ?>**
                    </td>
                    <td>
                        <form method="POST" action="dashboard.php" class="form-status">
                            <input type="hidden" name="id_marcacao" value="<?php echo $marcacao['id_marcacao']; ?>">
                            
                            <input type="date" name="data_fim_prevista" class="status-select" 
                            value="<?php echo htmlspecialchars($marcacao['data_fim_prevista'] ?? ''); ?>" 
                            style="width: 120px; font-size: 0.8em; margin-right: 5px;">
                            
                            <select name="novo_status" class="status-select">
                                <option value="Pendente" <?php if ($marcacao['status'] == 'Pendente') echo 'selected'; ?>>Pendente</option>
                                <option value="Confirmada" <?php if ($marcacao['status'] == 'Confirmada') echo 'selected'; ?>>Confirmada</option>
                                <option value="Concluída" <?php if ($marcacao['status'] == 'Concluída') echo 'selected'; ?>>Concluída</option>
                                <option value="Cancelada" <?php if ($marcacao['status'] == 'Cancelada') echo 'selected'; ?>>Cancelada</option>
                            </select>
                            <button type="submit" name="acao_status" class="status-btn">Atualizar</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    
</div>

</body>
</html>