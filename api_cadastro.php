<?php
require_once 'conexao.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $pdo->query("SELECT * FROM alunos ORDER BY nome ASC");
    $alunos = $stmt->fetchAll();
    echo json_encode($alunos);
} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (isset($data['action']) && $data['action'] === 'delete') {
        $matricula = $data['matricula'];
        $stmt = $pdo->prepare("DELETE FROM alunos WHERE matricula = ?");
        $stmt->execute([$matricula]);
        echo json_encode(['success' => true]);
        exit;
    }

    if (isset($data['action']) && $data['action'] === 'edit') {
        $sql = "UPDATE alunos SET nome=?, turma=?, nascimento=?, sexo=?, curso=?, email=?, telefone=?, endereco=?, resp_nome=?, resp_tel=?, resp2_nome=?, resp2_tel=?, obs=?, status=? WHERE matricula=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $data['nome'], $data['turma'], $data['nascimento'], $data['sexo'],
            $data['curso'], $data['email'], $data['telefone'], $data['endereco'],
            $data['resp_nome'], $data['resp_tel'], $data['resp2_nome'], $data['resp2_tel'],
            $data['obs'], $data['status'], $data['matricula']
        ]);
        echo json_encode(['success' => true]);
        exit;
    }

    // CREATE
    $sql = "INSERT INTO alunos (matricula, nome, turma, nascimento, sexo, curso, email, telefone, endereco, resp_nome, resp_tel, resp2_nome, resp2_tel, cpf_aluno, cpf_resp1, cpf_resp2, obs, status, criado_em) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    try {
        $stmt->execute([
            $data['matricula'], $data['nome'], $data['turma'], $data['nascimento'], $data['sexo'],
            $data['curso'], $data['email'], $data['telefone'], $data['endereco'],
            $data['resp_nome'], $data['resp_tel'], $data['resp2_nome'], $data['resp2_tel'],
            $data['cpf_aluno'], $data['cpf_resp1'], $data['cpf_resp2'], $data['obs'],
            $data['status'], $data['criado_em']
        ]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            echo json_encode(['success' => false, 'message' => 'Matrícula já cadastrada.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro no banco: ' . $e->getMessage()]);
        }
    }
}
?>
