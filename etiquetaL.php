<?php
session_start();

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit();
}

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $serial = $_POST["serial"] ?? "";

    if ($serial === "") {
        $mensaje = "Por favor ingresa el número de serie.";
    } elseif (strlen($serial) !== 14) {
        $mensaje = "<span style='color:#d8000c;'>El número no existe.</span>";
    } else {
        // Aquí puedes poner la lógica para reimprimir la etiqueta L
        $mensaje = "Etiqueta para la caja con número de serie <b>" . htmlspecialchars($serial) . "</b> lista para reimprimir.";
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
            color: #fff;
            border: none;
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
    </style>
    <script>
        // Ejemplo para confirmar una acción 
        function confirmarBaja(event) {
            event.preventDefault();
            const serial = document.getElementById('serial').value;
            if (serial.trim() === "") {
                alert("Por favor ingresa el número de serie.");
                return false;
            }
            if (confirm("¿Estás seguro de que deseas dar de baja la caja con número de serie " + serial + "?")) {
                // Aquí va la lógica para dar de baja la caja
                event.target.form.submit();
            }
        }
    </script>
</head>
<body>
<div class="container">
    <h2>Reimprimir etiqueta L</h2>

    <?php if ($mensaje): ?>
        <div class="mensaje"><?php echo $mensaje; ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <label for="serial">Número de serie de la caja:</label>
        <input type="text" id="serial" name="serial" required maxlength="14" value="<?php echo isset($_POST['serial']) ? htmlspecialchars($_POST['serial']) : ''; ?>">
        <button type="submit">Reimprimir etiqueta</button>
        <a href="bienvenida.php" class="menu-log">Volver al menú</a>
    </form>
</div>
</body>
</html>
