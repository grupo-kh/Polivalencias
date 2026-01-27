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
    <title>Gestión de Usuarios - KH</title>
    <style>
        :root { --kh-red: #8c181a; --kh-gold: #b18e3a; }
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7f6; padding: 20px; }
        .container { max-width: 600px; margin: auto; background: white; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); overflow: hidden; }
        .header { background: var(--kh-red); color: white; padding: 20px; display: flex; justify-content: space-between; align-items: center; }
        form { padding: 20px; background: #eee; border-bottom: 2px solid #ddd; }
        .field { margin-bottom: 10px; }
        label { display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; }
        input, select { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .btn { background: var(--kh-red); color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        .rol-tag { font-size: 10px; font-weight: bold; padding: 3px 8px; border-radius: 10px; background: #ddd; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h2 style="margin:0;">Gestión de Usuarios</h2>
        <a href="index.php" style="color:white; font-size:12px; text-decoration:none;">← Volver al Panel</a>
    </div>
    <form method="POST">
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
        <button type="submit" name="btn_guardar" class="btn">REGISTRAR NUEVO USUARIO</button>
    </form>
    <table>
        <thead><tr><th>Usuario</th><th>Rol</th></tr></thead>
        <tbody>
            <?php while($u = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)): ?>
            <tr>
                <td>👤 <?php echo htmlspecialchars($u['usuario']); ?></td>
                <td><span class="rol-tag"><?php echo $u['rol']; ?></span></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>