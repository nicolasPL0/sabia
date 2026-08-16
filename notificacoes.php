<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$isAdmin = isset($_SESSION['usuario_nivel']) && $_SESSION['usuario_nivel'] === 'admin';
$isPdt   = isset($_SESSION['usuario_pdt']) && (int)$_SESSION['usuario_pdt'] === 1;

// Buscar todas as notificações gravadas
try {
    $sql = "
        SELECT 
            o.id,
            o.detalhe_item AS tipo_alerta,
            o.observacao AS mensagem,
            o.data_registro,
            a.nome AS aluno_nome,
            a.matricula AS aluno_matricula,
            a.turma AS aluno_turma,
            u.nome AS professor_nome
        FROM ocorrencias o
        INNER JOIN alunos a ON o.aluno_id = a.id
        INNER JOIN usuarios u ON o.usuario_id = u.id
        WHERE o.tipo = 'Notificacao'
        ORDER BY o.data_registro DESC
    ";
    $stmt = $pdo->query($sql);
    $notificacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errorMsg = "Erro ao buscar notificações: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8" />
  <!-- viewport: evita que o mobile comprima tudo numa tela pequenininha -->
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <title>Notificações — SABIÁ </title>
  <link rel="stylesheet" href="style.css" />

  <style>
    /* limita a largura dessa página */
    .container {
      max-width: 800px;
    }

    /* título acima dos filtros */
    .filter-section-title {
      font-size: 13px;
      font-weight: 700;
      color: #555;
      text-transform: uppercase;
      letter-spacing: .5px;
      margin-bottom: 10px;
    }

    /* linha com badge de turma + botões de ação */
    .class-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      /* empilha no mobile */
      gap: 10px;
      margin-bottom: 20px;
    }

    /* grupo de botões de ação (filtrar, voltar, apagar) */
    .action-group {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
      align-items: center;
    }

    /* classe base de botão de ação inline */
    .btn-action {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      text-decoration: none;
      border: none;
      cursor: pointer;
    }

    /* lista vertical de cards de notificação */
    .notif-list {
      display: flex;
      flex-direction: column;
      gap: 12px;
      padding: 8px 0;
    }

    /* card individual de notificação: ícone + corpo + data/hora + botão de excluir */
    .notif-card {
      display: grid;
      grid-template-columns: 52px 1fr auto;
      gap: 14px;
      align-items: start;
      padding: 16px 18px;
      border-radius: 8px;
      border: 1px solid #e8ecf0;
      background: #fff;
      box-shadow: 0 1px 4px rgba(0, 0, 0, .05);
    }

    /* ícone circular do tipo de notificação */
    .notif-icon {
      width: 52px;
      height: 52px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 22px;
      flex-shrink: 0;
      background: #f8fafb;
      color: #158a2f;
      border: 1px solid #e8ecf0;
    }

    /* variações de cor do ícone por tipo de ocorrência */
    .notif-icon.atraso {
      background: #fff7ec;
      color: #c76b00;
      border-color: #ffd8a8;
    }

    .notif-icon.ocorrencia {
      background: #fff1f1;
      color: #d93025;
      border-color: #ffc7c7;
    }

    .notif-icon.info {
      background: #eef5ff;
      color: #1463d6;
      border-color: #c9ddff;
    }

    /* corpo da notificação */
    .notif-body strong {
      display: block;
      font-size: 15px;
      margin-bottom: 4px;
      color: #222;
    }

    .notif-body span {
      display: block;
      color: #555;
      font-size: 13px;
      line-height: 1.6;
      margin-bottom: 8px;
    }

    /* chips de metadados (curso, série, tipo) */
    .notif-meta {
      display: flex;
      flex-wrap: wrap;
      /* quebra linha se necessário */
      gap: 8px;
      font-size: 12px;
      color: #777;
    }

    .meta-chip {
      padding: 4px 8px;
      border-radius: 999px;
      background: #f6f8fa;
      border: 1px solid #e5e8ec;
    }

    /* coluna da data/hora + botão de excluir */
    .notif-time {
      font-size: 12px;
      color: #888;
      text-align: right;
      line-height: 1.5;
      min-width: 92px;
    }

    /* botão vermelho de excluir registro */
    .notif-time button {
      margin-top: 26px;
      padding: 6px 10px;
      border: 1px solid #dcdcdc;
      border-radius: 999px;
      background: #fff;
      color: #c22a20;
      font-weight: 700;
      cursor: pointer;
      transition: background .2s, color .2s, border-color .2s;
    }

    .notif-time button:hover {
      background: #fef0f0;
      border-color: #f1c0c0;
      color: #a91914;
    }

    /* no mobile, o card vira coluna única (sem grade de 3) */
    @media (max-width: 700px) {
      .notif-card {
        grid-template-columns: 1fr;
      }

      .notif-time {
        text-align: left;
        min-width: auto;
      }

      .notif-time button {
        margin-top: 10px;
      }
    }

    /* filtros em coluna no mobile */
    @media (max-width: 580px) {
      .form-row {
        grid-template-columns: 1fr;
      }
    }

         .topbar {
    position: relative;
    width: 100%;
    padding: 20px 15px;
    background: linear-gradient(135deg, #0f7536 0%, #17a2b8 100%);
    color: #ffffff;
    overflow: hidden;
    box-sizing: border-box;
  }

  /* Decoração Inclinada de Fundo */
  .angled-deco {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.08);
    transform: skewY(-2deg);
    transform-origin: top left;
    pointer-events: none;
    z-index: 1;
  }

  /* Bloco Centralizado (Título, Subtítulo e Usuário) */
  .topbar-center {
    position: relative;
    z-index: 2;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
  }

  .brand .logo {
    font-size: 30px;
    font-weight: 900;
    letter-spacing: 2px;
    line-height: 1.1;
  }

  .brand .subtitle {
    font-size: 13px;
    opacity: 0.95;
    margin-top: 4px;
  }

  .user-text {
    font-size: 13px;
    margin-top: 8px;
  }

  /* BOTÃO DE SAIR NO CANTO SUPERIOR DIREITO (COM BORDA E ANIMAÇÃO) */
  .btn-logout-top-right {
    position: absolute;
    top: 15px;
    right: 20px;
    z-index: 3;
    background-color:linear-gradient(135deg, #0f7536 0%, #17a2b8 100%); color: #ffffff;;
    color: #ffffff !important;
    padding: 8px 18px;
    border-radius: 20px;
    text-decoration: none;
    font-weight: bold;
    font-size: 13px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    
    /* Borda destacada em amarelo/dourado */
    border: 2px solid; 
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);

    /* Animação Suave */
    transition: transform 0.25s cubic-bezier(0.175, 0.885, 0.32, 1.275), 
                background-color 0.2s ease, 
                box-shadow 0.25s ease,
                border-color 0.2s ease;
  }

  /* Animação ao passar o mouse (Hover) */
  .btn-logout-top-right:hover {
    background-color: #c82333;
    border-color: #ffffff; /* Borda brilha em branco */
    transform: translateY(-3px) scale(1.05); /* Sobe e cresce levemente */
    box-shadow: 0 6px 15px rgba(0, 0, 0, 0.4), 0 0 10px rgba(241, 171, 8, 0.5); /* Sombra + Glow */
  }

  /* Animação ao clicar */
  .btn-logout-top-right:active {
    transform: translateY(-1px) scale(0.98);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}
  </style>
