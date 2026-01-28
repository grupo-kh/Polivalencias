<?php
include 'conexion.php';
error_reporting(E_ALL & ~E_DEPRECATED);

$id = $_GET['id'] ?? '';

if (empty($id)) {
    die("Error: ID de operario no proporcionado.");
}

// 2. OBTENER DATOS ACTUALES
$sql = "SELECT [Operario], [NombreOperario], [Cargo1], [FechaBaja] FROM [dbo].[pol_Operarios] WHERE [Operario] = ?";
$res = sqlsrv_query($conn, $sql, array($id));
$datos = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC);

if (!$datos) {
    die("Error: Operario no encontrado en la base de datos.");
}

// Formatear fecha para el input type="date" (YYYY-MM-DD)
$fechaBajaInput = "";
if ($datos['FechaBaja'] instanceof DateTime) {
    $fechaBajaInput = $datos['FechaBaja']->format('Y-m-d');
}

// 3. PROCESAR GUARDADO
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = $_POST['nombre'];
    $cargo  = $_POST['cargo'];
    $f_baja = !empty($_POST['fechabaja']) ? $_POST['fechabaja'] : null;

    $sqlUpd = "UPDATE [dbo].[pol_Operarios] SET [NombreOperario] = ?, [Cargo1] = ?, [FechaBaja] = ? WHERE [Operario] = ?";
    $params = array($nombre, $cargo, $f_baja, $id);

    if (sqlsrv_query($conn, $sqlUpd, $params)) {
        header("Location: gestion_operarios.php?msg=updated");
        exit;
    } else {
        die(print_r(sqlsrv_errors(), true));
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <?php include 'header_meta.php'; ?>
    <title>KH - Editar Operario</title>
    <style>
        body { padding: 40px 15px; }
        .card-form { background: white; max-width: 500px; margin: auto; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); overflow: hidden; border-top: 6px solid #8c181a; }
        .form-header { background: #8c181a; color: white; padding: 20px; display: flex; justify-content: space-between; align-items: center; }
        .form-body { padding: 30px; }
        .readonly-id { background: #f9f9f9 !important; color: #8c181a !important; font-weight: bold; border: 1px solid #ddd; }
        .baja-section { margin-top: 20px; padding: 15px; background: #fff5f5; border-left: 4px solid #8c181a; border-radius: 4px; }
        .cancel { display: block; text-align: center; margin-top: 15px; color: #999; text-decoration: none; font-size: 13px; font-weight: bold; }
    </style>
</head>
<body>

<div class="container">
    <div class="card-form">
        <div class="form-header">
            <h2 style="margin:0; font-size:18px; color: white;">EDITAR FICHA OPERARIO</h2>
            <img src="logo.png" style="height:30px; background: white; padding: 2px; border-radius: 4px;">
        </div>

        <div class="form-body">
            <form method="POST">
                <label>Nº Operario (ID):</label>
                <input type="text" class="readonly-id" value="<?php echo limpiar($id); ?>" readonly>

                <label style="margin-top: 15px;">Nombre y Apellidos:</label>
                <input type="text" name="nombre" value="<?php echo limpiar($datos['NombreOperario']); ?>" required>

                <label style="margin-top: 15px;">Cargo / Sección:</label>
                <input type="text" name="cargo" value="<?php echo limpiar($datos['Cargo1']); ?>">

                <div class="baja-section">
                    <label style="margin-top:0; color: #8c181a;">Fecha de Baja:</label>
                    <input type="date" name="fechabaja" value="<?php echo $fechaBajaInput; ?>">
                    <p style="font-size: 11px; margin-top: 8px; color: #8c181a;">
                        * Si introduces una fecha, el operario dejará de ser visible en el listado activo.
                    </p>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 15px; margin-top: 25px; font-size: 16px;">GUARDAR CAMBIOS</button>
                <a href="gestion_operarios.php" class="cancel">← Cancelar y volver al listado</a>
            </form>
        </div>
    </div>
</div>

</body>
</html>
