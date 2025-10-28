<?php
$senha_pura = "123456";

// Gera a hash segura
$hash_final = password_hash($senha_pura, PASSWORD_DEFAULT);

echo "A senha pura é: " . $senha_pura . "\n";
echo "A hash segura gerada é: " . $hash_final . "\n";

// Exemplo de output (o valor será diferente cada vez que executar):
// A hash segura gerada é: $2y$10$v2l.o8Y9N2bX3Y1Z0J4P7.C6r9M8T5o7Q2e1L0k9A8I7Y6X5Z4W3V2U1T0S
?>