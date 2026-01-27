<?php
// C:\xampp\htdocs\Polivalencias\conexion.php
$serverName = "127.0.0.1\SQLEXPRESS"; 

$connectionInfo = array(
    "Database" => "KH_Gestion",
    "UID" => "", 
    "PWD" => "", 
    "CharacterSet" => "UTF-8", 
    "ReturnDatesAsStrings" => true,
    "TrustServerCertificate" => true
);

$conn = sqlsrv_connect($serverName, $connectionInfo);

// Esta línea ayuda a que PHP no se confunda con los formatos de Windows
ini_set('default_charset', 'UTF-8');
?>