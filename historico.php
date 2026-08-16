<?php
session_start();

// Redireciona para o login caso o usuário não esteja autenticado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$paginaAtual = basename($_SERVER['PHP_SELF']);
$isAdmin = isset($_SESSION['usuario_nivel']) && $_SESSION['usuario_nivel'] === 'admin';
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8" />
  <!-- viewport: fundamental pra funcionar direito no celular -->
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>SABIÁ — EEEP José de Barcelos</title>
  <!-- FontAwesome: ícones nos botões e badges -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="style.css" />
  <style>
    /* limita a largura dessa página */
    .container {
      max-width: 1150px;
    }

    /* caixa de filtros (curso/série) com borda e sombra suave */
    .filter-section {
      background: #fff;
      border-radius: 6px;
      padding: 20px 24px;
      margin-bottom: 20px;
      box-shadow: 0 1px 4px rgba(0, 0, 0, .07);
    }

    /* título dos filtros (ex: "CURSO", "SÉRIE") */
    .filter-section-title {
      font-size: 13px;
      font-weight: 700;
      color: #555;
      text-transform: uppercase;
      letter-spacing: .5px;
      margin-bottom: 10px;
    }

    /* grupo de botões dos filtros */
    .btn-filter-group {
      display: flex;
      flex-wrap: wrap;
      /* quebra linha se não couber */
      gap: 8px;
    }

    /* botão de filtro individual */
    .btn-filter {
      padding: 7px 16px;
      border: 2px solid #dde2e8;
      border-radius: 4px;
      background: #fff;
      font-size: 13px;
      font-weight: 700;
      color: #555;
      cursor: pointer;
      font-family: inherit;
      transition: all .15s;
    }

    .btn-filter:hover {
      border-color: #158a2f;
      color: #158a2f;
      background: #f0faf4;
    }

    .btn-filter.active {
      border-color: #158a2f;
      color: #fff;
      background: #158a2f;
    }

    /* linha com badge da turma selecionada + botão de relatório */
    .class-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      /* empilha no mobile */
      gap: 10px;
      margin-bottom: 20px;
    }

    /* badge verde "Informática — 1º Ano" */
    .class-badge {
      background: #158a2f;
      color: #fff;
      font-size: 13px;
      font-weight: 700;
      padding: 5px 14px;
      border-radius: 4px;
      letter-spacing: .5px;
    }

    /* grade de cards de alunos (auto-fill pra ser responsiva naturalmente) */
    .students-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
      gap: 16px;
    }

    /* card individual de cada aluno */
    .student-card {
      background: #fff;
      border-radius: 6px;
      padding: 18px;
      box-shadow: 0 1px 4px rgba(0, 0, 0, .07);
      border-top: 3px solid #158a2f;
      display: flex;
      flex-direction: column;
      gap: 12px;
    }

    /* linha do topo do card com avatar e nome */
    .card-profile-header {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    /* avatar circular com inicial do nome */
    .profile-avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: linear-gradient(135deg, #158a2f, #2bb389);
      color: #fff;
      font-weight: 800;
      font-size: 18px;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }

    .student-name {
      font-size: 14px;
      font-weight: 700;
      color: #222;
      margin: 0;
    }

    .student-meta {
      font-size: 12px;
      color: #888;
    }

    /* linha com os três indicadores (ADV, OCO, NOT) */
    .indicators-row {
      display: flex;
      gap: 8px;
    }

    /* cada bloquinho de indicador com pontinhos coloridos */
    .indicator-pill {
      flex: 1;
      background: #f8fafb;
      border: 1px solid #e8ecf0;
      border-radius: 4px;
      padding: 6px 8px;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 4px;
    }

    .pill-label {
      font-size: 10px;
      font-weight: 700;
      color: #888;
      text-transform: uppercase;
      letter-spacing: .4px;
    }

    /* wrapper dos pontinhos de status */
    .dots-wrapper {
      display: flex;
      gap: 3px;
    }

    /* ponto cinza = inativo, vermelho = ativo (registro existente) */
    .status-dot {
      width: 9px;
      height: 9px;
      border-radius: 50%;
      background: #e0e0e0;
    }

    .dot-active {
      background: #d93025;
    }

    /* botão de detalhes de cada aluno */
    .btn-view {
      display: block;
      text-align: center;
      padding: 7px;
      background: #f0faf4;
      border: 1px solid #c3e6cb;
      border-radius: 4px;
      color: #158a2f;
      font-size: 12px;
      font-weight: 700;
      text-decoration: none;
      transition: background .15s;
    }

    .btn-view:hover {
      background: #d4edda;
    }

    /* linha de controles acima da grade (busca + toggle) */
    .section-controls {
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 12px;
      margin-bottom: 16px;
    }

    /* campo de busca com ícone de lupa */
    .search-box {
      display: flex;
      align-items: center;
      gap: 8px;
      border: 2px solid #dde2e8;
      border-radius: 4px;
      padding: 6px 12px;
      background: #fff;
      transition: border-color .15s;
      flex: 1;
      /* ocupa o espaço disponível no mobile */
      max-width: 340px;
    }

    .search-box:focus-within {
      border-color: #158a2f;
    }

    .search-box i {
      color: #aaa;
      font-size: 13px;
    }

    .search-box input {
      border: none;
      outline: none;
      font-size: 13px;
      font-family: inherit;
      width: 100%;
      /* não deixa o input escapar */
      background: transparent;
    }

    /* botão de mostrar/ocultar a grade de alunos */
    .btn-toggle {
      padding: 7px 12px;
      border: 2px solid #dde2e8;
      border-radius: 4px;
      background: #fff;
      cursor: pointer;
      color: #555;
      font-size: 13px;
      transition: all .15s;
    }

    .btn-toggle:hover {
      border-color: #158a2f;
      color: #158a2f;
    }

    /* no mobile, cards ocupam 1 coluna só */
    @media (max-width: 580px) {
      .students-grid {
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

  <!-- Conteúdo da página de histórico -->
  <main class="container page-content">
    <div class="page-title"> Status dos Alunos</div>
    <div class="page-subtitle">Visão disciplinar consolidada por turma e série.</div>

    <!-- Filtro de curso -->
    <div class="filter-section">
      <div class="filter-section-title"><i class="fa-solid fa-graduation-cap"></i> Curso</div>
      <div class="btn-filter-group" id="courseButtons">
        <button class="btn-filter" onclick="selectCourse(this,'Administração')">Administração</button>
        <button class="btn-filter" onclick="selectCourse(this,'Enfermagem')">Enfermagem</button>
        <button class="btn-filter" onclick="selectCourse(this,'Estética')">Estética</button>
        <button class="btn-filter" onclick="selectCourse(this,'Finanças')">Finanças</button>
        <!-- Informática começa selecionado por padrão -->
        <button class="btn-filter active" onclick="selectCourse(this,'Informática')">Informática</button>
      </div>
    </div>

    <!-- Filtro de série -->
    <div class="filter-section">
      <div class="filter-section-title"><i class="fa-solid fa-layer-group"></i> Série</div>
      <div class="btn-filter-group" id="yearButtons">
        <!-- 1º Ano começa selecionado por padrão -->
        <button class="btn-filter active" onclick="selectYear(this,'1º Ano')">1º Ano</button>
        <button class="btn-filter" onclick="selectYear(this,'2º Ano')">2º Ano</button>
        <button class="btn-filter" onclick="selectYear(this,'3º Ano')">3º Ano</button>
      </div>
    </div>

    <!-- Badge com o filtro atual + link pro relatório completo -->
    <div class="class-header">
      <span class="class-badge" id="selectedClass">Informática — 1º Ano</span>
      <a href="notificacoes.php" style="text-decoration: none;">
        <button type="submit" class="btn btn-primary">
          <i class="fa-solid fa-circle-check"></i>
          Acessar Relatório Completo
        </button>
      </a>
    </div>

    <!-- Cards de resumo da turma selecionada -->
    <div class="stats-row" style="margin-bottom:24px;">
      <div class="stat-card blue">
        <div class="stat-num" id="totalAlunos">0</div>
        <div class="stat-label"><i class="fa-solid fa-users"></i> Alunos Matriculados</div>
      </div>
      <div class="stat-card orange">
        <div class="stat-num" id="totalAdv">0</div>
        <div class="stat-label"><i class="fa-solid fa-triangle-exclamation"></i> Advertencias (ADV)</div>
      </div>
      <div class="stat-card red">
        <div class="stat-num" id="totalOco">0</div>
        <div class="stat-label"><i class="fa-solid fa-circle-exclamation"></i> Ocorrências (OCO)</div>
      </div>
      <div class="stat-card">
        <div class="stat-num" id="totalNot">0</div>
        <div class="stat-label"><i class="fa-solid fa-bell"></i> Notificações (NOT)</div>
      </div>
    </div>

    <!-- Card com a grade de alunos -->
    <div class="card">
      <div class="section-controls">
        <div class="card-title" style="margin-bottom:0;">
          <i class="fa-solid fa-address-book"></i> Status Disciplinar
        </div>
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
          <!-- Campo de busca por nome -->
          <div class="search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" placeholder="Buscar aluno pelo nome..." id="searchInput" oninput="filterStudents()">
          </div>
          <!-- Botão de recolher/expandir a lista -->
          <button class="btn-toggle" onclick="toggleStudents()" id="toggleBtn" title="Mostrar / Ocultar">
            <i class="fa-solid fa-chevron-down"></i>
          </button>
        </div>
      </div>

      <!-- Aqui os cards dos alunos são injetados via JavaScript -->
      <div class="students-grid" id="students"></div>
    </div>

  </main>

  <nav class="bottom-nav">
    <a href="index.php" class="active">
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
    // Estado atual dos filtros
    let selectedCourse = 'Informática';
    let selectedYear = '1º Ano';
    let listVisible = true;
    let studentsData = []; // dados carregados da API

    // Abre/fecha o menu hambúrguer no mobile
    function toggleMenu() {
      document.getElementById('navLinks').classList.toggle('active');
    }

    // Cria os 3 pontinhos de indicador (preenchidos = registros existentes)
    function createDots(qty) {
      let h = '';
      for (let i = 1; i <= 3; i++) {
        h += `<div class="status-dot ${i <= qty ? 'dot-active' : ''}"></div>`;
      }
      return h;
    }

    // Busca os dados dos alunos da turma selecionada na API
    async function loadStudents() {
      try {
        const res = await fetch(`api_historico.php?curso=${encodeURIComponent(selectedCourse)}&turma=${encodeURIComponent(selectedYear)}`);
        studentsData = await res.json();
        renderStudents();
      } catch (e) {
        console.error('Erro ao buscar dados:', e);
      }
    }

    // Renderiza os cards de aluno no DOM, opcionalmente com filtro de nome
    function renderStudents(filter = '') {
      const container = document.getElementById('students');
      container.innerHTML = '';

      let totalAdv = 0, totalOco = 0, totalNot = 0;

      // Filtra por nome se o campo de busca estiver preenchido
      const list = studentsData.filter(s =>
        s.name.toLowerCase().includes(filter.toLowerCase())
      );

      list.forEach(s => {
        // Acumula totais pra atualizar os cards de resumo
        totalAdv += s.Adv;
        totalOco += s.oco;
        totalNot += s.noti;

        // Cria o card do aluno dinamicamente
        const card = document.createElement('div');
        card.className = 'student-card';
        card.innerHTML = `
          <div class="card-profile-header">
            <div class="profile-avatar">${s.name.charAt(0)}</div>
            <div>
              <p class="student-name">${s.name}</p>
              <p class="student-meta">${s.curso} · ${s.turma}</p>
            </div>
          </div>
          <div class="indicators-row">
            <div class="indicator-pill">
              <span class="pill-label">ADV</span>
              <div class="dots-wrapper">${createDots(s.Adv)}</div>
            </div>
            <div class="indicator-pill">
              <span class="pill-label">OCO</span>
              <div class="dots-wrapper">${createDots(s.oco)}</div>
            </div>
            <div class="indicator-pill">
              <span class="pill-label">NOT</span>
              <div class="dots-wrapper">${createDots(s.noti)}</div>
            </div>
          </div>
          <a href="notificacoes.php?matricula=${s.matricula}" class="btn-view">
            <i class="fa-solid fa-clock-rotate-left"></i> Consultar Detalhes
          </a>`;
        container.appendChild(card);
      });

      // Atualiza os cards de resumo só quando não está filtrando
      if (!filter) {
        document.getElementById('totalAlunos').textContent = studentsData.length;
        document.getElementById('totalAdv').textContent = totalAdv;
        document.getElementById('totalOco').textContent = totalOco;
        document.getElementById('totalNot').textContent = totalNot;
      }
    }

    // Chamada pelo input de busca: filtra alunos pelo nome digitado
    function filterStudents() {
      renderStudents(document.getElementById('searchInput').value);
    }

    // Atualiza o badge da turma e recarrega os alunos
    function updateClassTitle() {
      document.getElementById('selectedClass').textContent = `${selectedCourse} — ${selectedYear}`;
      document.getElementById('searchInput').value = '';
      loadStudents();
    }

    // Muda o curso selecionado e recarrega
    function selectCourse(btn, course) {
      document.querySelectorAll('#courseButtons .btn-filter').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      selectedCourse = course;
      updateClassTitle();
    }

    // Muda a série selecionada e recarrega
    function selectYear(btn, year) {
      document.querySelectorAll('#yearButtons .btn-filter').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      selectedYear = year;
      updateClassTitle();
    }

    // Mostra/oculta a grade de cards de alunos
    function toggleStudents() {
      const area = document.getElementById('students');
      const btn = document.getElementById('toggleBtn');
      listVisible = !listVisible;
      area.style.display = listVisible ? 'grid' : 'none';
      // Gira a setinha conforme o estado
      btn.querySelector('i').style.transform = listVisible ? 'rotate(0deg)' : 'rotate(-180deg)';
    }

    // Carrega os dados ao abrir a página
    window.onload = loadStudents;
  </script>
</body>

</html>
