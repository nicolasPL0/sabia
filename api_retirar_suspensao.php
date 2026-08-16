<?php
require_once 'conexao.php';

header('Content-Type: application/json');

// Recebe os dados via POST (JSON)
$data = json_decode(file_get_contents('php://input'), true);
$matricula = $data['matricula'] ?? null;

if ($matricula) {
    try {
        // Altera o status do aluno de volta para "Ativo"
        $stmt = $pdo->prepare("UPDATE alunos SET status = 'Ativo' WHERE matricula = ?");
        $stmt->execute([$matricula]);
        
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Erro no banco: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Matrícula não foi enviada.']);
}
?>