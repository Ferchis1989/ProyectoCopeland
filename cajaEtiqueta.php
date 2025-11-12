<?php
session_start();

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit();
}

$mensaje = "";
$caja_info = null;
$paquetes = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $serial = strtoupper(trim($_POST["serial"] ?? ""));
    $accion = $_POST["accion"] ?? "";

    if ($serial !== "") {
        $datos_mock = [
            "CX-789" => [
                "modelo" => "Modelo Zeta 2025",
                "paquetes" => ["PAQ-001", "PAQ-002", "PAQ-003"]
            ],
            "CX-123" => [
                "modelo" => "Modelo Turbo X1",
                "paquetes" => ["PX-001", "PX-002"]
            ]
        ];

        $encontrado = false;

        // Primero busca como número de paquete
        foreach ($datos_mock as $serialCaja => $info) {
            if (in_array($serial, $info["paquetes"])) {
                $caja_info = [
                    "serial" => $serialCaja,
                    "modelo" => $info["modelo"]
                ];
                $paquetes = $info["paquetes"];
                $encontrado = true;
                break;
            }
        }

        // Si no lo encontró como paquete, intenta como caja
        if (!$encontrado && array_key_exists($serial, $datos_mock)) {
            $caja_info = [
                "serial" => $serial,
                "modelo" => $datos_mock[$serial]["modelo"]
            ];
            $paquetes = $datos_mock[$serial]["paquetes"];
            $encontrado = true;
        }

        if (!$encontrado) {
            $mensaje = "<span style='color:#d8000c;'>No se encontró ningún paquete o caja con número de serie <b>" . htmlspecialchars($serial) . "</b>.</span>";
        }

        if ($accion === "reimprimir" && $caja_info) {
            $mensaje = "<b>Etiqueta reimpresa correctamente para la caja " . htmlspecialchars($caja_info["serial"]) . ".</b>";
        }
    } else {
        $mensaje = "Por favor ingresa el número de serie del paquete o de la caja.";
    }
}
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reimprimir etiqueta</title>
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
            font-size: 15px;
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
            background: #a30000;
            color: #fff;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            font-size: 15px;
            cursor: pointer;
            text-align: center;
            padding: 10px 20px;
            font-weight: bold;
        }

        .menu-log:hover {
            background: #d8000c;
        }

        .info-caja, .info-paquetes {
            background: #eaf6ff;
            border-radius: 6px;
            padding: 12px;
            margin-bottom: 18px;
            text-align: left;
        }

        .botones-final {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Reimprimir etiqueta de paquete</h2>

    <?php if ($mensaje): ?>
        <div class="mensaje"><?php echo $mensaje; ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <label for="serial">Número de serie del paquete:</label>
        <input type="text" id="serial" name="serial" required value="<?php echo isset($_POST['serial']) ? htmlspecialchars($_POST['serial']) : ''; ?>">
        <input type="hidden" name="accion" value="buscar">
        <button type="submit">Buscar paquete</button>
    </form>

    <?php if (!$caja_info): ?>
        <form action="bienvenida.php" method="get" style="margin-top: 10px;">
            <button type="submit" class="menu-log">Volver al menú</button>
        </form>
    <?php endif; ?>

    <?php if ($caja_info): ?>
        <div class="info-caja">
            <b>Caja:</b> <?php echo htmlspecialchars($caja_info["serial"]); ?><br>
            <b>Modelo:</b> <?php echo htmlspecialchars($caja_info["modelo"]); ?>
        </div>

        <div class="info-paquetes">
            <b>Paquetes en la caja:</b><br>
            <ul>
                <?php foreach ($paquetes as $paq): ?>
                    <li><?php echo htmlspecialchars($paq); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="botones-final">
            <form method="POST" action="">
                <input type="hidden" name="serial" value="<?php echo htmlspecialchars($_POST['serial']); ?>">
                <input type="hidden" name="accion" value="reimprimir">
                <button type="submit">Reimprimir etiqueta</button>
            </form>
            <form action="bienvenida.php" method="get">
                <button type="submit" class="menu-log">Volver al menú</button>
            </form>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
