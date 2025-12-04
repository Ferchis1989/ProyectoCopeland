<?php
session_start();
if (!isset($_SESSION["usuario"])) exit();
require_once 'conexion.php';

// FUNCIONES AUXILIARES
function responder_json($data){
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}


// API AJAX


// Cargar operaciones según modelo
if(isset($_POST['modelo']) && !isset($_POST['op_id']) && !isset($_POST['accion'])){
    $modelo = $_POST['modelo'];
    $sql = "SELECT * 
            FROM dbo.operation 
            WHERE fam_id IN (SELECT fam_id FROM dbo.model WHERE mod_id=?) 
            ORDER BY op_id";
    $params = [$modelo];
    $stmt = sqlsrv_query($conn, $sql, $params);
    $result = [];
    if($stmt){
        while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
            $result[] = $row['op_id'];
        }
    }
    responder_json($result);
}

// Cargar números de serie según modelo y operación
if(isset($_POST['modelo']) && isset($_POST['op_id']) && !isset($_POST['accion'])){
    $modelo = $_POST['modelo'];
    $op_id = $_POST['op_id'];
    $op_id_end = substr($op_id, 0, 2) . '999';

    // fechas opcionales en formato YYYY-MM-DD
    $start_date = trim($_POST['start_date'] ?? '');
    $end_date   = trim($_POST['end_date'] ?? '');

    $sql = "
    WITH RankedUut AS (
        SELECT 
            u.uut_id,
            u.mod_id,
            d.tst_id,
            ROW_NUMBER() OVER (PARTITION BY u.uut_id ORDER BY d.uut_when DESC) AS rn
        FROM dbo.uut u
        JOIN dbo.uut_det d ON u.uut_id = d.uut_id
        WHERE (d.op_id > ? AND d.op_id < ?)
          AND u.uut_status <> 'EN' 
          AND u.mod_id= ?
    )
    SELECT uut_id, mod_id, tst_id
    FROM RankedUut
    WHERE rn = 1";
    
    $params = [$op_id, $op_id_end, $modelo];

    // Si ambas fechas vienen, validarlas y agregar filtro por rango (incluye todo el día end_date)
    if ($start_date !== '' && $end_date !== '') {
        $sd = DateTime::createFromFormat('Y-m-d', $start_date);
        $ed = DateTime::createFromFormat('Y-m-d', $end_date);
        if ($sd && $ed) {
            // insertar condición que filtra por d.uut_when en la subconsulta
            $sql = str_replace(
                "WHERE (d.op_id > ? AND d.op_id < ?)","WHERE (d.op_id > ? AND d.op_id < ?) AND d.uut_when >= ? AND d.uut_when < DATEADD(day,1,?)",
                $sql
            );
            $params[] = $sd->format('Y-m-d');
            $params[] = $ed->format('Y-m-d');
        }
    }

    $stmt = sqlsrv_query($conn, $sql, $params);
    $uuids = [];
    if($stmt){
        while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
            $uuids[] = $row['uut_id'];
        }
    }
    responder_json($uuids);
}

// Bloquear 
if(isset($_POST['accion']) && $_POST['accion'] === "bloquear"){
    $modelo = $_POST['modelo'];  
    $op_id = $_POST['op_id'];     

    sqlsrv_configure('WarningsReturnAsErrors', 0);

    $sql = "EXEC dbo.changeFirmwareProcess ?, ?";
    $params = [$modelo, $op_id];
    $stmt = sqlsrv_query($conn, $sql, $params);

    if($stmt === false){
        $errors = sqlsrv_errors();
        $msg = "Error al ejecutar el procedimiento.";
        if($errors){
            foreach($errors as $e){
                $msg .= " " . $e['message'];
            }
        }
        responder_json(['error'=>$msg]);
    }

    $output = '';
    do {
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $output .= implode(' | ', $row) . "<br>";
        }
    } while (sqlsrv_next_result($stmt));

    $msg = "Procedimiento ejecutado correctamente para el modelo $modelo en la operación $op_id.<br>$output";
    responder_json(['success'=>$msg]);
}

