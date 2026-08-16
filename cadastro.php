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
<!-- Página de cadastro de alunos -->

<head>
  <meta charset="UTF-8" />
  <!-- viewport: essencial pra não travar zoom no mobile -->
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>SABIÁ — EEEP José de Barcelos</title>
  <link rel="stylesheet" href="style.css" />
  <!-- Biblioteca de máscaras de input (CPF, telefone, etc.) -->
  <script src="https://cdn.jsdelivr.net/npm/vanilla-masker@1.2.0/build/vanilla-masker.min.js"></script>

  <style>
    /* largura máxima generosa pra tabela de alunos não comprimir -->
    .container { max-width: 1400px; }

    /* pequeno espaço abaixo de inputs e selects dentro dos formulários */
    input,
    select,
    textarea {
      margin-bottom: 10px;
    }

    /* toast de feedback flutuante (verde = sucesso, vermelho = erro) */
    #toast {
      position: fixed;
      top: 20px;
      right: 20px;
      display: none;
      min-width: 260px;
      max-width: 90vw;
      /* não estoura em mobile */
      padding: 14px 18px;
      border-radius: 8px;
      color: #fff;
      font-weight: 700;
      z-index: 9999;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.18);
      animation: fadeIn 0.25s ease;
    }

    #toast.success {
      background: #28a745;
    }

    #toast.error {
      background: #dc3545;
    }

    /* badges de status do aluno (Ativo, Inativo, Suspenso, etc.) */
    .badge {
      padding: 4px 8px;
      border-radius: 4px;
      font-size: 12px;
      font-weight: bold;
      color: white;
      display: inline-block;
    }

    .badge-green {
      background-color: #28a745;
    }

    .badge-gray {
      background-color: #6c757d;
    }

    .badge-orange {
      background-color: #fd7e14;
    }

    /* botão menor pra tabela */
    .btn-sm {
      padding: 4px 8px;
      font-size: 12px;
      cursor: pointer;
    }

    /* overlay do modal de edição */
    .modal-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.5);
      display: flex;
      align-items: center;
      justify-content: center;
      opacity: 0;
      pointer-events: none;
      transition: opacity 0.25s ease;
      z-index: 10000;
      padding: 16px;
      /* margem no mobile */
    }

    .modal-overlay.open {
      opacity: 1;
      pointer-events: auto;
    }

    /* janela interna do modal */
    .modal {
      background: white;
      padding: 24px;
      border-radius: 8px;
      width: 100%;
      max-width: 750px;
      max-height: 90vh;
      overflow-y: auto;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    }

    /* cabeçalho do modal com título e botão de fechar */
    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 15px;
      border-bottom: 1px solid #eee;
      padding-bottom: 10px;
    }

    .modal-close {
      background: none;
      border: none;
      font-size: 18px;
      cursor: pointer;
    }

    /* campos bloqueados ficam cinzas e não são editáveis */
    input[readonly] {
      background-color: #f1f3f5 !important;
      color: #6c757d !important;
      cursor: not-allowed;
      border: 1px solid #ced4da;
    }

    /* animação de entrada do toast */
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

    /* formulário de 3 campos na mesma linha (full width) */
    .form-row-full {
      display: flex;
      flex-direction: column;
      gap: 0;
    }

    /* tabela de alunos em mobile: scroll horizontal pra não truncar */
    .table-wrap {
      overflow-x: auto;
    }

    /* no mobile, formulários em coluna única */
    @media (max-width: 700px) {
      .form-row {
        grid-template-columns: 1fr;
      }

      .form-row.three {
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
  <!-- Toast: mensagem flutuante de feedback -->
  <div id="toast"></div>

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

  <!-- Barra de navegação -->
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
  <!-- Conteúdo principal: formulário de cadastro -->
  <div class="container page-content">
    <div class="page-title">Cadastrar Aluno</div>
    <div class="page-subtitle">Preencha os dados abaixo para adicionar um novo aluno ao sistema.</div>

    <!-- Card com o formulário de cadastro -->
    <div class="card">
      <h3 style="color:#158a2f;margin-bottom:20px;font-size:18px;">📋 Dados Pessoais</h3>
      <div class="form-row-full">

        <!-- Nome completo -->
        <div class="field">
          <label>Nome Completo *</label>
          <input type="text" id="nome" placeholder="Ex: João da Silva" required />
        </div>

        <!-- CPF do aluno e matrícula lado a lado -->
        <div class="form-row">
          <div class="field">
            <label for="cpf_aluno">CPF do Aluno *</label>
            <input type="text" id="cpf_aluno" placeholder="Ex: 123.456.789-10" required />
          </div>
          <div class="field">
            <label>Matrícula *</label>
            <input type="text" id="matricula" maxlength="7" placeholder="Ex: 2026001" required />
          </div>
        </div>

        <!-- Data de nascimento e sexo -->
        <div class="form-row">
          <div class="field">
            <label>Data de Nascimento *</label>
            <input type="date" id="nascimento" required />
          </div>
          <div class="field">
            <label>Sexo *</label>
            <select id="sexo" required>
              <option value="">Selecione</option>
              <option>Masculino</option>
              <option>Feminino</option>
              <option>Outro</option>
            </select>
          </div>
        </div>

        <!-- Curso e série -->
        <div class="form-row">
          <div class="field">
            <label>Curso *</label>
            <select id="curso" required>
              <option value="">Selecione</option>
              <option>Administração</option>
              <option>Enfermagem</option>
              <option>Estética</option>
              <option>Finanças</option>
              <option>Informática</option>
            </select>
          </div>
          <div class="field">
            <label>Série *</label>
            <select id="turma" required>
              <option value="">Selecione</option>
              <option>1º Ano</option>
              <option>2º Ano</option>
              <option>3º Ano</option>
            </select>
          </div>
        </div>

        <!-- Email e telefone -->
        <div class="form-row">
          <div class="field">
            <label>E-mail</label>
            <input type="email" id="email" placeholder="aluno@email.com" />
          </div>
          <div class="field">
            <label>Telefone / WhatsApp</label>
            <input type="tel" id="telefone" placeholder="(85) 99999-0000" />
          </div>
        </div>

        <!-- Endereço completo -->
        <div class="form-row full">
          <div class="field">
            <label>Endereço *</label>
            <input type="text" id="endereco" placeholder="Rua, número, bairro, cidade" required />
          </div>
        </div>

        <!-- Seção de responsáveis -->
        <h3 style="color:#158a2f;margin:24px 0 16px;font-size:18px;">Responsáveis</h3>

        <!-- 1º responsável: nome e telefone -->
        <div class="form-row">
          <div class="field">
            <label>Nome do 1º Responsável *</label>
            <input type="text" id="resp_nome" placeholder="Nome completo" required />
          </div>
          <div class="field">
            <label>Telefone do 1º Responsável *</label>
            <input type="tel" id="resp_telefone" placeholder="(85) 99999-0000" required />
          </div>
        </div>

        <!-- 2º responsável (opcional) -->
        <div class="form-row">
          <div class="field">
            <label>Nome do 2º Responsável (Opcional)</label>
            <input type="text" id="resp2_nome" placeholder="Nome completo" />
          </div>
          <div class="field">
            <label>Telefone do 2º Responsável (Opcional)</label>
            <input type="tel" id="resp2_telefone" placeholder="(85) 99999-0000" />
          </div>
        </div>

        <!-- CPFs dos responsáveis -->
        <div class="form-row">
          <div class="field">
            <label>CPF do 1º Responsável *</label>
            <input type="text" id="cpf_resp1" placeholder="123.456.789-10" required />
          </div>
          <div class="field">
            <label>CPF do 2º Responsável (Opcional)</label>
            <input type="text" id="cpf_resp2" placeholder="123.456.789-10" />
          </div>
        </div>
      </div><!-- fim form-row-full -->

      <!-- Observações gerais -->
      <div class="form-row full">
        <div class="field">
          <label>Observações</label>
          <textarea id="obs" placeholder="Informações adicionais relevantes sobre o aluno..."></textarea>
        </div>
      </div>

      <!-- Botões de ação do formulário -->
      <div style="display:flex;gap:12px;margin-top:8px;flex-wrap:wrap;">
        <button class="btn btn-primary" onclick="salvarAluno()">✔ Salvar Aluno</button>
        <button class="btn btn-ghost" onclick="limparForm()">✕ Limpar</button>
      </div>
    </div><!-- fim do card de cadastro -->

    <!-- Card com a tabela de alunos já cadastrados -->
    <div class="card" style="margin-top:22px;">
      <div class="card-title">Alunos cadastrados</div>
      <div class="table-wrap">
        <table style="width:100%;border-collapse:collapse;margin-top:10px;">
          <thead>
            <tr style="background-color:#f8fafb;text-align:left;">
              <th style="padding:10px;border-bottom:2px solid #dde2e8;">Nome</th>
              <th style="padding:10px;border-bottom:2px solid #dde2e8;">CPF</th>
              <th style="padding:10px;border-bottom:2px solid #dde2e8;">Curso</th>
              <th style="padding:10px;border-bottom:2px solid #dde2e8;">Série</th>
              <th style="padding:10px;border-bottom:2px solid #dde2e8;">Responsável</th>
              <th style="padding:10px;border-bottom:2px solid #dde2e8;">Status</th>
              <th style="padding:10px;border-bottom:2px solid #dde2e8;">Cadastro</th>
              <th style="padding:10px;border-bottom:2px solid #dde2e8;">Ações</th>
            </tr>
          </thead>
          <!-- Linhas injetadas pelo JavaScript -->
          <tbody id="listaAlunos">
            <tr>
              <td colspan="8" style="text-align:center;padding:20px;color:#aaa;">Nenhum aluno cadastrado.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

  </div><!-- fim do container -->

  <!-- Modal de edição de aluno (começa fechado) -->
  <div class="modal-overlay" id="modal-editar">
    <div class="modal">
      <div class="modal-header">
        <h3 style="color:#158a2f;">✏️ Editar Registro Completo</h3>
        <!-- Botão X pra fechar o modal -->
        <button class="modal-close" onclick="fecharModal('modal-editar')">✕</button>
      </div>

      <!-- Campos bloqueados — não podem ser alterados após o cadastro -->
      <div style="margin-bottom:15px;background:#f8f9fa;padding:12px;border-radius:6px;border-left:4px solid #6c757d;">
        <small style="color:#6c757d;font-weight:bold;display:block;margin-bottom:5px;">🔒 CAMPOS BLOQUEADOS (NÃO
          EDITÁVEIS):</small>
        <div class="form-row">
          <div class="field"><label>Matrícula</label><input type="text" id="e_matricula" readonly /></div>
          <div class="field"><label>CPF do Aluno</label><input type="text" id="e_cpf_aluno" readonly /></div>
        </div>
        <div class="form-row">
          <div class="field"><label>CPF do Responsável 1</label><input type="text" id="e_cpf_resp1" readonly /></div>
          <div class="field"><label>CPF do Responsável 2</label><input type="text" id="e_cpf_resp2" readonly /></div>
        </div>
      </div>

      <!-- Campos editáveis do modal -->
      <div class="form-row">
        <div class="field"><label>Nome Completo *</label><input type="text" id="e_nome" required /></div>
        <div class="field"><label>Data de Nascimento *</label><input type="date" id="e_nascimento" required /></div>
      </div>
      <div class="form-row">
        <div class="field">
          <label>Sexo *</label>
          <select id="e_sexo" required>
            <option>Masculino</option>
            <option>Feminino</option>
            <option>Outro</option>
          </select>
        </div>
        <div class="field">
          <label>Status do Sistema *</label>
          <select id="e_status" required>
            <option value="Ativo">Ativo</option>
            <option value="Inativo">Inativo</option>
            <option value="Transferido">Transferido</option>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="field">
          <label>Curso *</label>
          <select id="e_curso" required>
            <option>Administração</option>
            <option>Enfermagem</option>
            <option>Estética</option>
            <option>Finanças</option>
            <option>Informática</option>
          </select>
        </div>
        <div class="field">
          <label>Série *</label>
          <select id="e_turma" required>
            <option>1º Ano</option>
            <option>2º Ano</option>
            <option>3º Ano</option>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="field"><label>E-mail</label><input type="email" id="e_email" /></div>
        <div class="field"><label>Telefone Aluno</label><input type="tel" id="e_telefone" /></div>
      </div>
      <div class="form-row full">
        <div class="field"><label>Endereço *</label><input type="text" id="e_endereco" required /></div>
      </div>
      <div class="form-row">
        <div class="field"><label>Nome do 1º Responsável *</label><input type="text" id="e_resp_nome" required /></div>
        <div class="field"><label>Telefone do 1º Responsável *</label><input type="tel" id="e_resp_tel" required />
        </div>
      </div>
      <div class="form-row">
        <div class="field"><label>Nome do 2º Responsável</label><input type="text" id="e_resp2_nome" /></div>
        <div class="field"><label>Telefone do 2º Responsável</label><input type="tel" id="e_resp2_tel" /></div>
      </div>
      <div class="form-row full">
        <div class="field"><label>Observações</label><textarea id="e_obs"></textarea></div>
      </div>

      <!-- Botões de salvar/cancelar no modal -->
      <div style="display:flex;gap:10px;margin-top:16px;border-top:1px solid #eee;padding-top:15px;flex-wrap:wrap;">
        <button class="btn btn-primary" onclick="salvarEdicao()">✔ Salvar Alterações</button>
        <button class="btn btn-ghost" onclick="fecharModal('modal-editar')">Cancelar</button>
      </div>
    </div>
  </div><!-- fim do modal -->

  <!-- Navegação inferior (visível apenas em mobile) -->
  <nav class="bottom-nav">
    <a href="index.php" class="active">
      <span class="nav-icon">🏠</span>
      <span class="nav-text">Início</span>
    </a>
    <a href="registro.php">
      <span class="nav-icon">📝</span>
      <span class="nav-text">Registro</span>
    </a>
    <a href="cadastro.php" class="active">
      <span class="nav-icon">➕</span>
      <span class="nav-text">Cadastrar</span>
    </a>
    <a href="historico.php">
      <span class="nav-icon">⏳</span>
      <span class="nav-text">Histórico</span>
    </a>
  </nav>

  <script>
    // Guarda todos os alunos carregados pra usar no modal de edição
    let todosAlunos = [];
    // Armazena a matrícula do aluno que está sendo editado
    let editando = null;

    // Abre/fecha menu hambúrguer no mobile
    function toggleMenu() {
      document.getElementById('navLinks').classList.toggle('active');
    }

    // Ao carregar a página: aplica as máscaras nos inputs e carrega a tabela
    window.onload = function () {
      // Máscaras de CPF: só aceita o formato 000.000.000-00
      VMasker(document.getElementById("cpf_aluno")).maskPattern("999.999.999-99");
      VMasker(document.getElementById("cpf_resp1")).maskPattern("999.999.999-99");
      VMasker(document.getElementById("cpf_resp2")).maskPattern("999.999.999-99");
      // Máscaras de telefone: formato (00) 00000-0000
      VMasker(document.getElementById("telefone")).maskPattern("(99) 99999-9999");
      VMasker(document.getElementById("resp_telefone")).maskPattern("(99) 99999-9999");
      VMasker(document.getElementById("resp2_telefone")).maskPattern("(99) 99999-9999");
      // Máscaras dos campos do modal de edição
      VMasker(document.getElementById("e_telefone")).maskPattern("(99) 99999-9999");
      VMasker(document.getElementById("e_resp_tel")).maskPattern("(99) 99999-9999");
      VMasker(document.getElementById("e_resp2_tel")).maskPattern("(99) 99999-9999");

      renderTabela(); // carrega a lista de alunos
    };

    // Exibe o toast de feedback (some em 3 segundos)
    function showToast(msg, erro = false) {
      let t = document.getElementById('toast');
      if (!t) {
        t = document.createElement('div');
        t.id = 'toast';
        document.body.appendChild(t);
      }
      t.textContent = msg;
      t.className = erro ? 'error' : 'success';
      t.style.display = 'block';
      setTimeout(() => t.style.display = 'none', 3000);
    }

    // Valida e envia o formulário de cadastro de novo aluno
    async function salvarAluno() {
      const nome = document.getElementById('nome').value.trim();
      const cpf_aluno = document.getElementById('cpf_aluno').value.trim();
      const matricula = document.getElementById('matricula').value.trim();
      const nascimento = document.getElementById('nascimento').value;
      const sexo = document.getElementById('sexo').value;
      const curso = document.getElementById('curso').value;
      const turma = document.getElementById('turma').value;
      const endereco = document.getElementById('endereco').value.trim();
      const resp_nome = document.getElementById('resp_nome').value.trim();
      const resp_tel = document.getElementById('resp_telefone').value.trim();
      const cpf_resp1 = document.getElementById('cpf_resp1').value.trim();

      // Verifica se todos os obrigatórios foram preenchidos
      if (!nome || !cpf_aluno || !matricula || !nascimento || !sexo || !curso || !turma || !endereco || !resp_nome || !resp_tel || !cpf_resp1) {
        showToast('⚠ Por favor, preencha todos os campos obrigatórios (*).', true);
        return;
      }

      // Monta o payload pra mandar pra API
      const payload = {
        action: 'create',
        matricula, nome, turma, nascimento, sexo, curso,
        email: document.getElementById('email').value.trim(),
        telefone: document.getElementById('telefone').value.trim(),
        endereco, resp_nome, resp_tel,
        resp2_nome: document.getElementById('resp2_nome').value.trim(),
        resp2_tel: document.getElementById('resp2_telefone').value.trim(),
        cpf_aluno, cpf_resp1,
        cpf_resp2: document.getElementById('cpf_resp2').value.trim(),
        obs: document.getElementById('obs').value.trim(),
        status: 'Ativo',
        criado_em: new Date().toLocaleDateString('pt-BR')
      };

      try {
        const res = await fetch('api_cadastro.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
        const data = await res.json();

        if (data.success) {
          showToast('✅ Aluno cadastrado com sucesso!');
          limparForm();    // limpa o formulário
          renderTabela();  // atualiza a tabela
        } else {
          showToast(data.message || 'Erro ao cadastrar.', true);
        }
      } catch (e) {
        showToast('Erro de conexão.', true);
      }
    }

    // Limpa todos os campos do formulário de cadastro
    function limparForm() {
      ['nome', 'matricula', 'nascimento', 'sexo', 'curso', 'turma', 'email', 'telefone',
        'endereco', 'resp_nome', 'resp_telefone', 'resp2_nome', 'resp2_telefone',
        'cpf_aluno', 'cpf_resp1', 'cpf_resp2', 'obs'].forEach(id => {
          const el = document.getElementById(id);
          if (el) el.value = '';
        });
    }

    // Carrega e renderiza a tabela de alunos da API
    async function renderTabela() {
      const tbody = document.getElementById('listaAlunos');
      try {
        const res = await fetch('api_cadastro.php');
        const alunos = await res.json();
        todosAlunos = alunos; // salva pra usar no modal de edição

        if (!alunos.length) {
          tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;color:#aaa;padding:28px">Nenhum aluno cadastrado.</td></tr>';
          return;
        }

        // Gera as linhas da tabela pra cada aluno
        tbody.innerHTML = alunos.map(a => `
          <tr style="border-bottom:1px solid #dde2e8;">
            <td style="padding:10px;">
              <strong>${a.nome}</strong><br>
              <small style="color:#888;">Matrícula: ${a.matricula}</small>
            </td>
            <td style="padding:10px;">${a.cpf_aluno}</td>
            <td style="padding:10px;">${a.curso}</td>
            <td style="padding:10px;">${a.turma}</td>
            <td style="padding:10px;">${a.resp_nome}</td>
            <td style="padding:10px;">
              <!-- Badge de status: verde = ativo, cinza = inativo, laranja = outros -->
              <span class="badge ${a.status === 'Ativo' ? 'badge-green' : a.status === 'Inativo' ? 'badge-gray' : 'badge-orange'}">
                ${a.status}
              </span>
            </td>
            <td style="padding:10px;">${a.criado_em || '—'}</td>
            <td style="padding:10px;">
              <!-- Botão de editar abre o modal com os dados preenchidos -->
              <button class="btn btn-ghost btn-sm" onclick="abrirEditar('${a.matricula}')">✏️ Editar</button>
              <!-- Botão de excluir remove o aluno do banco -->
              <button class="btn btn-danger btn-sm" onclick="excluirAluno('${a.matricula}')">✕</button>
            </td>
          </tr>
        `).join('');
      } catch (e) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;color:red;padding:28px">Erro ao carregar alunos.</td></tr>';
      }
    }

    // Abre o modal de edição com os dados do aluno preenchidos
    function abrirEditar(mat) {
      const a = todosAlunos.find(x => x.matricula === mat);
      if (!a) return;
      editando = mat; // guarda qual matrícula está sendo editada

      // Preenche todos os campos do modal com os dados do aluno
      document.getElementById('e_matricula').value = a.matricula;
      document.getElementById('e_cpf_aluno').value = a.cpf_aluno;
      document.getElementById('e_cpf_resp1').value = a.cpf_resp1;
      document.getElementById('e_cpf_resp2').value = a.cpf_resp2 || '';
      document.getElementById('e_nome').value = a.nome;
      document.getElementById('e_nascimento').value = a.nascimento;
      document.getElementById('e_sexo').value = a.sexo;
      document.getElementById('e_status').value = a.status;
      document.getElementById('e_curso').value = a.curso;
      document.getElementById('e_turma').value = a.turma;
      document.getElementById('e_email').value = a.email || '';
      document.getElementById('e_telefone').value = a.telefone || '';
      document.getElementById('e_endereco').value = a.endereco;
      document.getElementById('e_resp_nome').value = a.resp_nome;
      document.getElementById('e_resp_tel').value = a.resp_tel;
      document.getElementById('e_resp2_nome').value = a.resp2_nome || '';
      document.getElementById('e_resp2_tel').value = a.resp2_tel || '';
      document.getElementById('e_obs').value = a.obs || '';

      // Abre o modal
      document.getElementById('modal-editar').classList.add('open');
    }

    // Salva as alterações feitas no modal de edição
    async function salvarEdicao() {
      const nomeEdicao = document.getElementById('e_nome').value.trim();
      const nascimentoEdicao = document.getElementById('e_nascimento').value;
      const sexoEdicao = document.getElementById('e_sexo').value;
      const statusEdicao = document.getElementById('e_status').value;
      const cursoEdicao = document.getElementById('e_curso').value;
      const turmaEdicao = document.getElementById('e_turma').value;
      const enderecoEdicao = document.getElementById('e_endereco').value.trim();
      const respNomeEdicao = document.getElementById('e_resp_nome').value.trim();
      const respTelEdicao = document.getElementById('e_resp_tel').value.trim();

      // Validação dos campos obrigatórios
      if (!nomeEdicao || !nascimentoEdicao || !sexoEdicao || !statusEdicao ||
        !cursoEdicao || !turmaEdicao || !enderecoEdicao || !respNomeEdicao || !respTelEdicao) {
        alert('Por favor, preencha todos os campos obrigatórios (*) na edição.');
        return;
      }

      // Monta o payload da edição
      const payload = {
        action: 'edit',
        matricula: editando,
        nome: nomeEdicao,
        nascimento: nascimentoEdicao,
        sexo: sexoEdicao,
        status: statusEdicao,
        curso: cursoEdicao,
        turma: turmaEdicao,
        email: document.getElementById('e_email').value.trim(),
        telefone: document.getElementById('e_telefone').value.trim(),
        endereco: enderecoEdicao,
        resp_nome: respNomeEdicao,
        resp_tel: respTelEdicao,
        resp2_nome: document.getElementById('e_resp2_nome').value.trim(),
        resp2_tel: document.getElementById('e_resp2_tel').value.trim(),
        obs: document.getElementById('e_obs').value.trim()
      };

      try {
        const res = await fetch('api_cadastro.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
        const data = await res.json();

        if (data.success) {
          fecharModal('modal-editar');
          renderTabela(); // atualiza a tabela com os novos dados
          showToast('✅ Aluno atualizado com sucesso!');
        } else {
          showToast('Erro ao atualizar', true);
        }
      } catch (e) {
        showToast('Erro de conexão.', true);
      }
    }

    // Exclui um aluno pelo número de matrícula (tem confirmação antes)
    async function excluirAluno(mat) {
      if (!confirm('Deseja realmente excluir este aluno do sistema?')) return;

      try {
        const res = await fetch('api_cadastro.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ action: 'delete', matricula: mat })
        });
        const data = await res.json();

        if (data.success) {
          renderTabela();
          showToast('Aluno removido.');
        } else {
          showToast('Erro ao remover', true);
        }
      } catch (e) {
        showToast('Erro de conexão.', true);
      }
    }

    // Fecha um modal pelo id do elemento
    function fecharModal(id) {
      document.getElementById(id).classList.remove('open');
    }

    // Fechar modal ao clicar fora da janela (no overlay escuro)
    document.querySelectorAll('.modal-overlay').forEach(m =>
      m.addEventListener('click', e => {
        if (e.target === m) m.classList.remove('open');
      })
    );
  </script>
</body>

</html>
