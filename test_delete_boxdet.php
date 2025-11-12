<?php
require_once 'conexion.php';


$connectionInfo = array("Database" => $dbadcs, "UID" => $username, "PWD" => $password);
$conn = sqlsrv_connect($hostname, $connectionInfo);

if (!$conn) {
    die("No se pudo conectar a la base de datos: " . print_r(sqlsrv_errors(), true));
}

$serial = 23329815; 

$sql_detalle = "DELETE FROM dbo.Box_det WHERE B_Id = ?";
$stmt_detalle = sqlsrv_query($conn, $sql_detalle, array($serial));

if ($stmt_detalle === false) {
    echo " Error al eliminar detalles (Box_det):<br>";
    echo "<pre>" . print_r(sqlsrv_errors(), true) . "</pre>";
} else {
    echo " Se eliminaron los detalles correctamente.";
}
?>
