<?php
include 'conexion.php';
$sql = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE'";
$res = sqlsrv_query($conn, $sql);
echo "<h2>Tablas disponibles en la base de datos:</h2><ul>";
while ($row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)) {
    echo "<li>" . $row['TABLE_NAME'] . "</li>";
}
echo "</ul>";
?>