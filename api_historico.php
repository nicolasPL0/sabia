<?php
require_once 'conexao.php';

header('Content-Type: application/json');

$curso = isset($_GET['curso']) ? $_GET['curso'] : '';
$turma = isset($_GET['turma']) ? $_GET['turma'] : '';

// Buscar alunos filtrados
$sql = "SELECT matricula, nome, curso, turma FROM alunos WHERE status IN ('Ativo', 'Suspenso')";
$params = [];

if ($curso) {
    $sql .= " AND curso = ?";
    $params[] = $curso;
}
if ($turma) {
    $sql .= " AND turma = ?";
    $params[] = $turma;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$alunos = $stmt->fetchAll();

// Buscar todos os registros dos alunos filtrados para agregar os dados
$matriculas = array_column($alunos, 'matricula');

if (empty($matriculas)) {
    echo json_encode([]);
    exit;
}

$inQuery = implode(',', array_fill(0, count($matriculas), '?'));
$sqlRegistros = "SELECT matricula, tipo FROM registros WHERE matricula IN ($inQuery)";
$stmtRegistros = $pdo->prepare($sqlRegistros);
$stmtRegistros->execute($matriculas);
$registros = $stmtRegistros->fetchAll();

// Mapear os registros por matrícula, contando pelo campo 'tipo' padronizado
$contagemPorMatricula = [];
foreach ($registros as $reg) {
    $mat = $reg['matricula'];
    if (!isset($contagemPorMatricula[$mat])) {
        $contagemPorMatricula[$mat] = [
            'Tolerância'      => 0,
            'Ocorrência'      => 0,
            'Notificação'     => 0,
            'Suspensão'       => 0,
            'Saída Antecipada'=> 0
        ];
    }
    $tipo = $reg['tipo'];
    if (isset($contagemPorMatricula[$mat][$tipo])) {
        $contagemPorMatricula[$mat][$tipo]++;
    }
}

$resultado = [];
foreach ($alunos as $aluno) {
    $mat = $aluno['matricula'];
    $contagem = isset($contagemPorMatricula[$mat]) ? $contagemPorMatricula[$mat] : [
        'Tolerância'      => 0,
        'Ocorrência'      => 0,
        'Notificação'     => 0,
        'Suspensão'       => 0,
        'Saída Antecipada'=> 0
    ];

    // Contagem direta do banco — sem cálculos derivados, pois a régua já gerou os registros reais
    $Adv  = $contagem['Tolerância'];
    $oco  = $contagem['Ocorrência'];
    $noti = $contagem['Notificação'];
    $susp = $contagem['Suspensão'];

    $resultado[] = [
        'matricula'  => $mat,
        'name'       => $aluno['nome'],
        'curso'      => $aluno['curso'],
        'turma'      => $aluno['turma'],
        'Adv'        => $Adv,
        'oco'        => $oco,
        'noti'       => $noti,
        'suspensao'  => $susp
    ];
}

echo json_encode($resultado);
?>
