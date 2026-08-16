<?php
/**
 * Régua Disciplinar — Escalonamento Automático
 * 
 * Regras:
 *   - A cada 3 Tolerâncias acumuladas  → Gera 1 Ocorrência automática
 *   - A cada 3 Ocorrências acumuladas  → Gera 1 Notificação automática
 *   - A cada 3 Notificações acumuladas → Gera 1 Suspensão automática + altera status do aluno
 *
 * Os registros automáticos são marcados com tipo_ocorrencia = 'Escalonamento Automático'
 * para diferenciá-los dos registros manuais.
 */

function aplicarReguaDisciplinar($pdo, $matricula) {
    // 1. Contar registros existentes por tipo para esta matrícula
    $stmt = $pdo->prepare("SELECT tipo, COUNT(*) as total FROM registros WHERE matricula = ? GROUP BY tipo");
    $stmt->execute([$matricula]);
    $contagem = $stmt->fetchAll();

    $totais = [
        'Tolerância'  => 0,
        'Ocorrência'  => 0,
        'Notificação' => 0,
        'Suspensão'   => 0
    ];

    foreach ($contagem as $row) {
        if (isset($totais[$row['tipo']])) {
            $totais[$row['tipo']] = (int) $row['total'];
        }
    }

    // 2. Contar quantos registros de escalonamento já existem por tipo gerado
    $stmtEsc = $pdo->prepare("SELECT tipo, COUNT(*) as total FROM registros WHERE matricula = ? AND tipo_ocorrencia = 'Escalonamento Automático' GROUP BY tipo");
    $stmtEsc->execute([$matricula]);
    $escExistentes = $stmtEsc->fetchAll();

    $escalonamentosExistentes = [
        'Ocorrência'  => 0,
        'Notificação' => 0,
        'Suspensão'   => 0
    ];

    foreach ($escExistentes as $row) {
        if (isset($escalonamentosExistentes[$row['tipo']])) {
            $escalonamentosExistentes[$row['tipo']] = (int) $row['total'];
        }
    }

    // 3. Calcular quantos escalonamentos deveriam existir
    $ocorrenciasEsperadas  = floor($totais['Tolerância'] / 3);
    $notificacoesEsperadas = floor($totais['Ocorrência'] / 3);
    $suspensoesEsperadas   = floor($totais['Notificação'] / 3);

    $dataHoje = date('Y-m-d');
    $horaAgora = date('H:i:s');
    $criadoEm = date('d/m/Y');
    $criadoHora = date('H:i');

    // 4. Buscar dados do aluno para preencher os registros automáticos
    $stmtAluno = $pdo->prepare("SELECT nome, turma, curso FROM alunos WHERE matricula = ?");
    $stmtAluno->execute([$matricula]);
    $aluno = $stmtAluno->fetch();

    if (!$aluno) return; // Aluno não encontrado, sair

    $alunoNome = $aluno['nome'];
    $alunoTurma = $aluno['turma'];
    $alunoCurso = $aluno['curso'];

    // 5. Gerar registros de escalonamento faltantes

    // 5a. Tolerâncias → Ocorrências
    $faltamOcorrencias = $ocorrenciasEsperadas - $escalonamentosExistentes['Ocorrência'];
    for ($i = 0; $i < $faltamOcorrencias; $i++) {
        inserirRegistroEscalonamento($pdo, [
            'tipo_ocorrencia' => 'Escalonamento Automático',
            'tipo'            => 'Ocorrência',
            'curso'           => $alunoCurso,
            'turma'           => $alunoTurma,
            'aluno'           => $alunoNome,
            'matricula'       => $matricula,
            'data_registro'   => $dataHoje,
            'hora_registro'   => $horaAgora,
            'observacoes'     => 'Gerado automaticamente: 3 Tolerâncias acumuladas = 1 Ocorrência.',
            'criado_em'       => $criadoEm,
            'criado_hora'     => $criadoHora
        ]);
    }

    // Recalcular total de Ocorrências após possível inserção
    if ($faltamOcorrencias > 0) {
        $totais['Ocorrência'] += $faltamOcorrencias;
        $notificacoesEsperadas = floor($totais['Ocorrência'] / 3);
    }

    // 5b. Ocorrências → Notificações
    $faltamNotificacoes = $notificacoesEsperadas - $escalonamentosExistentes['Notificação'];
    for ($i = 0; $i < $faltamNotificacoes; $i++) {
        inserirRegistroEscalonamento($pdo, [
            'tipo_ocorrencia' => 'Escalonamento Automático',
            'tipo'            => 'Notificação',
            'curso'           => $alunoCurso,
            'turma'           => $alunoTurma,
            'aluno'           => $alunoNome,
            'matricula'       => $matricula,
            'data_registro'   => $dataHoje,
            'hora_registro'   => $horaAgora,
            'observacoes'     => 'Gerado automaticamente: 3 Ocorrências acumuladas = 1 Notificação.',
            'criado_em'       => $criadoEm,
            'criado_hora'     => $criadoHora
        ]);
    }

    // Recalcular total de Notificações após possível inserção
    if ($faltamNotificacoes > 0) {
        $totais['Notificação'] += $faltamNotificacoes;
        $suspensoesEsperadas = floor($totais['Notificação'] / 3);
    }

    // 5c. Notificações → Suspensões
    $faltamSuspensoes = $suspensoesEsperadas - $escalonamentosExistentes['Suspensão'];
    for ($i = 0; $i < $faltamSuspensoes; $i++) {
        inserirRegistroEscalonamento($pdo, [
            'tipo_ocorrencia' => 'Escalonamento Automático',
            'tipo'            => 'Suspensão',
            'curso'           => $alunoCurso,
            'turma'           => $alunoTurma,
            'aluno'           => $alunoNome,
            'matricula'       => $matricula,
            'data_registro'   => $dataHoje,
            'hora_registro'   => $horaAgora,
            'observacoes'     => 'Gerado automaticamente: 3 Notificações acumuladas = 1 Suspensão. Aluno recluso das atividades por 3 dias.',
            'criado_em'       => $criadoEm,
            'criado_hora'     => $criadoHora
        ]);

        // Alterar o status do aluno para 'Suspenso'
        $stmtStatus = $pdo->prepare("UPDATE alunos SET status = 'Suspenso' WHERE matricula = ?");
        $stmtStatus->execute([$matricula]);
    }
}

/**
 * Insere um registro de escalonamento no banco de dados.
 */
function inserirRegistroEscalonamento($pdo, $dados) {
    $sql = "INSERT INTO registros (
                tipo_ocorrencia, tipo, curso, turma, aluno, matricula,
                data_registro, hora_registro, motivo_saida, outro_motivo,
                observacoes, criado_em, criado_hora, lida
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, '', '', ?, ?, ?, 0)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $dados['tipo_ocorrencia'],
        $dados['tipo'],
        $dados['curso'],
        $dados['turma'],
        $dados['aluno'],
        $dados['matricula'],
        $dados['data_registro'],
        $dados['hora_registro'],
        $dados['observacoes'],
        $dados['criado_em'],
        $dados['criado_hora']
    ]);
}
?>
