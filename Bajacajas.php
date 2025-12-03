<?php
session_start();

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit();
}
require_once 'conexion.php';


$mensaje = "";
$caja_info = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $serial = $_POST["serial"] ?? "";
    $accion = $_POST["accion"] ?? "";

    if ($serial === "") {
        $mensaje = "Por favor ingresa el número de serie.";
    } else {
        if ($accion === "buscar") {
            $sql = "SELECT B_Id, Mod_Id, ProcessCount, B_Status FROM dbo.Box WHERE B_Id = ?";
            $params = array($serial);
            $stmt = sqlsrv_query($conn, $sql, $params);

            if ($stmt === false) {
                $mensaje = "Error al buscar: " . print_r(sqlsrv_errors(), true);
            } elseif ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $caja_info = $row;
            } else {
                $mensaje = "<span style='color:#d8000c;'>No se encontró el número de serie <b>" . htmlspecialchars($serial) . "</b>.</span>";
            }

         } elseif ($accion === "baja") {
            
sqlsrv_begin_transaction($conn);

// Primero eliminar box_det
$sql_detalle = "DELETE FROM dbo.Box_det WHERE B_Id = ?";
$stmt_detalle = sqlsrv_query($conn, $sql_detalle, array($serial));

if ($stmt_detalle === false) {
    sqlsrv_rollback($conn);
    $errors = print_r(sqlsrv_errors(), true);
    $mensaje = "Error al dar de baja (Box_det): <pre>$errors</pre>";
} else {
    // Luego eliminar en box
    $sql_box = "DELETE FROM dbo.Box WHERE B_Id = ?";
    $stmt_box = sqlsrv_query($conn, $sql_box, array($serial));

    if ($stmt_box === false) {
        sqlsrv_rollback($conn);
        $errors = print_r(sqlsrv_errors(), true);
        $mensaje = "Error al eliminar la caja (Box): <pre>$errors</pre>";
    } else {
        sqlsrv_commit($conn);
        $mensaje = "Caja con número de serie <b>" . htmlspecialchars($serial) . "</b> Fue dada de baja correctamente.";
    }
}

        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dar de baja cajas</title>
    <style>
        body {
            background: #aad3f0;
            font-family: Arial, sans-serif;
        }
        .container {
            background: #fff;
            padding: 40px 60px;
            margin: 80px auto;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            max-width: 600px;
            text-align: center;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #000;
            font-weight: bold;
        }
        input[type="text"] {
            width: 100%;
            padding: 8px;
            margin-bottom: 18px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            background: #232368ff;
            border: none;
            color: #ffffff;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
        }
        button:hover {
            background: #3d87d6ff;
        }
        .mensaje {
            margin-bottom: 16px;
            color: #165884;
        }
        .menu-log {
            display: inline-block;
            width: auto;
            min-width: 120px;
            margin: 15px auto 0 auto;
            background: #a30000;
            color: #fff;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.2s;
            text-align: center;
            padding: 10px 20px;
            font-weight: bold;
        }
        .menu-log:hover {
            background: #d8000c;
            color: #fff;
        }
        .info-caja {
            background: #eaf6ff;
            border-radius: 6px;
            padding: 12px;
            margin-bottom: 18px;
            text-align: left;
        }

        /* Logo fijo en la esquina superior derecha */
        .corner-logo {
            position: fixed;
            top: 12px;
            right: 12px;
            width: 220px;
            max-width: 25vw;
            opacity: 0.95;
            z-index: 9999;
            pointer-events: none; 
        }
        @media (max-width: 600px) {
            .corner-logo { width: 90px; top: 8px; right: 8px; }
        }
    </style>
</head>
<body>
        <img src="Copeland%20Logo_16_9_PNG.webp" alt="Copeland logo" class="corner-logo" loading="lazy">
<div class="container">
    <h2>Dar de baja cajas</h2>
    <?php if ($mensaje): ?>
        <div class="mensaje"><?php echo $mensaje; ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <label for="serial">Número de serie de la caja:</label>
        <input type="text" id="serial" name="serial" required
               value="<?php echo isset($_POST['serial']) ? htmlspecialchars($_POST['serial']) : ''; ?>">
        <button type="submit" name="accion" value="buscar">Buscar caja</button>
    </form>

    <?php if ($caja_info): ?>
        <div class="info-caja">
            <b>Box ID:</b> <?php echo htmlspecialchars($caja_info["B_Id"]); ?><br>
            <b>Modelo:</b> <?php echo htmlspecialchars($caja_info["Mod_Id"]); ?><br>
            <b>Proceso:</b> <?php echo htmlspecialchars($caja_info["ProcessCount"]); ?><br>
            <b>Estado:</b> <?php echo htmlspecialchars($caja_info["B_Status"]); ?><br>
        </div>

        <form method="POST" action="" onsubmit="return confirm('¿Estás seguro de dar de baja esta caja? Esta acción eliminará el registro permanentemente.');">
            <input type="hidden" name="serial" value="<?php echo htmlspecialchars($caja_info["B_Id"]); ?>">
            <button type="submit" name="accion" value="baja">Dar de baja</button>
        </form>
    <?php endif; ?>

    <a href="bienvenida.php" class="menu-log">Volver al menú</a>
</div>
</body>
</html>

