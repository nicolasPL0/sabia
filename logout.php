<?php
session_start();

// Limpa todas as variáveis da sessão
$_SESSION = array();

// Destrói o cookie da sessão se existir
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destrói a sessão
session_destroy();

// Redireciona para a tela de login
header("Location: login.php");
exit;
?>