<?php
session_start();

// Verifica se o usuário tem permissão (Deve ser Admin ou PDT)
$nivel = $_SESSION['usuario_nivel'] ?? '';
$isPdt = $_SESSION['usuario_pdt'] ?? 0;

if (!isset($_SESSION['usuario_id']) || ($nivel !== 'admin' && $isPdt != 1)) {
    header('Location: index.php');
    exit;
}
?>