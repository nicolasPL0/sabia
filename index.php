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
  <!-- viewport: garante que o celular não tente comprimir a página inteira numa tela pequena -->
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>SABIÁ — EEEP José de Barcelos</title>
  <link rel="stylesheet" href="style.css"/>
  <style>
    /* limita a largura do conteúdo nessa página */
    .container {
      max-width: 1000px;
    }

    /* caixa verde com as regras disciplinares da escola */
    .rule-box {
      background: #f0faf4;
      border-left: 4px solid #158a2f;
      padding: 16px;
      border-radius: 6px;
      font-size: 13px;
      line-height: 1.6;
      color: #333;
    }

    .rule-box strong {
      color: #158a2f;
    }

    /* ajuste mobile: sidebar vai pra baixo do conteúdo principal */
    @media (max-width: 900px) {
      .main-grid {
        grid-template-columns: 1fr;
      }
    }
    /* Header Principal */
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

  <!-- Cabeçalho verde do topo com o logo -->
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
  <!-- Barra de navegação com os links das páginas -->
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

  <!-- Toast: mensagem temporária de sucesso/erro (aparece no canto da tela) -->
  <div class="toast" id="toast"></div>

 

  <main class="container">
    <div class="main-grid">
      
      <div class="content-main">
        
        <div class="card">
          <div class="card-title">🦅 Sistema SABIÁ</div>
          <p style="font-size: 14.5px; color: #4a5568; line-height: 1.6; margin-bottom: 0;">
            Bem-vindo ao painel de gestão escolar da <strong>EEEP José de Barcelos</strong>. 
            O SABIÁ foi desenvolvido para centralizar e simplificar o dia a dia da coordenação, permitindo o controle rápido de pontualidade, uniformes e saídas da comunidade discente.
          </p>
        </div>

        

        <div class="card">
          <div class="card-title"> Recursos Disponíveis</div>
          <ul style="margin-left: 18px; font-size: 14px; color: #4a5568; line-height: 1.8;">
            <li><strong>Cadastro de Alunos:</strong> Insira novos estudantes no banco de dados para habilitar o rastreamento disciplinar.</li>
            <li><strong>Controle de Fluxo:</strong> Registre de forma prática se o aluno chegou atrasado, se está com o fardamento incorreto ou se precisou sair mais cedo da instituição.</li>
            <li><strong>Gestão de Penalidades:</strong> Histórico automatizado focado no acúmulo de advertências, ocorrências e notificações geradas.</li>
          </ul>
        </div>

      </div>

      <aside class="sidebar">
        <div class="card">
          <div class="card-title"> Diretrizes e Níveis</div>
          <div class="rule-box">
            <p>O sistema processa os horários de entrada da manhã de acordo com os critérios:</p>
            <ul style="margin-left: 18px; margin-top: 8px; margin-bottom: 12px;">
              <li><strong>Até 07:20:</strong> Entrada Normal</li>
              <li><strong>07:21 às 07:30:</strong> Tolerância</li>
              <li><strong>A partir de 07:31:</strong> Ocorrência Direta</li>
            </ul>
            
            <hr style="border:0; border-top:1px solid #d2e7d7; margin:12px 0;" />
            
            <p style="margin-bottom: 6px;"><strong>Regra de Conversão Acumulada:</strong></p>
            <p>A cada <strong>3 Advertências</strong> → Gera-se automaticamente <strong>1 Ocorrência</strong>.</p>
            <p style="margin-top: 4px;">A cada <strong>3 Ocorrências</strong> → Gera-se automaticamente <strong>1 Notificação</strong> para contato com o responsável.</p>
            
            <div class="alerta-box">
              ⚠️ <strong>Atenção:</strong> O acúmulo de <strong>3 Notificações</strong> gera automaticamente o status de <strong>Suspensão</strong>.
            </div>
            
            <hr style="border:0; border-top:1px solid #d2e7d7; margin:12px 0;" />
            
            <p><strong>Fardamento Inadequado:</strong> Marcar esta opção gera uma Ocorrência Direta imediata, independente do horário de chegada.</p>
          </div>
        </div>
      </aside>

    </div></main>

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
    <a href="historico.php">
      <span class="nav-icon">⏳</span>
      <span class="nav-text">Histórico</span>
    </a>
  </nav>

  <script>
    // Abre/fecha o menu hambúrguer no mobile
    function toggleMenu() {
      document.getElementById('navLinks').classList.toggle('active');
    }
  </script>

</body>

</html>
