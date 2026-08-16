<?php
session_start();
require_once 'conexao.php';

header('Content-Type: application/json; charset=utf-8');

// Verifica autenticação
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

// -------------------------------------------------------------
// 1. PROCESSA REQUISIÇÕES POST (Deletar Individual / Apagar Tudo)
// -------------------------------------------------------------
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';

    try {
        if ($action === 'delete') {
            $id = (int)($input['id'] ?? 0);
            if ($id > 0) {
                $stmt = $pdo->prepare("DELETE FROM ocorrencias WHERE id = ?");
                $stmt->execute([$id]);
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'ID inválido']);
            }
            exit;
        }

        if ($action === 'delete_all') {
            // Limpa todas as ocorrências
            $stmt = $pdo->prepare("DELETE FROM ocorrencias");
            $stmt->execute();
            echo json_encode(['success' => true]);
            exit;
        }

        echo json_encode(['success' => false, 'message' => 'Ação inválida']);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// -------------------------------------------------------------
// 2. PROCESSA REQUISIÇÕES GET (Listar e Filtrar Registros)
// -------------------------------------------------------------
if ($method === 'GET') {
    $curso      = trim($_GET['curso'] ?? '');
    $turma      = trim($_GET['turma'] ?? ''); // Equivalente a 'Série' no formulário
    $mesInicio  = (int)($_GET['mes_inicio'] ?? 0);
    $mesFim     = (int)($_GET['mes_fim'] ?? 0);
    $matricula  = trim($_GET['matricula'] ?? '');

    try {
        // Query com JOINs para pegar dados completos dos Alunos
        $sql = "
            SELECT 
                o.id,
                o.tipo AS tipo,
                o.detalhe_item AS tipo_ocorrencia,
                o.observacao AS observacoes,
                DATE_FORMAT(o.data_registro, '%Y-%m-%d') AS data_registro,
                DATE_FORMAT(o.data_registro, '%H:%i') AS hora_registro,
                a.nome AS aluno_nome,
                a.matricula AS aluno_matricula,
                a.curso AS curso,
                a.serie AS turma,
                u.nome AS usuario_nome
            FROM ocorrencias o
            INNER JOIN alunos a ON o.aluno_id = a.id
            LEFT JOIN usuarios u ON o.usuario_id = u.id
            WHERE 1=1
        ";

        $params = [];

        // Filtro por Curso
        if (!empty($curso)) {
            $sql .= " AND a.curso = ?";
            $params[] = $curso;
        }

        // Filtro por Série / Turma
        if (!empty($turma)) {
            $sql .= " AND a.serie = ?";
            $params[] = $turma;
        }

        // Filtro por Matrícula Específica (caso venha do histórico)
        if (!empty($matricula)) {
            $sql .= " AND a.matricula = ?";
            $params[] = $matricula;
        }

        // Filtro por Período de Meses
        if ($mesInicio > 0 && $mesFim > 0) {
            $sql .= " AND MONTH(o.data_registro) BETWEEN ? AND ?";
            $params[] = $mesInicio;
            $params[] = $mesFim;
        }

        $sql .= " ORDER BY o.data_registro DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Mapeia os dados ajustando nomes para o HTML consumir perfeitamente
        $registrosFormatados = array_map(function($r) {
            return [
                'id'              => (int)$r['id'],
                'tipo'            => $r['tipo'] ?: $r['tipo_ocorrencia'],
                'tipo_ocorrencia' => $r['tipo_ocorrencia'] ?: 'Ocorrência',
                'aluno_nome'      => $r['aluno_nome'],
                'observacoes'     => $r['observacoes'] ?: 'Sem observações.',
                'curso'           => $r['curso'],
                'turma'           => $r['turma'],
                'data_registro'   => $r['data_registro'],
                'hora_registro'   => $r['hora_registro'],
                'motivo_saida'    => null
            ];
        }, $resultados);

        echo json_encode($registrosFormatados);
        exit;

    } catch (PDOException $e) {
        echo json_encode(['error' => 'Erro ao consultar banco: ' . $e->getMessage()]);
        exit;
    }
}