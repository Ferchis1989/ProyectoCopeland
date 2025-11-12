<?php
session_start();
require_once 'authenticate.php'; // Incluye la función AD

$error = "";

// Procesar formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = $_POST["usuario"] ?? "";
    $contrasena = $_POST["contrasena"] ?? "";

    if (authenticate($usuario, $contrasena)) {
        // Usuario autenticado, redirigir
        $_SESSION["usuario"] = $usuario;
        header("Location: bienvenida.php");
        exit();
    } else {
        $error = "Usuario o contraseña incorrectos, o sin permisos.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Login</title>
<style>
    body { background: #c7e5f3ff; font-family: Arial, sans-serif; }
    #preloader { position: fixed; top:0; left:0; width:100vw; height:100vh; background:#fff; display:flex; flex-direction:column; align-items:center; justify-content:center; z-index:9999; transition:opacity 0.3s; }
    #preloader img { width:200px; margin-bottom:24px; }
    .loader { border:8px solid #c7e5f3ff; border-top:8px solid #2e1ae0ff; border-radius:50%; width:60px; height:60px; animation:spin 1s linear infinite; }
    @keyframes spin { 0%{transform:rotate(0deg);} 100%{transform:rotate(360deg);} }
    .login-container { background:#fff; padding:30px 40px; margin:80px auto; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.1); max-width:350px; text-align:center; }
    h2 { margin-bottom:24px; color:#333; }
    label { display:block; margin-bottom:6px; color:#000; text-align:left; font-weight:bold; }
    input[type="text"], input[type="password"] { width:100%; padding:8px; margin-bottom:18px; border:1px solid #ccc; border-radius:4px; box-sizing:border-box; }
    button { background:#2e1ae0ff; color:#fff; border:none; padding:10px 20px; border-radius:4px; cursor:pointer; font-size:16px; font-weight:bold; }
    button:hover { background:#3d87d6ff; }
    .error-message { color:#d8000c; background:#ffd2d2; padding:8px; border-radius:4px; margin-bottom:16px; }
</style>
</head>
<body>

<div id="preloader">
    <img src="Copeland Logo_16_9_PNG.webp" alt="Logo de la empresa">
    <div class="loader"></div>
</div>

<div class="login-container">
    <img src="Copeland Logo_16_9_PNG.webp" alt="Logo de la empresa" style="width:200px; margin-bottom:20px;">
    <h2>Iniciar sesión</h2>

    <?php if ($error): ?>
    <div class="error-message"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <label><b>Usuario:</b></label>
        <input type="text" name="usuario" required>

        <label><b>Contraseña:</b></label>
        <input type="password" name="contrasena" required>

        <button type="submit"><b>Entrar</b></button>
    </form>
</div>

<script>
// Ocultar preloader al cargar
window.addEventListener('load', function(){
    document.getElementById('preloader').style.opacity='0';
    setTimeout(function(){
        document.getElementById('preloader').style.display='none';
    },1000);
});
</script>
</body>
</html>
