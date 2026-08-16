<?php
session_start();
require_once 'conexao.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $usuarioInput = trim($data['usuario'] ?? '');
    $senhaInput   = trim($data['senha'] ?? '');
    $nivelInput   = trim($data['nivel'] ?? '');

    if (empty($usuarioInput) || empty($senhaInput) || empty($nivelInput)) {
        echo json_encode(['success' => false, 'message' => 'Preencha todos os campos.']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = ? AND nivel = ?");
    $stmt->execute([$usuarioInput, $nivelInput]);
    $user = $stmt->fetch();

    if ($user && password_verify($senhaInput, $user['senha'])) {
        $_SESSION['usuario_id']     = $user['id'];
        $_SESSION['usuario_nome']   = $user['nome'];
        $_SESSION['usuario_nivel']  = $user['nivel'];
        $_SESSION['usuario_pdt']    = $user['is_pdt'] ?? 0; // Armazena a permissão de PDT

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Usuário, senha ou perfil incorretos.']);
    }
}
?>