<?php
include 'conexion.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 1. CALCULAR EL PRÓXIMO NÚMERO DE OPERARIO AUTOMÁTICAMENTE
$sqlMax = "SELECT MAX(CAST([Operario] AS INT)) as ultimo FROM [dbo].[pol_Operarios]";
$resMax = sqlsrv_query($conn, $sqlMax);
$rowMax = sqlsrv_fetch_array($resMax, SQLSRV_FETCH_ASSOC);

// Si no hay ninguno, empezamos en 1. Si hay, sumamos 1 al máximo.
$proximoID = ($rowMax['ultimo']) ? $rowMax['ultimo'] + 1 : 1;

$mensaje = "";

// 2. PROCESAR EL GUARDADO
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Tomamos el ID del campo oculto o del cálculo inicial
    $id      = $_POST['id_generado'];
    $nombre  = $_POST['nombre'];
    $cargo   = $_POST['cargo'] ?? '';

    $sql = "INSERT INTO [dbo].[pol_Operarios] ([Operario], [NombreOperario], [Cargo1]) VALUES (?, ?, ?)";
    $params = array($id, $nombre, $cargo);

    $stmt = sqlsrv_query($conn, $sql, $params);

    if($stmt) {
        header("Location: gestion_operarios.php?msg=created");
        exit;
    } else {
        $errors = sqlsrv_errors();
        $mensaje = "<div style='color:red; margin-bottom: 15px;'>Error al guardar: " . $errors[0]['message'] . "</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php include 'header_meta.php'; ?>
    <title>KH - Nuevo Operario</title>
    <style>
        body { padding: 40px 15px; }
        .card-form { background: white; max-width: 500px; margin: auto; padding: 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); border-top: 6px solid #8c181a; }
        h2 { color: #8c181a; margin-top: 0; text-align: center; }
        input[readonly] { background: #eee; cursor: not-allowed; font-weight: bold; color: #8c181a; }
        .btn-save { background: #8c181a; color: white; border: none; padding: 15px; width: 100%; margin-top: 25px; cursor: pointer; border-radius: 4px; font-weight: bold; text-transform: uppercase; }
        @media (max-width: 480px) {
            .card-form { padding: 20px; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="card-form">
        <h2>NUEVO OPERARIO</h2>
        <?php echo $mensaje; ?>

        <form method="POST">
            <div style="margin-bottom: 15px;">
                <label>Número de Operario (Automático):</label>
                <input type="text" name="id_generado" value="<?php echo $proximoID; ?>" readonly>
            </div>

            <div style="margin-bottom: 15px;">
                <label>Apellidos, Nombre:</label>
                <input type="text" name="nombre" placeholder="Ej: PÉREZ GARCÍA, JUAN" required autofocus>
            </div>

            <div style="margin-bottom: 15px;">
                <label>Cargo / Sección:</label>
                <input type="text" name="cargo" placeholder="Ej: Montaje / Línea 1">
            </div>

            <button type="submit" class="btn-save">DAR DE ALTA</button>
            <a href="gestion_operarios.php" style="display:block; text-align:center; margin-top:15px; color:#999; text-decoration:none; font-size:14px; font-weight: bold;">← Cancelar y volver</a>
        </form>
    </div>
</div>

</body>
</html>
