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
    body {
        background: #bbdef5ff;
        font-family: Arial, sans-serif;
    }
    .login-container {
        background: #fff;
        padding: 30px 40px;
        margin: 80px auto;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        max-width: 350px;
        text-align: center;
    }
    .login-logo {
        width: 180px;        /* ajusta tamaño aquí */
        height: auto;
        display: block;
        margin: 0 auto 16px auto;
    }
    h2 {
        margin-bottom: 24px;
        color: #333;
    }
   
    label {
        display: block;
        margin-bottom: 6px;
        color: #434242ff;
        text-align: left;
        font-weight: 700; 
    }
    input[type="text"], input[type="password"] {
        width: 100%;
        padding: 8px;
        margin-bottom: 18px;
        border: 1px solid #ccc;
        border-radius: 4px;
        box-sizing: border-box;
    }
    button {
        background: #19199eff;
        color: #fff;
        border: none;
        padding: 10px 20px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 16px;
    }
    button:hover {
        background: #0056b3;
    }
    .error-message {
        color: #d8000c;
        background: #ffd2d2;
        padding: 8px;
        border-radius: 4px;
        margin-bottom: 16px;
        font-weight: 700; /* opcional: error también en negrita */
    }
</style>
</head>
<body>

<div class="login-container">
    <img src="Copeland%20Logo_16_9_PNG.webp" alt="Copeland logo" class="login-logo" loading="lazy">
    <h2>Iniciar sesión</h2>

    <?php if ($error): ?>
    <p class="error-message"><strong><?php echo $error; ?></strong></p>
    <?php endif; ?>

    <form method="POST" action="">
        <label><b>Usuario:</b></label>
        <input type="text" name="usuario" required>

        <label><b>Contraseña:</b></label>
        <input type="password" name="contrasena" required>

        <button type="submit">Entrar</button>
    </form>
</div>

</body>
</html>
