<?php
session_start();

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Bienvenida</title>
<style>
    body {
        background: #bbdef5ff;
        margin: 0;
        font-family: Arial, sans-serif;
    }
    .top-bar {
        width: 100%;
        background: #2439a5ff;
        color: #fff;
        padding: 18px 0;
        text-align: center;
        font-size: 22px;
        font-weight: bold;
        letter-spacing: 1px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.07);
        margin-bottom: 40px;
    }
    .menu-btn {
        display: block;
        width: 250px;
        margin: 15px auto;
        background: #232368ff;
        color: #ffffff;
        border: none;
        border-radius: 4px;
        text-decoration: none;
        font-size: 16px;
        cursor: pointer;
        transition: background 0.2s;
        text-align: center;
        padding: 14px 0;
    }
    .menu-btn:hover {
        background: #099fe4ff;
        color: #fff;
    }
    .menu-log {
        display: block;
        width: 250px;
        margin: 15px auto;
        background: #5d5555ff;
        color: #fff;
        border: none;
        border-radius: 4px;
        text-decoration: none;
        font-size: 16px;
        cursor: pointer;
        transition: background 0.2s;
        text-align: center;
        padding: 14px 0;
    }
    .menu-log:hover {
        background: #c21f1fff;
        color: #ffffff;
    }
    .menu-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        margin-top: 0;
    }
    .menu-wrapper {
    max-width: 400px;
    margin: 0 auto;
    padding: 30px;
    background: #e4edf1ff;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }

</style>
</head>
<body>
<div class="top-bar">
    Bienvenid@ <?php echo htmlspecialchars($_SESSION["displayname"]); ?>
</div>
<div class="menu-wrapper">
    <div class="menu-container">
        <div class="menu-container">
    <a href="Bajacajas.php" class="menu-btn">Dar de baja cajas</a>
    <a href="etiquetaQR.php" class="menu-btn">Reimprimir etiqueta Datamatrix</a>
    <a href="etiquetaL.php" class="menu-btn">Reimprimir etiqueta L</a>
    <a href="etiquetaWC.php" class="menu-btn">Reimprimir etiqueta Welcome</a>
    <a href="historial.php" class="menu-btn">Bloqueo de seriales</a>
    <a href="logout.php" class="menu-log">Cerrar sesión</a>
</div>
    </div>
</div>
</body>
</html>

