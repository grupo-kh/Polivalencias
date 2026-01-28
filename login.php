<?php
session_start();
include 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usuario = $_POST['usuario'];
    $password = $_POST['password'];

    $sql = "SELECT usuario, password_hash, rol FROM [dbo].[pol_Usuarios] WHERE usuario = ?";
    $params = array($usuario);
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt && $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        if (password_verify($password, $row['password_hash'])) {
            $_SESSION['usuario'] = $row['usuario'];
            $_SESSION['rol'] = $row['rol'];
            header("Location: index.php");
            exit();
        } else {
            $error = "Contraseña incorrecta";
        }
    } else {
        $error = "El usuario no existe";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php include 'header_meta.php'; ?>
    <title>Login - KH Polivalencias</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #8c181a; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 20px; box-sizing: border-box; }
        .login-box { background: white; padding: 40px; border-radius: 10px; box-shadow: 0 10px 25px rgba(0,0,0,0.3); width: 100%; max-width: 350px; text-align: center; }
        img { width: 100px; margin-bottom: 20px; }
        input { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box; font-size: 16px; }
        button { width: 100%; padding: 12px; background: #b18e3a; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; font-size: 16px; text-transform: uppercase; transition: background 0.3s; }
        button:hover { background: #967931; }
        .error { color: #8c181a; font-size: 14px; margin-bottom: 15px; font-weight: bold; }
        h3 { color: #6e6d6b; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="login-box">
        <img src="logo.png" alt="KH Logo">
        <h3>Acceso al Sistema</h3>
        <?php if(isset($error)) echo "<p class='error'>$error</p>"; ?>
        <form method="POST">
            <input type="text" name="usuario" placeholder="Usuario" required autofocus>
            <input type="password" name="password" placeholder="Contraseña" required>
            <button type="submit">ENTRAR</button>
        </form>
    </div>
</body>
</html>
