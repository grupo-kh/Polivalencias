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
header('Content-Type: text/html; charset=utf-8');

function limpiar($texto) {
    if ($texto === null) return "";
    // Si ya es UTF-8, lo dejamos. Si no, intentamos convertir desde ISO-8859-1 (común en Windows/SQL Server)
    if (!mb_check_encoding($texto, 'UTF-8')) {
        $texto = mb_convert_encoding($texto, 'UTF-8', 'ISO-8859-1');
    }
    return htmlspecialchars(trim($texto), ENT_QUOTES, 'UTF-8');
}
?>