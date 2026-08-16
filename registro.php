<?php
session_start();

// Redireciona para o login caso o usuário não esteja autenticado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$paginaAtual = basename($_SERVER['PHP_SELF']);
$isAdmin = isset($_SESSION['usuario_nivel']) && $_SESSION['usuario_nivel'] === 'admin';
// Verifica o nível e a flag do usuário na sessão
$isAdmin = isset($_SESSION['usuario_nivel']) && $_SESSION['usuario_nivel'] === 'admin';
$isPdt   = isset($_SESSION['usuario_pdt']) && (int)$_SESSION['usuario_pdt'] === 1;

// Define se o usuário tem permissão total (Admin ou PDT)
$temAcessoTotal = $isAdmin || $isPdt;
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8" />
  <!-- viewport: essencial pra não travar o zoom no mobile -->
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title> SABIÁ — Registrar Ocorrência</title>
  <!-- FontAwesome: ícones que aparecem nos botões e cards -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="style.css">
  <style>
    /* largura máxima dessa página */
    .container {
      max-width: 1100px;
    }

    /* layout de 2 colunas: formulário + caixinha de instruções */
    .registro-layout {
      display: grid;
      grid-template-columns: 1fr 300px;
      gap: 24px;
      align-items: start;
    }

    /* grade dos 4 tipos de ocorrência (tolerância, notificação, etc.) */
    .tipo-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 10px;
      margin-bottom: 22px;
    }

    /* cada box clicável de tipo de ocorrência */
    .box-tipo {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 6px;
      padding: 14px 10px;
      border: 2px solid #dde2e8;
      border-radius: 6px;
      cursor: pointer;
      font-size: 12px;
      font-weight: 700;
      color: #666;
      text-align: center;
      transition: all .15s;
      background: #fff;
      user-select: none;
      /* evita seleção de texto ao clicar */
    }

    .box-tipo i {
      font-size: 20px;
      color: #aaa;
      transition: color .15s;
    }

    /* hover e estado ativo ficam verdes */
    .box-tipo:hover {
      border-color: #158a2f;
      color: #158a2f;
    }

    .box-tipo:hover i {
      color: #158a2f;
    }

    .box-tipo.active {
      border-color: #158a2f;
      background: #f0faf4;
      color: #158a2f;
    }

    .box-tipo.active i {
      color: #158a2f;
    }

    /* label pequeno acima das seções do formulário */
    .form-section-label {
      font-size: 11px;
      font-weight: 700;
      color: #888;
      text-transform: uppercase;
      letter-spacing: .6px;
      margin-bottom: 10px;
    }

    /* botões de ação do formulário (salvar, limpar) */
    .form-actions {
      display: flex;
      gap: 10px;
      margin-top: 6px;
      flex-wrap: wrap;
    }

    /* toast local de sucesso/erro */
    .toast {
      position: fixed;
      top: 20px;
      right: 20px;
      display: none;
      min-width: 260px;
      max-width: 90vw;
      padding: 14px 18px;
      border-radius: 8px;
      color: #fff;
      font-weight: 700;
      z-index: 9999;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.18);
      animation: fadeIn 0.25s ease;
    }

    .toast.success {
      background: #28a745;
    }

    .toast.error {
      background: #dc3545;
    }

    /* caixa verde de instruções na sidebar */
    .info-box {
      background: #f0faf4;
      border-left: 4px solid #158a2f;
      padding: 18px;
      border-radius: 6px;
      font-size: 13px;
      line-height: 1.65;
      color: #333;
    }

    .info-box strong {
      color: #158a2f;
    }

    .info-box .info-title {
      font-size: 14px;
      font-weight: 700;
      color: #158a2f;
      margin-bottom: 10px;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    /* grade de campos do formulário */
    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 14px;
    }

    .form-row.full {
      grid-template-columns: 1fr;
    }

    /* campos individuais */
    .field label {
      display: block;
      margin-bottom: 6px;
      font-size: 13px;
      font-weight: 700;
      color: #555;
    }

    .field input,
    .field select,
    .field textarea {
      width: 100%;
      padding: 12px 14px;
      border: 1px solid #d7dde4;
      border-radius: 6px;
      background: #fff;
      outline: none;
      font-size: 14px;
      transition: border-color .15s, box-shadow .15s;
      font-family: inherit;
    }

    .field input:focus,
    .field select:focus,
    .field textarea:focus {
      border-color: #158a2f;
      box-shadow: 0 0 0 3px rgba(21, 138, 47, 0.08);
    }

    /* caixa de dados do responsável que aparece ao selecionar um aluno */
    .responsavel-box {
      display: none;
      /* começa escondida */
      margin-top: 10px;
      padding: 14px 16px;
      background: #f8fafb;
      border: 1px solid #dfe7df;
      border-left: 4px solid #158a2f;
      border-radius: 6px;
      font-size: 13px;
      line-height: 1.7;
      color: #333;
    }

    .responsavel-box strong {
      color: #158a2f;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(-10px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    /* tablet: sidebar vai embaixo do formulário */
    @media (max-width: 800px) {
      .registro-layout {
        grid-template-columns: 1fr;
      }

      .tipo-grid {
        grid-template-columns: 1fr 1fr;
      }
    }

    /* mobile: campos e tipo-grid em coluna única */
    @media (max-width: 500px) {
      .tipo-grid {
        grid-template-columns: 1fr 1fr;
      }

      .form-row {
        grid-template-columns: 1fr;
      }

      .form-actions .btn {
        width: 100%;
        justify-content: center;
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

  <!-- Topo verde com o logo -->
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

  <!-- Navegação entre páginas -->
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
  <!-- Mensagem de sucesso/erro que aparece e desaparece -->
  <div class="toast" id="toast"></div>

  <!-- Área principal da página de registro -->
  <main class="container page-content">
    <div class="page-title">Registrar Ocorrência</div>
    <div class="page-subtitle">Selecione o tipo de ocorrência e preencha os dados do estudante correspondente.</div>

    <!-- Layout: formulário à esquerda, instruções à direita -->
    <div class="registro-layout">

      <!-- Formulário principal -->
      <div class="card">

        <!-- Seleção do tipo de ocorrência -->
        <div class="form-section-label">Tipo de Ocorrência</div>
        <div class="tipo-grid">
          <!-- Cada box representa um tipo; o onclick seleciona e atualiza o campo hidden -->
           <?php if ($temAcessoTotal): ?>
          <div class="box-tipo active" onclick="selectTipo(this,'Tolerância')">
            <i class="fa-solid fa-clock"></i>
            <span>Tolerância / Atraso</span>
          </div>
          <?php endif; ?>
          <div class="box-tipo" onclick="selectTipo(this,'Notificação')">
            <i class="fa-solid fa-bell"></i>
            <span>Notificação</span>
          </div>
          <?php if ($temAcessoTotal): ?>
          <div class="box-tipo" onclick="selectTipo(this,'Fardamento')">
            <i class="fa-solid fa-shirt"></i>
            <span>Fardamento</span>
          </div>
          <?php endif; ?>
          <?php if ($temAcessoTotal): ?>
          <div class="box-tipo" onclick="selectTipo(this,'Saída Antecipada')">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span>Saída Antecipada</span>
          </div>
          <?php endif; ?>
        </div>

        <!-- Campo oculto que armazena o tipo selecionado pra enviar no formulário -->
        <input type="hidden" id="tipoOcorrenciaInput" value="Tolerância">

        <!-- Dados do estudante -->
        <div class="form-section-label" style="margin-top:4px;">Dados do Estudante</div>
        <form id="formOcorrencia">

          <!-- Filtros de curso e série (que preenchem o select de alunos) -->
          <div class="form-row" style="margin-bottom:14px;">
            <div class="field">
              <label>Curso</label>
              <select name="curso" id="curso" required>
                <option value="">Selecione o curso...</option>
                <option>Informática</option>
                <option>Finanças</option>
                <option>Enfermagem</option>
                <option>Estética</option>
                <option>Administração</option>
              </select>
            </div>
            <div class="field">
              <label>Série</label>
              <select name="serie" id="serie" required>
                <option value="">Selecione a série...</option>
                <option>1º Ano</option>
                <option>2º Ano</option>
                <option>3º Ano</option>
              </select>
            </div>
          </div>

          <!-- Select de aluno: preenchido dinamicamente quando curso+série são selecionados -->
          <div class="form-row full" style="margin-bottom:14px;">
            <div class="field">
              <label>Aluno</label>
              <select name="aluno" id="aluno" required>
                <option value="">Selecione o aluno...</option>
              </select>
              <!-- Caixinha com dados do responsável, aparece ao selecionar o aluno -->
              <div id="responsavelBox" class="responsavel-box"></div>
            </div>
          </div>

          <!-- Campos específicos de Saída Antecipada (ficam escondidos por padrão) -->
          <div id="saidaAntecipadaFields" style="display:none;">
            <div class="form-row full" style="margin-bottom:8px;">
              <div class="field">
                <label>Motivo da Saída Antecipada</label>
                <select name="motivo_saida" id="motivoSaida">
                  <option value="">Selecione o motivo...</option>
                  <option>Consulta médica</option>
                  <option>Problema familiar</option>
                  <option>Transporte</option>
                  <option>Atividade externa</option>
                  <option>Outros</option>
                </select>
              </div>
            </div>
            <!-- Campo de texto que aparece só se o motivo for "Outros" -->
            <div class="form-row full" id="outroMotivoBox" style="display:none; margin-bottom:14px;">
              <div class="field">
                <label>Qual o motivo?</label>
                <textarea name="outro_motivo" id="outroMotivo" rows="3" placeholder="Descreva o motivo..."></textarea>
              </div>
            </div>
            <div class="form-row" style="margin-bottom:14px;">
              <div class="field"><label>Data da Saída</label><input type="date" name="data_saida" id="dataSaida"></div>
              <div class="field"><label>Horário da Saída</label><input type="time" name="horario_saida"
                  id="horarioSaida"></div>
            </div>
          </div>

          <!-- Campos de data/hora para os outros tipos de ocorrência -->
          <div class="form-row" style="margin-bottom:14px;" id="ocorrenciaFields">
            <div class="field"><label>Data</label><input type="date" name="data" id="dataOcorrencia"></div>
            <div class="field"><label>Horário</label><input type="time" name="horario" id="horarioChegada"></div>
          </div>

          <!-- Campo de observações livre -->
          <div class="form-row full" style="margin-bottom:18px;">
            <div class="field">
              <label>Observações</label>
              <textarea name="observacoes" id="observacoes" placeholder="Observações adicionais ou justificativas..."
                rows="3"></textarea>
            </div>
          </div>

          <!-- Botões de ação -->
          <div class="form-actions">
            <button type="submit" class="btn btn-primary">
              <i class="fa-solid fa-circle-check"></i> Finalizar Registro
            </button>
            <button type="reset" class="btn btn-ghost">
              <i class="fa-solid fa-trash-can"></i> Limpar
            </button>
          </div>
        </form>
      </div><!-- fim do card do formulário -->

      <!-- Sidebar com instruções de uso -->
      <aside>
        <div class="info-box">
          <div class="info-title"><i class="fa-solid fa-circle-info"></i> Instruções</div>
          <p>Utilize este formulário para registrar atrasos, notificações, irregularidades de fardamento e saídas
            antecipadas.</p><br>
          <p><strong>Atenção:</strong> Os dados salvos aqui serão usados pela página de notificações e histórico.</p>
          <br>
          <hr style="border:0;border-top:1px solid #c3e6cb;margin:4px 0 10px;">
          <p><strong>Tipos de ocorrência:</strong></p>
          <ul style="margin-left:16px;margin-top:6px;line-height:1.9;">
            <li><strong>Tolerância/Atraso</strong> — entrada fora do horário permitido.</li>
            <li><strong>Notificação</strong> — ocorrência disciplinar</li>
            <li><strong>Fardamento</strong> — gera ocorrência direta imediata.</li>
          </ul>
        </div>
      </aside>

    </div><!-- fim do registro-layout -->
  </main>

  <!-- Navegação inferior (visível apenas em mobile) -->
  <nav class="bottom-nav">
    <a href="index.php" >
      <span class="nav-icon">🏠</span>
      <span class="nav-text">Início</span>
    </a>
    <a href="registro.php" class="active">
      <span class="nav-icon">📝</span>
      <span class="nav-text">Registro</span>
    </a>
    <a href="cadastro.php">
      <span class="nav-icon">➕</span>
      <span class="nav-text">Cadastrar</span>
    </a>
    <a href="historico.php">
      <span class="nav-icon">⏳</span>
      <span class="nav-text">Histórico</span>
    </a>
  </nav>


  <script>
    // Lista de alunos carregada da API (usada pra preencher o select de alunos)
    let alunosCadastrados = [];

    // Abre/fecha o menu hamburger no mobile
    function toggleMenu() {
      document.getElementById('navLinks').classList.toggle('active');
    }

    // Carrega todos os alunos cadastrados da API
    async function loadAlunos() {
      try {
        const res = await fetch('api_cadastro.php');
        alunosCadastrados = await res.json();
      } catch (e) {
        showToast('Erro ao carregar lista de alunos', 'error');
      }
    }

    // Exibe uma mensagem de feedback temporária (some após 3 segundos)
    function showToast(msg, type = '') {
      const t = document.getElementById('toast');
      t.textContent = msg;
      t.className = 'toast ' + (type === 'error' ? 'error' : 'success');
      t.style.display = 'block';
      setTimeout(() => { t.style.display = 'none'; }, 3000);
    }

    // Preenche o campo de hora com o horário atual do sistema
    function setHorarioAutomatico() {
      const agora = new Date();
      const horas = String(agora.getHours()).padStart(2, '0');
      const minutos = String(agora.getMinutes()).padStart(2, '0');
      document.getElementById('horarioChegada').value = `${horas}:${minutos}`;
      document.getElementById('horarioSaida').value = `${horas}:${minutos}`;
    }

    // Preenche o campo de data com a data de hoje
    function setDataAutomatica() {
      const agora = new Date();
      const ano = agora.getFullYear();
      const mes = String(agora.getMonth() + 1).padStart(2, '0');
      const dia = String(agora.getDate()).padStart(2, '0');
      const data = `${ano}-${mes}-${dia}`;
      document.getElementById('dataOcorrencia').value = data;
      document.getElementById('dataSaida').value = data;
    }

    // Marca o tipo de ocorrência selecionado e atualiza a interface
    function selectTipo(el, valor) {
      // Remove "active" de todos os boxes
      document.querySelectorAll('.box-tipo').forEach(b => b.classList.remove('active'));
      // Ativa o box clicado
      el.classList.add('active');
      // Atualiza o campo hidden com o valor
      document.getElementById('tipoOcorrenciaInput').value = valor;
      // Mostra/esconde os campos específicos de saída antecipada
      updateSaidaAntecipada();
    }

    // Controla visibilidade dos campos de acordo com o tipo selecionado
    function updateSaidaAntecipada() {
      const tipo = document.getElementById('tipoOcorrenciaInput').value;
      const saidaBox = document.getElementById('saidaAntecipadaFields');
      const ocorrenciaFields = document.getElementById('ocorrenciaFields');
      const motivoSelect = document.getElementById('motivoSaida');
      const outroBox = document.getElementById('outroMotivoBox');
      const outroText = document.getElementById('outroMotivo');

      if (tipo === 'Saída Antecipada') {
        // Só saída antecipada tem campos específicos de motivo/hora de saída
        saidaBox.style.display = 'block';
        ocorrenciaFields.style.display = 'none';
      } else {
        saidaBox.style.display = 'none';
        ocorrenciaFields.style.display = 'grid';
        motivoSelect.value = '';
        outroBox.style.display = 'none';
        outroText.value = '';
      }
    }

    // Preenche o select de alunos baseado no curso e série selecionados
    function preencherAlunos() {
      const curso = document.getElementById('curso').value;
      const serie = document.getElementById('serie').value;
      const alunoSelect = document.getElementById('aluno');
      const responsavelBox = document.getElementById('responsavelBox');

      // Limpa o select e esconde a caixa de responsável
      alunoSelect.innerHTML = '<option value="">Selecione o aluno...</option>';
      responsavelBox.style.display = 'none';
      responsavelBox.innerHTML = '';

      if (!curso || !serie) return; // aguarda os dois campos serem preenchidos

      // Filtra apenas alunos ativos do curso+série selecionados
      const alunos = alunosCadastrados.filter(
        a => a.curso === curso && a.turma === serie && a.status === 'Ativo'
      );

      if (alunos.length === 0) {
        alunoSelect.innerHTML = '<option value="">Nenhum aluno ativo encontrado...</option>';
        return;
      }

      // Adiciona cada aluno como opção no select
      alunos.forEach(aluno => {
        const option = document.createElement('option');
        option.value = aluno.nome;
        option.textContent = `${aluno.matricula} - ${aluno.nome}`;
        option.dataset.matricula = aluno.matricula;
        alunoSelect.appendChild(option);
      });
    }

    // Mostra os dados do responsável quando um aluno é selecionado
    function mostrarResponsavel() {
      const alunoNome = document.getElementById('aluno').value;
      const responsavelBox = document.getElementById('responsavelBox');

      if (!alunoNome) {
        responsavelBox.style.display = 'none';
        responsavelBox.innerHTML = '';
        return;
      }

      const aluno = alunosCadastrados.find(a => a.nome === alunoNome);
      if (!aluno) {
        responsavelBox.style.display = 'none';
        return;
      }

      // Monta o HTML com os dados do(s) responsável(eis)
      let htmlContent = `
        <strong>1º Responsável:</strong> ${aluno.resp_nome || '—'}<br>
        <strong>Telefone R1:</strong> ${aluno.resp_tel || '—'}<br>
        <strong>CPF R1:</strong> ${aluno.cpf_resp1 || '—'}
      `;

      // Adiciona segundo responsável se existir
      if (aluno.resp2_nome) {
        htmlContent += `
          <hr style="border:0; border-top:1px dashed #dfe7df; margin:8px 0;">
          <strong>2º Responsável:</strong> ${aluno.resp2_nome}<br>
          <strong>Telefone R2:</strong> ${aluno.resp2_tel || '—'}<br>
          <strong>CPF R2:</strong> ${aluno.cpf_resp2 || '—'}
        `;
      }

      responsavelBox.innerHTML = htmlContent;
      responsavelBox.style.display = 'block';
    }

    // Quando curso ou série mudar, atualiza lista de alunos
    document.getElementById('curso').addEventListener('change', preencherAlunos);
    document.getElementById('serie').addEventListener('change', preencherAlunos);

    // Quando aluno mudar, mostra os dados do responsável
    document.getElementById('aluno').addEventListener('change', mostrarResponsavel);

    // Se motivo for "Outros", abre campo de texto livre
    document.getElementById('motivoSaida').addEventListener('change', function () {
      const outroBox = document.getElementById('outroMotivoBox');
      if (this.value === 'Outros') {
        outroBox.style.display = 'block';
      } else {
        outroBox.style.display = 'none';
        document.getElementById('outroMotivo').value = '';
      }
    });

    // Na carga da página, inicializa campos de data/hora e carrega alunos
    window.addEventListener('load', function () {
      setHorarioAutomatico();
      setDataAutomatica();
      updateSaidaAntecipada();
      loadAlunos();
    });

    // Envio do formulário de registro
    document.getElementById('formOcorrencia').addEventListener('submit', async function (e) {
      e.preventDefault(); // impede o reload padrão do form

      const form = new FormData(this);
      const tipo = document.getElementById('tipoOcorrenciaInput').value;
      const curso = form.get('curso');
      const serie = form.get('serie');
      const alunoNome = form.get('aluno');

      // Validação básica antes de enviar
      if (!curso || !serie || !alunoNome) {
        showToast('Preencha curso, série e aluno.', 'error');
        return;
      }

      const agora = new Date();
      const dataAtual = agora.toLocaleDateString('pt-BR');
      const horaAtual = agora.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });

      // Busca os dados completos do aluno selecionado (pra pegar a matrícula)
      const alunoObj = alunosCadastrados.find(a => a.nome === alunoNome);

      // Monta o objeto que vai ser enviado para a API
      const registro = {
        tipo_ocorrencia: tipo,
        tipo: tipo, // o servidor vai reclassificar baseado nas regras de horário
        curso,
        turma: serie,
        aluno: alunoNome,
        matricula: alunoObj?.matricula || '',
        // Data e hora dependem do tipo (saída antecipada tem campos próprios)
        data: tipo === 'Saída Antecipada' ? form.get('data_saida') : form.get('data'),
        hora: tipo === 'Saída Antecipada' ? form.get('horario_saida') : form.get('horario'),
        motivo_saida: form.get('motivo_saida') || '',
        outro_motivo: form.get('outro_motivo') || '',
        observacoes: form.get('observacoes') || '',
        criado_em: dataAtual,
        criado_hora: horaAtual,
        lida: false
      };

      // Saída antecipada precisa ter motivo
      if (tipo === 'Saída Antecipada' && !registro.motivo_saida) {
        showToast('Selecione o motivo da saída antecipada.', 'error');
        return;
      }

      try {
        const res = await fetch('api_registro.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(registro)
        });
        const data = await res.json();

        if (data.success) {
          showToast('Ocorrência registrada com sucesso!');
          // Aguarda um pouco e reseta o formulário
          setTimeout(() => {
            this.reset();
            // Volta o tipo selecionado para o padrão (Tolerância)
            document.querySelectorAll('.box-tipo').forEach((b, i) => b.classList.toggle('active', i === 0));
            document.getElementById('tipoOcorrenciaInput').value = 'Tolerância';
            setHorarioAutomatico();
            setDataAutomatica();
            updateSaidaAntecipada();
            preencherAlunos();
            // Esconde a caixa de responsável
            document.getElementById('responsavelBox').style.display = 'none';
            document.getElementById('responsavelBox').innerHTML = '';
          }, 500);
        } else {
          showToast(data.message || 'Erro ao registrar.', 'error');
        }
      } catch (e) {
        showToast('Erro de conexão.', 'error');
      }
    });
  </script>

</body>

</html>
