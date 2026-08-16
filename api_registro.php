<?php
require_once 'conexao.php';
require_once 'regua_disciplinar.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Buscar todos os registros
    $stmt = $pdo->query("SELECT * FROM registros ORDER BY id DESC");
    $registros = $stmt->fetchAll();
    echo json_encode($registros);

} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    // ---------------------------------------------------------------
    // REGRA DE HORÁRIOS AUTOMÁTICA + MAPEAMENTO DO CAMPO 'tipo'
    // ---------------------------------------------------------------
    $tipoOcorrencia = isset($data['tipo_ocorrencia']) ? $data['tipo_ocorrencia'] : '';
    $horaRecebida   = isset($data['hora']) ? $data['hora'] : '';

    // Determinar o valor final da coluna 'tipo' baseado nas regras de negócio
    switch ($tipoOcorrencia) {
        case 'Tolerância':
            // Regra de horário: classificar automaticamente com base na hora
            $tipo = classificarAtraso($horaRecebida);
            break;

        case 'Notificação':
            $tipo = 'Notificação';
            break;

        case 'Fardamento':
            // Fardamento gera Ocorrência direta imediata
            $tipo = 'Ocorrência';
            break;

        case 'Saída Antecipada':
            $tipo = 'Saída Antecipada';
            break;

        default:
            $tipo = $tipoOcorrencia; // Fallback: usar o valor recebido
            break;
    }

    $sql = "INSERT INTO registros (
                tipo_ocorrencia, tipo, curso, turma, aluno, matricula, 
                data_registro, hora_registro, motivo_saida, outro_motivo, 
                observacoes, criado_em, criado_hora, lida
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
    $stmt = $pdo->prepare($sql);
    
    try {
        $matricula = isset($data['matricula']) ? $data['matricula'] : '';

        $stmt->execute([
            $tipoOcorrencia,
            $tipo,
            isset($data['curso']) ? $data['curso'] : '',
            isset($data['turma']) ? $data['turma'] : '',
            isset($data['aluno']) ? $data['aluno'] : '',
            $matricula,
            isset($data['data']) ? $data['data'] : null,
            $horaRecebida ?: null,
            isset($data['motivo_saida']) ? $data['motivo_saida'] : '',
            isset($data['outro_motivo']) ? $data['outro_motivo'] : '',
            isset($data['observacoes']) ? $data['observacoes'] : '',
            isset($data['criado_em']) ? $data['criado_em'] : '',
            isset($data['criado_hora']) ? $data['criado_hora'] : '',
            (isset($data['lida']) && $data['lida']) ? 1 : 0
        ]);

        // ---------------------------------------------------------------
        // APÓS O INSERT: Aplicar a Régua Disciplinar (Escalonamento)
        // ---------------------------------------------------------------
        if ($matricula) {
            aplicarReguaDisciplinar($pdo, $matricula);
        }
        
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erro no banco: ' . $e->getMessage()]);
    }
}

/**
 * Classifica o tipo de atraso com base na hora recebida.
 * 
 * Regras:
 *   - Até 07:20        → Tolerância (entrada quase normal, mas usuário registrou)
 *   - 07:21 a 07:30    → Tolerância
 *   - 07:31 a 16:40    → Ocorrência
 *   - Após 16:40       → Tolerância (fallback)
 */
function classificarAtraso($hora) {
    if (empty($hora)) {
        return 'Tolerância'; // Sem hora informada, assumir tolerância
    }

    // Aceitar formatos HH:MM e HH:MM:SS
    $partes = explode(':', $hora);
    $h = (int) $partes[0];
    $m = isset($partes[1]) ? (int) $partes[1] : 0;
    $totalMinutos = $h * 60 + $m;

    $limiteTolerancia = 7 * 60 + 30;  // 07:30
    $limiteOcorrencia = 16 * 60 + 40; // 16:40

    if ($totalMinutos <= $limiteTolerancia) {
        return 'Tolerância';
    } elseif ($totalMinutos <= $limiteOcorrencia) {
        return 'Ocorrência';
    } else {
        return 'Tolerância'; // Fallback
    }
}
?>
