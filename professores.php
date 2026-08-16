<?php
session_start();

if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['usuario_nivel']) || $_SESSION['usuario_nivel'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$paginaAtual = basename($_SERVER['PHP_SELF']);
$isAdmin = true;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>SABIÁ — Gerenciar Professores e PDT</title>
  <link rel="stylesheet" href="style.css" />
  <style>   .topbar {
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
}</style>
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
      <div class="nav-links">
        <a href="index.php">INÍCIO</a>
        <a href="registro.php">REGISTRO</a>
        <a href="cadastro.php">CADASTRAR ALUNOS</a>
        <a href="historico.php">HISTÓRICO</a>
        <a href="professores.php" class="active">PROFESSORES</a>
      </div>
    </div>
  </nav>

  <div class="container page-content">
    <div class="page-title">Cadastrar Professor / PDT</div>
    <div class="page-subtitle">Cadastre novos professores e defina o curso e série vinculados ao PDT.</div>

    <div class="card">
      <div class="form-row">
        <div class="field">
          <label>Nome do Professor *</label>
          <input type="text" id="p_nome" placeholder="Ex: Prof. Carlos Silva" required />
        </div>
        <div class="field">
          <label>Nome de Usuário *</label>
          <input type="text" id="p_usuario" placeholder="Ex: prof_carlos" required />
        </div>
        <div class="field">
          <label>Senha *</label>
          <input type="password" id="p_senha" placeholder="Digite a senha" required />
        </div>
      </div>

      <!-- MARCAÇÃO E SELEÇÃO DE TURMA PDT -->
      <div style="margin-top: 15px;">
        <div class="field" style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
          <input type="checkbox" id="p_pdt" onchange="toggleTurmaInput()" style="width: auto; cursor: pointer;" />
          <label for="p_pdt" style="margin: 0; cursor: pointer; font-weight: bold;">É Professor Diretor de Turma (PDT)?</label>
        </div>

        <!-- CAIXAS DISTINTAS (CURSO E SÉRIE) -->
        <div id="box_turma" class="form-row" style="display: none; margin-bottom: 14px;">
          <div class="field">
            <label>Curso *</label>
            <select name="curso" id="curso">
              <option value="">Selecione o curso...</option>
              <option value="Informática">Informática</option>
              <option value="Finanças">Finanças</option>
              <option value="Enfermagem">Enfermagem</option>
              <option value="Estética">Estética</option>
              <option value="Administração">Administração</option>
            </select>
          </div>

          <div class="field">
            <label>Série *</label>
            <select name="serie" id="serie">
              <option value="">Selecione a série...</option>
              <option value="1º Ano">1º Ano</option>
              <option value="2º Ano">2º Ano</option>
              <option value="3º Ano">3º Ano</option>
            </select>
          </div>
        </div>
      </div>

      <button class="btn btn-primary" style="margin-top: 10px;" onclick="cadastrarProfessor()">✔ Cadastrar Professor</button>
    </div>

    <div class="card" style="margin-top: 20px;">
      <div class="card-title">Professores Cadastrados</div>
      
      <div style="overflow-x: auto; margin-top: 15px;">
        <table class="data-table" style="width: 100%;">
          <thead>
            <tr>
              <th>Nome</th>
              <th>Usuário</th>
              <th>Cargo / Função</th>
              <th>Turma Dirigida (PDT)</th>
            </tr>
          </thead>
          <tbody id="listaProfessores">
            <tr><td colspan="4" style="text-align: center; padding: 20px;">Carregando...</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <script>
    function toggleTurmaInput() {
      const isPdt = document.getElementById('p_pdt').checked;
      document.getElementById('box_turma').style.display = isPdt ? 'flex' : 'none';
    }

    async function carregarProfessores() {
      try {
        const res = await fetch('api_usuarios.php');
        const professores = await res.json();
        const tbody = document.getElementById('listaProfessores');

        if (!Array.isArray(professores) || professores.length === 0) {
          tbody.innerHTML = '<tr><td colspan="4" style="text-align: center; padding: 20px;">Nenhum professor cadastrado.</td></tr>';
          return;
        }

        tbody.innerHTML = professores.map(p => {
          let turmaTexto = '—';
          if (p.is_pdt == 1 && p.pdt_serie && p.pdt_curso) {
            turmaTexto = `${p.pdt_serie} - ${p.pdt_curso}`;
          }

          return `
            <tr>
              <td><strong>${p.nome}</strong></td>
              <td><code>${p.usuario}</code></td>
              <td>${p.is_pdt == 1 ? '<span style="color:#158a2f; font-weight:bold;">PDT</span>' : 'Professor Comum'}</td>
              <td>${turmaTexto}</td>
            </tr>
          `;
        }).join('');
      } catch (e) {
        document.getElementById('listaProfessores').innerHTML = '<tr><td colspan="4" style="text-align: center; padding: 20px; color: #dc3545;">Erro ao carregar lista.</td></tr>';
      }
    }

    async function cadastrarProfessor() {
      const nome = document.getElementById('p_nome').value.trim();
      const usuario = document.getElementById('p_usuario').value.trim();
      const senha = document.getElementById('p_senha').value.trim();
      const is_pdt = document.getElementById('p_pdt').checked;
      const pdt_curso = document.getElementById('curso').value;
      const pdt_serie = document.getElementById('serie').value;

      if (!nome || !usuario || !senha) {
        alert('Por favor, preencha os campos obrigatórios de identificação!');
        return;
      }

      if (is_pdt && (!pdt_curso || !pdt_serie)) {
        alert('Selecione o Curso e a Série para o Professor Diretor de Turma (PDT)!');
        return;
      }

      const res = await fetch('api_usuarios.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ nome, usuario, senha, is_pdt, pdt_curso, pdt_serie })
      });
      const data = await res.json();

      if (data.success) {
        alert('Professor/PDT cadastrado com sucesso!');
        location.reload();
      } else {
        alert(data.message || 'Erro ao cadastrar.');
      }
    }

    window.onload = carregarProfessores;
  </script>
</body>
</html>