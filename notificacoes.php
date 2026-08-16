<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$paginaAtual = basename($_SERVER['PHP_SELF']);
$isAdmin     = isset($_SESSION['usuario_nivel']) && $_SESSION['usuario_nivel'] === 'admin';
$isPdt       = isset($_SESSION['usuario_pdt']) && (int)$_SESSION['usuario_pdt'] === 1;
$matricula   = $_GET['matricula'] ?? '';


// Busca todas as notificações gravadas (Atualizado para a tabela correta)
$registrosBanco = [];
try {
    $sql = "
        SELECT 
            id,
            tipo,
            tipo_ocorrencia,
            observacoes,
            data_registro,
            hora_registro,
            MONTH(data_registro) AS mes_num,
            aluno AS aluno_nome,
            matricula,
            curso,
            turma
        FROM registros
        ORDER BY data_registro DESC
    ";
    $stmt = $pdo->query($sql);
    $registrosBanco = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errorMsg = "Erro ao buscar notificações: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <title>Notificações — SABIÁ</title>
  <link rel="stylesheet" href="style.css" />

  <style>
    .container { max-width: 800px; }
    .filter-section-title { font-size: 13px; font-weight: 700; color: #555; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 10px; }
    .class-header { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; margin-bottom: 20px; }
    .action-group { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
    .btn-action { display: inline-flex; align-items: center; gap: 8px; text-decoration: none; border: none; cursor: pointer; }
    .notif-list { display: flex; flex-direction: column; gap: 12px; padding: 8px 0; }
    .notif-card { display: grid; grid-template-columns: 52px 1fr auto; gap: 14px; align-items: start; padding: 16px 18px; border-radius: 8px; border: 1px solid #e8ecf0; background: #fff; box-shadow: 0 1px 4px rgba(0, 0, 0, .05); }
    .notif-icon { width: 52px; height: 52px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0; background: #f8fafb; color: #158a2f; border: 1px solid #e8ecf0; }
    .notif-icon.atraso { background: #fff7ec; color: #c76b00; border-color: #ffd8a8; }
    .notif-icon.ocorrencia { background: #fff1f1; color: #d93025; border-color: #ffc7c7; }
    .notif-icon.info { background: #eef5ff; color: #1463d6; border-color: #c9ddff; }
    .notif-body strong { display: block; font-size: 15px; margin-bottom: 4px; color: #222; }
    .notif-body span { display: block; color: #555; font-size: 13px; line-height: 1.6; margin-bottom: 8px; }
    .notif-meta { display: flex; flex-wrap: wrap; gap: 8px; font-size: 12px; color: #777; }
    .meta-chip { padding: 4px 8px; border-radius: 999px; background: #f6f8fa; border: 1px solid #e5e8ec; }
    .notif-time { font-size: 12px; color: #888; text-align: right; line-height: 1.5; min-width: 92px; }
    .notif-time button { margin-top: 26px; padding: 6px 10px; border: 1px solid #dcdcdc; border-radius: 999px; background: #fff; color: #c22a20; font-weight: 700; cursor: pointer; transition: background .2s, color .2s, border-color .2s; }
    .notif-time button:hover { background: #fef0f0; border-color: #f1c0c0; color: #a91914; }
    
    @media (max-width: 700px) {
      .notif-card { grid-template-columns: 1fr; }
      .notif-time { text-align: left; min-width: auto; }
      .notif-time button { margin-top: 10px; }
    }
    @media (max-width: 580px) {
      .form-row { grid-template-columns: 1fr; }
    }

    .btn-logout-top-right {
      position: absolute; top: 15px; right: 20px; z-index: 3;
      background-color: linear-gradient(135deg, #0f7536 0%, #17a2b8 100%); color: #ffffff !important;
      padding: 8px 18px; border-radius: 20px; text-decoration: none; font-weight: bold; font-size: 13px;
      display: inline-flex; align-items: center; gap: 6px; border: 2px solid; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
      transition: transform 0.25s cubic-bezier(0.175, 0.885, 0.32, 1.275), background-color 0.2s ease, box-shadow 0.25s ease, border-color 0.2s ease;
    }
    .btn-logout-top-right:hover { background-color: #c82333; border-color: #ffffff; transform: translateY(-3px) scale(1.05); box-shadow: 0 6px 15px rgba(0, 0, 0, 0.4), 0 0 10px rgba(241, 171, 8, 0.5); }
    .btn-logout-top-right:active { transform: translateY(-1px) scale(0.98); box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3); }

    .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); display: flex; justify-content: center; align-items: center; z-index: 10000; }
    .modal-card { background: #fff; width: 90%; max-width: 450px; border-radius: 8px; padding: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
    .modal-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 15px; }
    .modal-footer { display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid #eee; padding-top: 15px; margin-top: 15px; }
    .btn-close { background: none; border: none; font-size: 22px; cursor: pointer; color: #888; }
  </style>
</head>

<body>

  <header style="width: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 20px 15px; background: linear-gradient(135deg, #0f7536 0%, #17a2b8 100%); color: #ffffff; position: relative;">
    <div style="display: flex; flex-direction: column; align-items: center; margin-bottom: 12px;">
      <div style="font-size: 32px; font-weight: 900; letter-spacing: 2px;">
        <span style="color:#f1ab08;">SABIÁ</span>
      </div>
      <div style="font-size: 14px; opacity: 0.95; font-weight: 500; margin-top: 4px;">
        Sistema de Acompanhamento e Busca de Informações Acadêmicas — 2026
      </div>
    </div>

    <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px;">
      <div style="font-size: 14px; text-align: center;">
        Usuário: <strong><?= htmlspecialchars($_SESSION['usuario_nome'] ?? 'Usuário') ?></strong> 
        <small style="opacity: 0.85;">(<?= $isAdmin ? 'Administrador' : ($isPdt ? 'PDT' : 'Professor') ?>)</small>
      </div>
      <a href="logout.php" class="btn-logout-top-right" title="Sair do Sistema">🚪 Sair</a>
    </div>
  </header>

  <nav class="nav-row">
    <div class="container nav-inner">
      <div class="nav-links" id="navLinks">
        <a href="index.php" class="<?= $paginaAtual === 'index.php' ? 'active' : '' ?>">INÍCIO</a>
        <a href="registro.php" class="<?= $paginaAtual === 'registro.php' ? 'active' : '' ?>">REGISTRO</a>
        <?php if ($isAdmin): ?>
          <a href="cadastro.php" class="<?= $paginaAtual === 'cadastro.php' ? 'active' : '' ?>">CADASTRAR ALUNOS</a>
        <?php endif; ?>
        <a href="historico.php" class="<?= $paginaAtual === 'historico.php' ? 'active' : '' ?>">HISTÓRICO</a>
        <?php if ($isAdmin): ?>
          <a href="professores.php" class="<?= $paginaAtual === 'professores.php' ? 'active' : '' ?>">PROFESSORES</a>
        <?php endif; ?>
      </div>
    </div>
  </nav>

  <div class="container page-content">
    <div class="page-title">Notificações</div>
    <div class="page-subtitle">Registros feitos em alunos, filtrados por curso, série, tipo e período.</div>

    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-num" id="st-total">0</div>
        <div class="stat-label">Registros exibidos</div>
      </div>
      <div class="stat-card orange">
        <div class="stat-num" id="st-atraso">0</div>
        <div class="stat-label">Atrasos</div>
      </div>
      <div class="stat-card red">
        <div class="stat-num" id="st-ocorr">0</div>
        <div class="stat-label">Ocorrências</div>
      </div>
      <div class="stat-card blue">
        <div class="stat-num" id="st-info">0</div>
        <div class="stat-label">Outros</div>
      </div>
    </div>

    <div class="form-row" style="margin-bottom:16px;">
      <div class="field">
        <label>Curso</label>
        <select id="curso" onchange="renderRegistros();">
          <option value="">Selecione</option>
          <option>Informática</option>
          <option>Finanças</option>
          <option>Enfermagem</option>
          <option>Estética</option>
          <option>Administração</option>
        </select>
      </div>
      <div class="field">
        <label>Série</label>
        <select id="turma" onchange="renderRegistros();">
          <option value="">Selecione</option>
          <option>1º Ano</option>
          <option>2º Ano</option>
          <option>3º Ano</option>
        </select>
      </div>
      <div class="field">
        <label>Período (Início)</label>
        <select id="periodo" onchange="renderRegistros();">
          <option value="">Selecione</option>
          <option>janeiro</option>
          <option>fevereiro</option>
          <option>março</option>
          <option>abril</option>
          <option>maio</option>
          <option>junho</option>
          <option>julho</option>
          <option>agosto</option>
          <option>setembro</option>
          <option>outubro</option>
          <option>novembro</option>
          <option>dezembro</option>
        </select>
      </div>
      <div class="field">
        <label>Período (Fim)</label>
        <select id="periodo-fim" onchange="renderRegistros();">
          <option value="">Selecione</option>
          <option>janeiro</option>
          <option>fevereiro</option>
          <option>março</option>
          <option>abril</option>
          <option>maio</option>
          <option>junho</option>
          <option>julho</option>
          <option>agosto</option>
          <option>setembro</option>
          <option>outubro</option>
          <option>novembro</option>
          <option>dezembro</option>
        </select>
      </div>
    </div>

    <div class="class-header">
      <div class="action-group" id="action-group-container">
        <button type="button" class="btn btn-primary btn-action" onclick="abrirModalFiltros()">
          <i class="fa-solid fa-filter"></i> Filtrar registros
        </button>
        <button type="button" class="btn btn-primary btn-action" onclick="location.href='historico.php'">
          <i class="fa-solid fa-circle-check"></i> Voltar ao histórico
        </button>
      </div>
    </div>

    <div class="card">
      <div class="filter-section-title">Registros encontrados</div>
      <div id="lista-notifs" class="notif-list">
        <p style="text-align:center;color:#aaa;padding:40px;font-size:14px;">Carregando...</p>
      </div>
    </div>
  </div>

  <nav class="bottom-nav">
    <a href="index.php"><span class="nav-icon">🏠</span><span class="nav-text">Início</span></a>
    <a href="registro.php"><span class="nav-icon">📝</span><span class="nav-text">Registro</span></a>
    <a href="cadastro.php"><span class="nav-icon">➕</span><span class="nav-text">Cadastrar</span></a>
    <a href="historico.php" class="active"><span class="nav-icon">⏳</span><span class="nav-text">Histórico</span></a>
  </nav>

  <!-- Modal de Filtros Avançados -->
  <div id="modalFiltros" class="modal-overlay" style="display:none;">
    <div class="modal-card">
      <div class="modal-header">
        <h3><i class="fa-solid fa-filter"></i> Filtrar Registros</h3>
        <button type="button" onclick="fecharModalFiltros()" class="btn-close">&times;</button>
      </div>
      <div class="modal-body">
        <div class="field" style="margin-bottom: 12px;">
          <label>Tipo de Categoria:</label>
          <select id="filtroTipoEspecial">
            <option value="">Todos os Registros</option>
            <option value="Notificação">Apenas Notificações</option>
            <option value="Ocorrência">Apenas Ocorrências</option>
            <option value="Tolerância">Apenas Atrasos (Tolerância)</option>
            <option value="Suspensão">Apenas Alunos Suspensos</option>
          </select>
        </div>

        <div class="field" style="margin-bottom: 12px;">
          <label>Curso:</label>
          <select id="modalCurso">
            <option value="">Todos os Cursos</option>
            <option>Informática</option>
            <option>Finanças</option>
            <option>Enfermagem</option>
            <option>Estética</option>
            <option>Administração</option>
          </select>
        </div>

        <div class="field" style="margin-bottom: 12px;">
          <label>Série / Sala:</label>
          <select id="modalTurma">
            <option value="">Todas as Séries</option>
            <option>1º Ano</option>
            <option>2º Ano</option>
            <option>3º Ano</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="fecharModalFiltros()">Cancelar</button>
        <button type="button" class="btn btn-primary" onclick="aplicarFiltrosEFechar()">Aplicar Filtro</button>
      </div>
    </div>
  </div>

  <script>
    let TODOS_REGISTROS = <?php echo json_encode($registrosBanco, JSON_UNESCAPED_UNICODE); ?>;
    const filterMatricula = "<?php echo htmlspecialchars($matricula); ?>";

    const MESES_MAP = {
      janeiro: 1, fevereiro: 2, março: 3, abril: 4, maio: 5, junho: 6,
      julho: 7, agosto: 8, setembro: 9, outubro: 10, novembro: 11, dezembro: 12
    };

    const ICONES = {
      'Tolerância': '⏰',
      'Ocorrência': '⚠️',
      'Notificação': '🔔',
      'Suspensão': '🚫',
      'Saída Antecipada': '🚪',
      'Fardamento': '👔'
    };

    function abrirModalFiltros() {
      document.getElementById('modalFiltros').style.display = 'flex';
    }

    function fecharModalFiltros() {
      document.getElementById('modalFiltros').style.display = 'none';
    }

    function aplicarFiltrosEFechar() {
      const modalCurso = document.getElementById('modalCurso').value;
      const modalTurma = document.getElementById('modalTurma').value;
      
      if (modalCurso) document.getElementById('curso').value = modalCurso;
      if (modalTurma) document.getElementById('turma').value = modalTurma;

      fecharModalFiltros();
      renderRegistros();
    }

    function formatarDataParaExibir(dataStr) {
      if (!dataStr) return '—';
      const partes = dataStr.split(' ')[0].split('-');
      if (partes.length === 3) return `${partes[2]}/${partes[1]}/${partes[0]}`;
      return dataStr;
    }

    function renderStats(list) {
      document.getElementById('st-total').textContent = list.length;
      document.getElementById('st-atraso').textContent = list.filter(n => n.tipo === 'Tolerância').length;
      document.getElementById('st-ocorr').textContent = list.filter(n =>
        n.tipo === 'Ocorrência' || n.tipo === 'Notificação' || n.tipo === 'Suspensão'
      ).length;
      document.getElementById('st-info').textContent = list.filter(n =>
        n.tipo === 'Saída Antecipada' || n.tipo === 'Fardamento'
      ).length;
    }

    function renderRegistros() {
      const curso = document.getElementById('curso').value;
      const turma = document.getElementById('turma').value;
      const periodoInicio = document.getElementById('periodo').value;
      const periodoFim = document.getElementById('periodo-fim').value;
      const tipoEspecial = document.getElementById('filtroTipoEspecial').value;

      let registrosFiltrados = [...TODOS_REGISTROS];

      if (curso) {
        registrosFiltrados = registrosFiltrados.filter(r => r.curso && r.curso.toLowerCase() === curso.toLowerCase());
      }

      if (turma) {
        registrosFiltrados = registrosFiltrados.filter(r => r.turma && r.turma.toLowerCase() === turma.toLowerCase());
      }

      if (filterMatricula) {
        registrosFiltrados = registrosFiltrados.filter(r => r.matricula === filterMatricula);
      }

      if (periodoInicio && periodoFim) {
        const mInicio = MESES_MAP[periodoInicio.toLowerCase()];
        const mFim = MESES_MAP[periodoFim.toLowerCase()];
        if (mInicio && mFim) {
          registrosFiltrados = registrosFiltrados.filter(r => {
            const m = parseInt(r.mes_num, 10);
            return m >= mInicio && m <= mFim;
          });
        }
      }

      if (tipoEspecial) {
        registrosFiltrados = registrosFiltrados.filter(r => {
          const tipoReg = r.tipo || r.tipo_ocorrencia;
          return tipoReg && tipoReg.toLowerCase() === tipoEspecial.toLowerCase();
        });
      }

      const lista = document.getElementById('lista-notifs');

      if (!registrosFiltrados.length) {
        lista.innerHTML = '<p style="text-align:center;color:#aaa;padding:40px;font-size:14px;">Nenhum registro encontrado para estes filtros.</p>';
      } else {
        lista.innerHTML = registrosFiltrados.map(r => {
          const tipoIcon = ICONES[r.tipo] || ICONES[r.tipo_ocorrencia] || '📢';
          const tipoClasse = (r.tipo === 'Tolerância')
            ? 'atraso'
            : (r.tipo === 'Ocorrência' || r.tipo === 'Notificação' || r.tipo === 'Suspensão')
              ? 'ocorrencia'
              : 'info';
          const dataExibicao = formatarDataParaExibir(r.data_registro);

          // Renderiza o botão se o registro for do tipo "Suspensão"
          const btnRetirar = (r.tipo === 'Suspensão') 
            ? `<button type="button" class="meta-chip" style="background:#d4edda; color:#155724; border-color:#c3e6cb; cursor:pointer;" onclick="retirarSuspensaoManual('${r.matricula}')">✅ Retirar Suspensão</button>` 
            : '';

          return `
            <div class="notif-card" id="card-notif-${r.id}">
              <div class="notif-icon ${tipoClasse}">${tipoIcon}</div>
              <div class="notif-body">
                <strong>${r.aluno_nome || 'Aluno'}</strong>
                <span>${r.tipo_ocorrencia || 'Registro'} — ${r.observacoes || ''}</span>
                <div class="notif-meta">
                  <span class="meta-chip">Curso: ${r.curso || '—'}</span>
                  <span class="meta-chip">Série: ${r.turma || '—'}</span>
                  <span class="meta-chip">Tipo: ${r.tipo || r.tipo_ocorrencia}</span>
                  ${btnRetirar}
                </div>
              </div>
              <div class="notif-time" style="display:flex;flex-direction:column;justify-content:space-between;align-items:flex-end;">
                <div>${dataExibicao}<br>${r.hora_registro || '—'}</div>
                <button onclick="deletarRegistroIndividual(${r.id})" title="Excluir este registro">✕</button>
              </div>
            </div>`;
        }).join('');
      }

      renderStats(registrosFiltrados);
    }

    function deletarRegistroIndividual(id) {
      if (confirm('Deseja realmente apagar este registro?')) {
        TODOS_REGISTROS = TODOS_REGISTROS.filter(r => r.id !== id);
        renderRegistros();
      }
    }

    function clearAllRegistros() {
      if (!confirm('Deseja apagar todos os registros da visualização? Essa ação não pode ser desfeita.')) return;
      TODOS_REGISTROS = [];
      renderRegistros();
    }

    async function retirarSuspensaoManual(matricula) {
      if (!confirm('Deseja retirar a suspensão e marcar o aluno como Ativo novamente?')) return;
      
      try {
        const res = await fetch('api_retirar_suspensao.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ matricula: matricula })
        });
        const data = await res.json();
        
        if (data.success) {
          alert('Suspensão retirada com sucesso! O aluno está Ativo novamente.');
          location.reload(); // Recarrega para mostrar tudo atualizado
        } else {
          alert('Erro ao atualizar: ' + (data.error || 'Erro desconhecido.'));
        }
      } catch(e) {
        console.error(e);
        alert('Erro de conexão ao tentar retirar a suspensão.');
      }
    }

    window.addEventListener('load', () => {
      renderRegistros();

      const actionGroup = document.getElementById('action-group-container');
      if (actionGroup) {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn btn-primary btn-action';
        btn.innerHTML = '<i class="fa-solid fa-trash"></i> Apagar tudo';
        btn.addEventListener('click', clearAllRegistros);
        actionGroup.appendChild(btn);
      }
    });
  </script>
</body>

</html>