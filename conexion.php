<?php
$hostname = "BWDSBK3";
$username = "adcs_tools";
$password = "Corona66!";
$dbadcs = "ADCS_CHI";

$connectionInfo = array( "Database"=>$dbadcs, "UID"=>$username, "PWD"=>$password );
$conn = sqlsrv_connect( $hostname, $connectionInfo );

if (!$conn) {
    echo "No se pudo conectar:<br>";
    die(print_r(sqlsrv_errors(), true));
}


?>