</head>

<body>

  <!-- Header Principal com Topbar estilo Sabiá -->
<header style="width: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 20px 15px; background: linear-gradient(135deg, #0f7536 0%, #17a2b8 100%); color: #ffffff;">
  
  <!-- TÍTULO E SUBTÍTULO CENTRALIZADOS -->
  <div style="display: flex; flex-direction: column; align-items: center; margin-bottom: 12px;">
    <div style="font-size: 32px; font-weight: 900; letter-spacing: 2px;">
      <span style="color:#f1ab08;">SABIÁ</span>
    </div>
    <div style="font-size: 14px; opacity: 0.95; font-weight: 500; margin-top: 4px;">
      Sistema de Acompanhamento e Busca de Informações Acadêmicas — 2026
    </div>
  </div>

  <!-- USUÁRIO E BOTÃO LOGOUT CENTRALIZADOS -->
  <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px;">
    <div style="font-size: 14px; text-align: center;">
      Usuário: <strong><?= htmlspecialchars($_SESSION['usuario_nome']) ?></strong> 
      <small style="opacity: 0.85;">(<?= $isAdmin ? 'Administrador' : ($isPdt ? 'PDT' : 'Professor') ?>)</small>
    </div>
<a href="logout.php" class="btn-logout-top-right" title="Sair do Sistema">
    🚪 Sair
  </a>
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

  <!-- Conteúdo da página de notificações -->
  <div class="container page-content">
    <div class="page-title">Notificações</div>
    <div class="page-subtitle">Registros feitos em alunos, filtrados por curso, série, tipo e período.</div>

    <!-- Cards de resumo (total, atrasos, ocorrências, outros) -->
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

    <!-- Filtros: curso, série, período inicial, período final -->
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

    <!-- Botões de ação: filtrar, voltar ao histórico (e "apagar tudo" adicionado via JS) -->
    <div class="class-header">
      <div class="action-group" id="action-group-container">
        <button type="button" class="btn btn-primary btn-action" onclick="renderRegistros()">
          <i class="fa-solid fa-filter"></i>
          Filtrar registros
        </button>
        <button type="button" class="btn btn-primary btn-action" onclick="location.href='historico.php'">
          <i class="fa-solid fa-circle-check"></i>
          Voltar ao histórico
        </button>
      </div>
    </div>

    <!-- Card com a lista de notificações encontradas -->
    <div class="card">
      <div class="filter-section-title">Registros encontrados</div>
      <!-- Lista injetada dinamicamente pelo JS -->
      <div id="lista-notifs" class="notif-list">
        <p style="text-align:center;color:#aaa;padding:40px;font-size:14px;">Carregando...</p>
      </div>
    </div>

  </div><!-- fim do container -->

  <!-- Navegação inferior (visível apenas em mobile) -->
  <nav class="bottom-nav">
    <a href="index.php">
      <span class="nav-icon">🏠</span>
      <span class="nav-text">Início</span>
    </a>
    <a href="registro.php">
      <span class="nav-icon">📝</span>
      <span class="nav-text">Registro</span>
    </a>
    <a href="cadastro.php">
      <span class="nav-icon">➕</span>
      <span class="nav-text">Cadastrar</span>
    </a>
    <a href="historico.php" class="active">
      <span class="nav-icon">⏳</span>
      <span class="nav-text">Histórico</span>
    </a>
  </nav>

  <script>
    // ARRAY DE DADOS DO BANCO INJETADO PELO PHP
    let TODOS_REGISTROS = <?php echo json_encode($registrosBanco, JSON_UNESCAPED_UNICODE); ?>;

    // Matrícula vinda da URL via PHP
    const filterMatricula = "<?php echo $matricula; ?>";

    // Mapeamento de nome do mês para número
    const MESES_MAP = {
      janeiro: 1, fevereiro: 2, março: 3, abril: 4, maio: 5, junho: 6,
      julho: 7, agosto: 8, setembro: 9, outubro: 10, novembro: 11, dezembro: 12
    };

    // Ícones de emoji por tipo de ocorrência
    const ICONES = {
      'Tolerância': '⏰',
      'Ocorrência': '⚠️',
      'Notificação': '🔔',
      'Suspensão': '🚫',
      'Saída Antecipada': '🚪',
      'Fardamento': '👔'
    };

    // Abre/fecha o menu hambúrguer no mobile
    function toggleMenu() {
      document.getElementById('navLinks').classList.toggle('active');
    }

    // Converte data de "YYYY-MM-DD" pra "DD/MM/YYYY" (formato brasileiro)
    function formatarDataParaExibir(dataStr) {
      if (!dataStr) return '—';
      const p = dataStr.split('-');
      if (p.length === 3) return `${p[2]}/${p[1]}/${p[0]}`;
      return dataStr;
    }

    // Atualiza os 4 cards de resumo com as contagens da lista exibida
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

    // Filtra os dados carregados do PHP de acordo com o formulário
    function renderRegistros() {
      const curso = document.getElementById('curso').value;
      const turma = document.getElementById('turma').value;
      const periodoInicio = document.getElementById('periodo').value;
      const periodoFim = document.getElementById('periodo-fim').value;

      let registrosFiltrados = [...TODOS_REGISTROS];

      // Filtro de Curso
      if (curso) {
        registrosFiltrados = registrosFiltrados.filter(r => r.curso.toLowerCase() === curso.toLowerCase());
      }

      // Filtro de Série/Turma
      if (turma) {
        registrosFiltrados = registrosFiltrados.filter(r => r.turma.toLowerCase() === turma.toLowerCase());
      }

      // Filtro de Matrícula (vindo do histórico)
      if (filterMatricula) {
        registrosFiltrados = registrosFiltrados.filter(r => r.matricula === filterMatricula);
      }

      // Filtro de Período (Mês inicial e Mês final)
      if (periodoInicio && periodoFim) {
        const mInicio = MESES_MAP[periodoInicio.toLowerCase()];
        const mFim = MESES_MAP[periodoFim.toLowerCase()];
        if (mInicio && mFim) {
          registrosFiltrados = registrosFiltrados.filter(r => r.mes_num >= mInicio && r.mes_num <= mFim);
        }
      }

      const lista = document.getElementById('lista-notifs');

      if (!registrosFiltrados.length) {
        lista.innerHTML = '<p style="text-align:center;color:#aaa;padding:40px;font-size:14px;">Nenhum registro encontrado.</p>';
      } else {
        // Renderiza cada notificação como um card
        lista.innerHTML = registrosFiltrados.map(r => {
          const tipoIcon = ICONES[r.tipo] || ICONES[r.tipo_ocorrencia] || '📢';
          const tipoClasse = (r.tipo === 'Tolerância')
            ? 'atraso'
            : (r.tipo === 'Ocorrência' || r.tipo === 'Notificação' || r.tipo === 'Suspensão')
              ? 'ocorrencia'
              : 'info';
          const dataExibicao = formatarDataParaExibir(r.data_registro);

          return `
            <div class="notif-card" id="card-notif-${r.id}">
              <div class="notif-icon ${tipoClasse}">${tipoIcon}</div>
              <div class="notif-body">
                <strong>${r.aluno_nome || 'Aluno'}</strong>
                <span>${r.tipo_ocorrencia || 'Registro'} — ${r.observacoes}</span>
                <div class="notif-meta">
                  <span class="meta-chip">Curso: ${r.curso}</span>
                  <span class="meta-chip">Série: ${r.turma}</span>
                  <span class="meta-chip">Tipo: ${r.tipo_ocorrencia}</span>
                  ${r.motivo_saida ? `<span class="meta-chip">Motivo: ${r.motivo_saida}</span>` : ''}
                </div>
              </div>
              <div class="notif-time" style="display:flex;flex-direction:column;justify-content:space-between;align-items:flex-end;">
                <div>${dataExibicao}<br>${r.hora_registro || '—'}</div>
                <!-- Botão de excluir esse registro específico -->
                <button onclick="deletarRegistroIndividual(${r.id})" title="Excluir este registro">✕</button>
              </div>
            </div>`;
        }).join('');
      }

      // Atualiza os cards de resumo
      renderStats(registrosFiltrados);
    }

    // Exclui um registro específico localmente da interface
    function deletarRegistroIndividual(id) {
      if (confirm('Deseja realmente apagar este registro?')) {
        TODOS_REGISTROS = TODOS_REGISTROS.filter(r => r.id !== id);
        renderRegistros();
      }
    }

    // Exclui TODOS os registros localmente
    function clearAllRegistros() {
      if (!confirm('Deseja apagar todos os registros da visualização? Essa ação não pode ser desfeita.')) return;
      TODOS_REGISTROS = [];
      renderRegistros();
    }

    // Ao carregar a página: renderiza os dados
    window.addEventListener('load', () => {
      renderRegistros();

      // Adiciona o botão "Apagar tudo" no grupo de ações
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