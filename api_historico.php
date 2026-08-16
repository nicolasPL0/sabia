<?php
require_once 'conexao.php';

header('Content-Type: application/json');

$curso = isset($_GET['curso']) ? $_GET['curso'] : '';
$turma = isset($_GET['turma']) ? $_GET['turma'] : '';

// --- INÍCIO DA CHECAGEM AUTOMÁTICA DE SUSPENSÕES ---
// Busca todos os alunos que estão com o status "Suspenso"
$stmtSuspensos = $pdo->query("SELECT matricula FROM alunos WHERE status = 'Suspenso'");
$suspensos = $stmtSuspensos->fetchAll(PDO::FETCH_ASSOC);

foreach ($suspensos as $aluno) {
    $mat = $aluno['matricula'];
    
    // Pega a data da suspensão mais recente do aluno
    $stmtReg = $pdo->prepare("SELECT data_registro FROM registros WHERE matricula = ? AND tipo = 'Suspensão' ORDER BY data_registro DESC LIMIT 1");
    $stmtReg->execute([$mat]);
    $ultimaSuspensao = $stmtReg->fetch(PDO::FETCH_ASSOC);

    if ($ultimaSuspensao && !empty($ultimaSuspensao['data_registro'])) {
        $dataSuspensao = new DateTime($ultimaSuspensao['data_registro']);
        
        // Pega a data de hoje pegando apenas ano, mês e dia para a conta bater certinho
        $hoje = new DateTime(date('Y-m-d')); 
        $diasUteis = 0;
        
        $cloneData = clone $dataSuspensao;
        $cloneData->setTime(0,0,0);
        
        // Vai somando 1 dia até chegar no dia de hoje, contando apenas de segunda a sexta
        while ($cloneData < $hoje) {
            $cloneData->modify('+1 day');
            // Formato 'N': 1 (segunda) até 7 (domingo). Menor que 6 = dia da semana.
            if ($cloneData->format('N') < 6) { 
                $diasUteis++;
            }
        }

        // Se já passaram 3 dias úteis (letivos), volta o aluno para 'Ativo'
        if ($diasUteis >= 3) {
            $pdo->prepare("UPDATE alunos SET status = 'Ativo' WHERE matricula = ?")->execute([$mat]);
        }
    }
}
// --- FIM DA CHECAGEM AUTOMÁTICA ---

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
