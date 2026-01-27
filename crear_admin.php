<?php
include 'conexion.php';

$usuario = 'Alberto';
$password_plana = '7876';
$rol = 'Administrador';

// Generamos el hash exacto que PHP entiende
$password_hash = password_hash($password_plana, PASSWORD_DEFAULT);

// Primero borramos si existe para evitar duplicados
sqlsrv_query($conn, "DELETE FROM [dbo].[pol_Usuarios] WHERE usuario = 'Alberto'");

// Insertamos con el hash generado por PHP
$sql = "INSERT INTO [dbo].[pol_Usuarios] (usuario, password_hash, rol) VALUES (?, ?, ?)";
$params = array($usuario, $password_hash, $rol);
$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt) {
    echo "Usuario Administrador creado/actualizado correctamente. <br>";
    echo "Usuario: Alberto <br> Contraseña: 7876 <br>";
    echo "<a href='login.php'>Ir al Login</a>";
} else {
    die(print_r(sqlsrv_errors(), true));
}
?>