// Cargar cajas recientes por modelo
if(isset($_POST['accion']) && $_POST['accion'] === "cajas"){
    $modelo = $_POST['modelo'];

    $sql = "
SELECT 
    b.B_Id AS Box_ID,
    b.Mod_Id AS Model_ID,
    b.InsertedAt,
    CASE 
        WHEN b.[B_Status] = 2 THEN 'Completa'
        ELSE 'Parcial'
    END AS Estado
FROM dbo.Box b
WHERE b.Mod_Id = ?
  AND d.InsertedAt> DATEADD(HOUR,-4,DATEADD(DAY, DATEDIFF(DAY, 0, GETDATE()), 0))
ORDER BY b.InsertedAt DESC
";


//AND CAST(b.InsertedAt AS DATE) = '2024-02-09'

    $params = [$modelo];
    $stmt = sqlsrv_query($conn, $sql, $params);
    $cajas = [];
if($stmt){
    while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
        $cajas[] = [
            'Box_ID'     => $row['Box_ID'],
            'Model_ID'   => $row['Model_ID'],
            'InsertedAt' => $row['InsertedAt'] ? $row['InsertedAt']->format('Y-m-d H:i:s') : null,
            'Estado'     => $row['Estado']
        ];
    }
}

    responder_json($cajas);
}

$sql = "SELECT DISTINCT mod_id FROM dbo.model ORDER BY mod_id";
$stmt = sqlsrv_query($conn, $sql);
$modelos = [];
while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
    $modelos[] = $row['mod_id'];
}
// EXPORTAR SERIALS
if(isset($_POST['accion']) && $_POST['accion'] === 'exportar_seriales'){
    $modelo = $_POST['modelo'] ?? '';
    $op_id  = $_POST['op_id'] ?? '';
    $start_date = trim($_POST['start_date'] ?? '');
    $end_date   = trim($_POST['end_date'] ?? '');

    $op_id_end = substr($op_id, 0, 2).'999';
    $sql = "
    WITH RankedUut AS (
        SELECT 
            u.uut_id,
            ROW_NUMBER() OVER (PARTITION BY u.uut_id ORDER BY d.uut_when DESC) AS rn
        FROM dbo.uut u
        JOIN dbo.uut_det d ON u.uut_id = d.uut_id
        WHERE (d.op_id > ? AND d.op_id < ?)
          AND u.mod_id = ?
    )
    SELECT uut_id
    FROM RankedUut
    WHERE rn = 1";
    $params = [$op_id, $op_id_end, $modelo];

    if ($start_date !== '' && $end_date !== '') {
        $sd = DateTime::createFromFormat('Y-m-d', $start_date);
        $ed = DateTime::createFromFormat('Y-m-d', $end_date);
        if ($sd && $ed) {
            $sql = str_replace(
                "WHERE (d.op_id > ? AND d.op_id < ?)","WHERE (d.op_id > ? AND d.op_id < ?) AND d.uut_when >= ? AND d.uut_when < DATEADD(day,1,?)",
                $sql
            );
            $params[] = $sd->format('Y-m-d');
            $params[] = $ed->format('Y-m-d');
        }
    }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="seriales_'.$modelo.'_'.$op_id.'.csv"');

    $output = fopen('php://output','w');
   
    fputcsv($output, ['#','Numero de Serie']);

    $i = 1;
    if($stmt){
        while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
            // Forzar como texto 
            fputcsv($output, [$i++, "'".$row['uut_id']]);
        }
    }
    fclose($output);
    exit;
}



// EXPORTAR CAJAS
if(isset($_POST['accion']) && $_POST['accion'] === 'exportar_cajas'){
    $modelo = $_POST['modelo'] ?? '';

    $sql = "
    SELECT 
        b.B_Id AS Box_ID,
        b.Mod_Id AS Model_ID,
        b.InsertedAt,
        CASE 
            WHEN b.[B_Status] = 2 THEN 'Completa'
            ELSE 'Parcial'
        END AS Estado
    FROM dbo.Box b
    WHERE b.Mod_Id = ?
    ORDER BY b.InsertedAt DESC";
    $params = [$modelo];
    $stmt = sqlsrv_query($conn, $sql, $params);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="cajas_'.$modelo.'.csv"');

    $output = fopen('php://output','w');
    fputcsv($output,['#','Box ID','Modelo','Fecha Insertada','Estado']);
    $i=1;
    if($stmt){
    while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
        fputcsv($output, [
            $i++,
            "'".$row['Box_ID'],
            "'".$row['Model_ID'],
            "'".($row['InsertedAt'] ? $row['InsertedAt']->format('Y-m-d H:i:s') : ''),
            "'".$row['Estado']
        ]);
    }
}

    fclose($output);
    exit;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Historial</title>
