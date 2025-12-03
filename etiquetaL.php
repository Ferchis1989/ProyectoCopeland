<?php
session_start();

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit();
}

require_once 'conexion.php';
$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $serial = trim($_POST["serial"] ?? "");

    if ($serial === "") {
        $mensaje = "Por favor ingresa el número de serie.";
    } elseif (strlen($serial) !== 14) {
        $mensaje = "<span style='color:#d8000c;'>El número de serie debe tener exactamente 14 caracteres.</span>";
    } else {
       
        if (isset($conn)) {
            $sql = "UPDATE [ADCS_CHI].[dbo].[mac_ids] SET mac_status = 'R' WHERE uut_id = ? AND mac_status = 'L'";
            $params = array($serial);
            $stmt = sqlsrv_query($conn, $sql, $params);
            if ($stmt === false) {
                $errors = print_r(sqlsrv_errors(), true);
                $mensaje = "<span style='color:#d8000c;'>Error al marcar para reimpresión: <pre>$errors</pre></span>";
            } else {
                $mensaje = "Etiqueta para la caja con número de serie <b>" . htmlspecialchars($serial) . "</b> lista para reimprimir.";
            }
        } else {
            
            $mensaje = "Etiqueta para la caja con número de serie <b>" . htmlspecialchars($serial) . "</b> lista para reimprimir.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reimprimir etiqueta L</title>
    <style>
        body {
            background: #aad3f0;
            font-family: Arial, sans-serif;
        }
        /* Contenedor más grande */
        .container {
            background: #fff;
            padding: 48px 80px;
            margin: 60px auto;
            border-radius: 8px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.12);
            max-width: 760px;
            text-align: center;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #000;
            font-weight: bold;
            text-align: left;
        }
        /* Input más grande y cómodo */
        input[type="text"] {
            width: 100%;
            padding: 14px;
            margin-bottom: 18px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 16px;
            box-sizing: border-box;
        }
        button {
            background: #232368ff;
            color: #fff;
            border: none;
            padding: 12px 26px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            margin-right: 12px;
        }
        button:hover {
            background: #3d87d6ff;
        }
        .mensaje {
            margin-bottom: 18px;
            color: #165884;
        }
        .menu-log {
            display: inline-block;
            background: #a30000;
            color: #fff;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            padding: 10px 16px;
        }
        .menu-log:hover { background:#d8000c; color:#fff; }

        .corner-logo {
            position: fixed;
            top: 12px;
            right: 12px;
            width: 220px;      /* tamaño principal (ajusta a tu preferencia) */
            max-width: 40vw;   /* escala en pantallas muy grandes */
            height: auto;
            opacity: 0.95;
            z-index: 999;
            pointer-events: none;
        }

        /* puntos de quiebre para adaptar tamaño en pantallas más pequeñas */
        @media (max-width: 1200px) {
            .corner-logo { width: 280px; top: 10px; right: 10px; }
        }
        @media (max-width: 800px) {
            .corner-logo { width: 180px; top: 8px; right: 8px; }
        }
        @media (max-width: 400px) {
            .corner-logo { width: 120px; top: 6px; right: 6px; }
        }
    </style>
</head>
<body>
    <!-- Logo visible en todas las páginas si incluyes este fragmento -->
    <img src="Copeland Logo_16_9_PNG.webp" alt="Logo" class="corner-logo">

<div class="container">
    <h2>Reimprimir etiqueta L</h2>

    <?php if ($mensaje): ?>
        <div class="mensaje"><?php echo $mensaje; ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <label for="serial">Número de serie:</label>
        <input type="text" id="serial" name="serial" required maxlength="14" value="<?php echo isset($_POST['serial']) ? htmlspecialchars($_POST['serial']) : ''; ?>">
        <div>
            <button type="submit">Reimprimir etiqueta</button>
            <a href="bienvenida.php" class="menu-log">Volver al menú</a>
        </div>
    </form>
</div>
</body>
</html>
