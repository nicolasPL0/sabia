<?php
session_start();
require_once 'conexao.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_nivel']) || $_SESSION['usuario_nivel'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $pdo->prepare("SELECT id, nome, usuario, is_pdt, pdt_curso, pdt_serie, criado_em FROM usuarios WHERE nivel = 'professor' ORDER BY nome ASC");
    $stmt->execute();
    echo json_encode($stmt->fetchAll());
    exit;
}

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $nome    = trim($data['nome'] ?? '');
    $usuario = trim($data['usuario'] ?? '');
    $senha   = trim($data['senha'] ?? '');
    $isPdt   = !empty($data['is_pdt']) ? 1 : 0;
    $curso   = $isPdt ? trim($data['pdt_curso'] ?? '') : null;
    $serie   = $isPdt ? trim($data['pdt_serie'] ?? '') : null;
    $turma   = ($isPdt && $serie && $curso) ? "{$serie} - {$curso}" : null;

    if (empty($nome) || empty($usuario) || empty($senha)) {
        echo json_encode(['success' => false, 'message' => 'Preencha todos os campos obrigatórios.']);
        exit;
    }

    if ($isPdt && (empty($curso) || empty($serie))) {
        echo json_encode(['success' => false, 'message' => 'Selecione o Curso e a Série para o PDT.']);
        exit;
    }

    $hashSenha = password_hash($senha, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("INSERT INTO usuarios (nome, usuario, senha, nivel, is_pdt, turma_dirigida, pdt_curso, pdt_serie) VALUES (?, ?, ?, 'professor', ?, ?, ?, ?)");

    try {
        $stmt->execute([$nome, $usuario, $hashSenha, $isPdt, $turma, $curso, $serie]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erro ao cadastrar: ' . $e->getMessage()]);
    }
}
?>