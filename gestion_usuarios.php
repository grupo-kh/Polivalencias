<?php
session_start();
include 'conexion.php';
error_reporting(E_ALL & ~E_DEPRECATED);

// BLOQUEO DE SEGURIDAD: Solo Administradores
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'Administrador') {
    header("Location: index.php");
    exit();
}

// PROCESAR ALTA DE USUARIO
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['btn_guardar'])) {
    $user = $_POST['usuario'];
    $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $rol  = $_POST['rol'];

    $sql = "INSERT INTO [dbo].[pol_Usuarios] (usuario, password_hash, rol) VALUES (?, ?, ?)";
    $params = array($user, $pass, $rol);
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt) { echo "<script>alert('Usuario creado con éxito');</script>"; }
}

// OBTENER USUARIOS
$res = sqlsrv_query($conn, "SELECT id, usuario, rol FROM [dbo].[pol_Usuarios]");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <?php include 'header_meta.php'; ?>
    <title>Gestión de Usuarios - KH</title>
    <style>
        .form-usuarios { padding: 20px; background: #eee; border-bottom: 2px solid #ddd; }
        .field { margin-bottom: 10px; }
        .rol-tag { font-size: 10px; font-weight: bold; padding: 3px 8px; border-radius: 10px; background: #ddd; }
    </style>
</head>
<body>
<div class="header-kh">
    <div style="display:flex; align-items:center; gap:20px;">
        <a href="index.php" style="color:white; text-decoration:none; font-size:24px;">🏠</a>
        <h2 style="margin:0;">GESTIÓN DE USUARIOS</h2>
    </div>
    <img src="logo.png" style="height:40px; background: white; padding: 2px; border-radius: 4px;">
</div>

<div class="container" style="padding: 20px 15px;">
    <div class="card" style="max-width: 600px; margin: auto; background: white; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); overflow: hidden; border-top: 6px solid #8c181a;">
        <form method="POST" class="form-usuarios">
            <div class="field">
                <label>Nombre de Usuario</label>
                <input type="text" name="usuario" required placeholder="Ej: alberto.kh">
            </div>
            <div class="field">
                <label>Contraseña</label>
                <input type="password" name="password" required>
            </div>
            <div class="field">
                <label>Rol de Usuario</label>
                <select name="rol">
                    <option value="Administrador">Administrador (Acceso Total)</option>
                    <option value="Tecnico">Técnico (Solo Edición)</option>
                    <option value="Usuario">Usuario (Solo Lectura)</option>
                </select>
            </div>
            <button type="submit" name="btn_guardar" class="btn btn-primary" style="width: 100%; margin-top: 10px;">REGISTRAR NUEVO USUARIO</button>
        </form>
        <div class="table-responsive">
            <table>
                <thead><tr><th>Usuario</th><th>Rol</th></tr></thead>
                <tbody>
                    <?php while($u = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)): ?>
                    <tr>
                        <td>👤 <?php echo limpiar($u['usuario']); ?></td>
                        <td><span class="rol-tag"><?php echo limpiar($u['rol']); ?></span></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
