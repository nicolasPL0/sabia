<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$paginaAtual = basename($_SERVER['PHP_SELF']);
$isAdmin = isset($_SESSION['usuario_nivel']) && $_SESSION['usuario_nivel'] === 'admin';
$isPdt   = isset($_SESSION['usuario_pdt']) && $_SESSION['usuario_pdt'] == 1;

// Pode acessar áreas administrativas e restritas se for Admin ou PDT
$podeAcessarRestritos = $isAdmin || $isPdt;
?>

<nav class="nav-row">
  <div class="container nav-inner">
    <div class="nav-links" id="navLinks">
      <a href="index.php" class="<?= $paginaAtual === 'index.php' ? 'active' : '' ?>">INÍCIO</a>
      <a href="registro.php" class="<?= $paginaAtual === 'registro.php' ? 'active' : '' ?>">REGISTRO</a>
      <a href="cadastro.php" class="<?= $paginaAtual === 'cadastro.php' ? 'active' : '' ?>">CADASTRAR ALUNOS</a>
      <a href="historico.php" class="<?= $paginaAtual === 'historico.php' ? 'active' : '' ?>">HISTÓRICO</a>

      <!-- Abas restritas a Administradores e PDTs -->
      <?php if ($podeAcessarRestritos): ?>
        <a href="fardamento.php" class="<?= $paginaAtual === 'fardamento.php' ? 'active' : '' ?>">FARDAMENTO</a>
        <a href="saida_antecipada.php" class="<?= $paginaAtual === 'saida_antecipada.php' ? 'active' : '' ?>">SAÍDA ANTECIPADA</a>
        <a href="atrasos.php" class="<?= $paginaAtual === 'atrasos.php' ? 'active' : '' ?>">TOLERÂNCIA / ATRASO</a>
      <?php endif; ?>

      <!-- Aba exclusiva do Administrador Master -->
      <?php if ($isAdmin): ?>
        <a href="professores.php" class="<?= $paginaAtual === 'professores.php' ? 'active' : '' ?>">PROFESSORES / PDT</a>
      <?php endif; ?>
    </div>
  </div>
</nav>