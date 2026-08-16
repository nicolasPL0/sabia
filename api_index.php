<?php
require_once 'conexao.php';

header('Content-Type: application/json');

$hoje = date('Y-m-d');

// 1. Registros de hoje
$stmtHoje = $pdo->prepare("SELECT * FROM registros WHERE data_registro = ? ORDER BY hora_registro DESC");
$stmtHoje->execute([$hoje]);
$registrosHoje = $stmtHoje->fetchAll();

// 2. Alunos em alerta — contagem baseada no campo 'tipo' padronizado
$stmtAlunos = $pdo->query("SELECT matricula, nome, turma FROM alunos WHERE status = 'Ativo'");
$alunos = $stmtAlunos->fetchAll();

$stmtRegistros = $pdo->query("SELECT matricula, tipo FROM registros");
$todosRegistros = $stmtRegistros->fetchAll();

$contagemTol = [];
$contagemOcorr = [];

foreach ($todosRegistros as $r) {
    $mat = $r['matricula'];
    if (!isset($contagemTol[$mat])) $contagemTol[$mat] = 0;
    if (!isset($contagemOcorr[$mat])) $contagemOcorr[$mat] = 0;

    // Contagem direta pelo campo 'tipo' padronizado
    if ($r['tipo'] === 'Tolerância') {
        $contagemTol[$mat]++;
    } elseif ($r['tipo'] === 'Ocorrência') {
        $contagemOcorr[$mat]++;
    }
}

$emAlerta = [];
foreach ($alunos as $a) {
    $mat = $a['matricula'];
    $tol = isset($contagemTol[$mat]) ? $contagemTol[$mat] : 0;
    $ocorr = isset($contagemOcorr[$mat]) ? $contagemOcorr[$mat] : 0;

    // Alerta: 2 ou mais ocorrências ou 2 ou mais tolerâncias
    if ($ocorr >= 2 || $tol >= 2) {
        $emAlerta[] = [
            'nome' => $a['nome'],
            'turma' => $a['turma'],
            'tol' => $tol,
            'ocorr' => $ocorr
        ];
    }
}

echo json_encode([
    'hoje' => $registrosHoje,
    'alertas' => $emAlerta
]);
?>