<style>
body { background: #aad3f0; font-family: Arial, sans-serif; }
.container { background: #fff; padding: 40px 60px; margin: 80px auto; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); max-width: 600px; text-align: center; }
label { display: block; margin-bottom: 8px; color: #000; font-weight: bold; }
input[type="text"], select { width: 100%; padding: 8px; margin-bottom: 18px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
button { background: #232368ff; border: none; color: #ffffff; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-size: 16px; font-weight: bold; margin: 5px; }
button:hover { background: #3d87d6ff; }
.menu-log { display: inline-block; width: auto; min-width: 120px; margin: 15px auto 0 auto; background: #a30000; color: #fff; border: none; border-radius: 4px; text-decoration: none; font-size: 16px; cursor: pointer; transition: background 0.2s; text-align: center; padding: 10px 20px; font-weight: bold; }
.menu-log:hover { background: #d8000c; color: #fff; }
.info-caja { background: #eaf6ff; border-radius: 6px; padding: 12px; margin-bottom: 18px; text-align: left; }
.select-wrapper { position: relative; }
.select-wrapper input { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; margin-bottom: 10px; box-sizing: border-box; }
.select-wrapper ul { list-style: none; padding: 0; margin: 0; max-height: 150px; overflow-y: auto; border: 1px solid #ccc; border-radius: 4px; display: none; position: absolute; width: 100%; background: #fff; z-index: 10; }
.select-wrapper ul li { padding: 6px 8px; cursor: pointer; }
.select-wrapper ul li:hover { background: #3d87d6ff; color: #fff; }
table { width: 100%; border-collapse: collapse; margin-top: 20px; }
table, th, td { border: 1px solid #ccc; }
th, td { padding: 8px; text-align: center; }

/* Modal estilos */
.modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); }
.modal-content { background-color: #fefefe; margin: 15% auto; padding: 20px; border: 1px solid #888; width: 400px; border-radius: 8px; text-align: center; }
.modal button { background: #232368ff; color: #fff; padding: 10px 20px; margin-top: 15px; border-radius: 4px; border: none; cursor: pointer; font-weight: bold; }
.modal button:hover { background: #3d87d6ff; }


.corner-logo {
    position: fixed;
    top: 12px;
    right: 12px;
    width: 220px;
    max-width: 22vw;
    opacity: 0.95;
    z-index: 9999;
    pointer-events: none; 
}
@media (max-width: 600px) {
    .corner-logo { width: 90px; top: 8px; right: 8px; }
}

/* nueva regla para alinear filtros en una fila */
.filters {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    align-items: center; /* alinea verticalmente inputs y botones */
    justify-content: flex-start;
    margin-bottom: 18px;
}

/* cada control (label + input/select) */
.filter-field {
    display: flex;
    flex-direction: column;
    min-width: 160px;
}

/* campos pequeños (fechas) */
.filter-field.small { min-width: 140px; max-width: 180px; }

/* asegurar que botones tengan altura consistente */
.filters .actions {
    display: flex;
    gap: 8px;
    align-items: center;
}

/* responsive */
@media (max-width: 800px) {
    .filters { gap: 8px; }
    .filter-field { min-width: 140px; max-width: 48%; }
    .filter-field.small { max-width: 40%; }
    .filters .actions { width: 100%; flex-wrap: wrap; }
}
</style>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
<img src="Copeland%20Logo_16_9_PNG.webp" alt="Copeland logo" class="corner-logo" loading="lazy">
<div class="container">
    <h2>Historial de Operaciones</h2>

    <!-- AGRUPA LOS CONTROLES EN .filters -->
    <div class="filters">
        <div class="filter-field">
            <label for="modelo">Modelo:</label>
            <div class="select-wrapper">
                <input type="text" id="modelo" placeholder="Escriba para buscar modelo">
                <ul id="lista_modelos"> 
                    <?php foreach($modelos as $m): ?>
                        <li><?= $m ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <div class="filter-field">
            <label for="operaciones">Operaciones:</label>
            <select id="operaciones">
                <option value="">Seleccione un modelo primero</option>
            </select>
        </div>

        <div class="filter-field small">
            <label for="fecha_desde">Fecha desde:</label>
            <input type="date" id="fecha_desde" min="2024-01-01" />
        </div>

        <div class="filter-field small">
            <label for="fecha_hasta">Fecha hasta:</label>
            <input type="date" id="fecha_hasta" min="2024-01-01" />
        </div>

        <div class="actions">
            <button id="buscar">Buscar Números de Serie</button>
            <button id="bloquear">Bloquear</button>
            <button id="cajasB">Cajas</button>
        </div>
    </div>
    
    <div>
        <button class="menu-log" onclick="location.href='bienvenida.php'">Regresar al Menú</button>
    </div>

    <div id="seriales"></div>
</div>

<!-- Modal -->
<div id="modalBloqueo" class="modal">
  <div class="modal-content">
    <span id="textoModal"></span><br>
    <button id="okModal">OK</button>
  </div>
</div>

<script>
$(document).ready(function(){
    $('#modelo').on('input', function(){
        let term = $(this).val().toLowerCase();
        $('#lista_modelos li').each(function(){
            let txt = $(this).text().toLowerCase();
            $(this).toggle(txt.includes(term));
        });
        $('#lista_modelos').show();
    });

    $('#lista_modelos li').click(function(){
        let modelo = $(this).text();
        $('#modelo').val(modelo);
        $('#lista_modelos').hide();

        $.ajax({
            url: 'historial.php',
            type: 'POST',
            dataType: 'json',
            data: {modelo: modelo},
            success: function(ops){
                let html = '<option value="">Seleccione operación</option>';
                ops.forEach(op => { html += '<option>'+op+'</option>'; });
                $('#operaciones').html(html);
            }
        });

        $('#seriales').html('');
    });

    $(document).click(function(e){
        if(!$(e.target).closest('.select-wrapper').length){
            $('#lista_modelos').hide();
        }
    });

    $('#buscar').click(function(){
        let modelo = $('#modelo').val();
        let op_id = $('#operaciones').val();
        let fecha_desde = $('#fecha_desde').val();
        let fecha_hasta = $('#fecha_hasta').val();
        if(!modelo || !op_id){ alert('Seleccione modelo y operación primero'); return; }

        $.ajax({
            url: 'historial.php',
            type: 'POST',
            dataType: 'json',
            data: {modelo: modelo, op_id: op_id, start_date: fecha_desde, end_date: fecha_hasta},
            success: function(uuids){
                if(uuids.length === 0){
                    $('#seriales').html('<p class="info-caja">No hay números de serie para esta operación.</p>');
                    return;
                }

                let html = `
                    <button id="exportar_seriales">Exportar Seriales CSV</button>
                    <table>
                        <tr><th>#</th><th>Número de Serie</th></tr>
                `;
                uuids.forEach((u,i)=>{ 
                    html += `<tr><td>${i+1}</td><td>${u}</td></tr>`; 
                });
                html += '</table>';
                $('#seriales').html(html);

                // Acción de exportar
                $('#exportar_seriales').click(function(){
                    let form = $('<form>', {method:'POST', action:'historial.php'});
                    form.append($('<input>', {type:'hidden', name:'accion', value:'exportar_seriales'}));
                    form.append($('<input>', {type:'hidden', name:'modelo', value:modelo}));
                    form.append($('<input>', {type:'hidden', name:'op_id', value:op_id}));
                    $('body').append(form);
                    form.submit();
                    form.remove();
                });
            }
        });
    });

    $('#bloquear').click(function(){
        let modelo = $('#modelo').val();
        let op_id = $('#operaciones').val();
        if(!modelo || !op_id){ alert('Seleccione modelo y operación primero'); return; }

        $.ajax({
            url: 'historial.php',
            type: 'POST',
            dataType: 'json',
            data: {accion:"bloquear", modelo: modelo, op_id: op_id},
            success: function(data){
                $('#textoModal').html(data.error ? data.error : data.success);
                $('#modalBloqueo').fadeIn();
            }
        });
    });

    $('#cajasB').click(function(){
        let modelo = $('#modelo').val();
        if(!modelo){ alert('Seleccione un modelo primero'); return; }

        $.ajax({
            url: 'historial.php',
            type: 'POST',
            dataType: 'json',
            data: {accion:"cajas", modelo: modelo},
            success: function(data){
                if(data.length === 0){
                    $('#seriales').html('<p class="info-caja">No hay cajas recientes para este modelo.</p>');
                    return;
                }

                let html = `
                    <button id="exportar_cajas">Exportar Cajas CSV</button>
                    <table>
                        <tr><th>#</th><th>Box ID</th><th>Modelo</th><th>Fecha Insertada</th><th>Estado</th></tr>
                `;
                data.forEach((c,i)=>{
                    html += `<tr>
                        <td>${i+1}</td>
                        <td>${c.Box_ID}</td>
                        <td>${c.Model_ID}</td>
                        <td>${c.InsertedAt}</td>
                        <td>${c.Estado}</td>
                    </tr>`;
                });
                html += '</table>';
                $('#seriales').html(html);

                // Acción de exportar
                $('#exportar_cajas').click(function(){
                    let form = $('<form>', {method:'POST', action:'historial.php'});
                    form.append($('<input>', {type:'hidden', name:'accion', value:'exportar_cajas'}));
                    form.append($('<input>', {type:'hidden', name:'modelo', value:modelo}));
                    $('body').append(form);
                    form.submit();
                    form.remove();
                });
            }
        });
    });

    $('#okModal').click(function(){ $('#modalBloqueo').fadeOut(); });
});
</script>

</body>
</html>
