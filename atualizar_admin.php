<?php
require_once 'conexao.php';

$usuarioAdmin = 'JBadmin123';
$senhaAdmin   = 'informatica2024-2026';
$hashSenha    = password_hash($senhaAdmin, PASSWORD_BCRYPT);

try {
    $stmt = $pdo->prepare("INSERT INTO usuarios (nome, usuario, senha, nivel) 
                           VALUES ('Administrador Master', ?, ?, 'admin') 
                           ON DUPLICATE KEY UPDATE senha = ?, nivel = 'admin'");

    $stmt->execute([$usuarioAdmin, $hashSenha, $hashSenha]);

    echo "✅ Administrador (JBadmin123) cadastrado/atualizado com sucesso!";
} catch (PDOException $e) {
    echo "❌ Erro ao atualizar administrador: " . $e->getMessage();
}
?>