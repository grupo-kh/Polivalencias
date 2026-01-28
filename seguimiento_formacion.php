<?php
include 'conexion.php';
error_reporting(E_ALL & ~E_DEPRECATED);

// --- LÓGICA DE GUARDADO ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['btnGuardarFormacion'])) {
    $id_operario = $_POST['id_operario'];
    $operacion = $_POST['operacion'];
    $fecha = $_POST['fecha_formacion'];
    $horas = floatval($_POST['horas_sesion']);

    $sql = "INSERT INTO [dbo].[pol_FormacionSeguimiento] (ID_Operario, Operacion, FechaFormacion, HorasSesion) VALUES (?, ?, ?, ?)";
    $params = array($id_operario, $operacion, $fecha, $horas);

    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) { die(print_r(sqlsrv_errors(), true)); }

    header("Location: seguimiento_formacion.php?id=$id_operario&op=".urlencode($operacion));
    exit;
}

// Variables de estado
$op_seleccionado = isset($_GET['id']) ? $_GET['id'] : '';
$puesto_seleccionado = isset($_GET['op']) ? $_GET['op'] : '';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <?php include 'header_meta.php'; ?>
    <title>KH - Seguimiento de Formación</title>
    <style>
        .sumatorio-card {
            background: #f8f8f8;
            border-left: 5px solid #8c181a;
            padding: 20px;
            margin-bottom: 30px;
        }
        .total-horas { font-size: 24px; color: #8c181a; font-weight: bold; }
        .form-row { display: flex; gap: 20px; flex-wrap: wrap; }
        @media (max-width: 600px) {
            .form-row { flex-direction: column; }
        }
    </style>
</head>
<body style="padding: 20px 15px;">

<div class="header-kh">
    <div style="display:flex; align-items:center; gap:20px;">
        <a href="index.html" style="color:white; text-decoration:none; font-size:24px;">🏠</a>
        <h2 style="margin:0;">INPUT INFORMACIÓN FORMACIONES</h2>
    </div>
    <img src="logo.png" style="height:40px; background:white; padding:2px; border-radius:4px;">
</div>

<div class="container" style="padding: 30px 15px; background: white; margin-top: 20px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">

    <div style="margin-bottom: 25px;">
        <label>Seleccionar Operario:</label>
        <select id="select_op" onchange="cambiarOperario(this.value)">
            <option value="">-- Seleccione Operario --</option>
            <?php
            $resOp = sqlsrv_query($conn, "SELECT ID_Operario, Nombre FROM pol_Operarios WHERE Activo = 1 ORDER BY Nombre");
            while($o = @sqlsrv_fetch_array($resOp, SQLSRV_FETCH_ASSOC)) {
                $sel = ($op_seleccionado == $o['ID_Operario']) ? 'selected' : '';
                echo "<option value='{$o['ID_Operario']}' $sel>".limpiar($o['Nombre'])."</option>";
            }
            ?>
        </select>
    </div>

    <?php if($op_seleccionado): ?>
    <div style="margin-bottom: 25px;">
        <label>Puesto / Polivalencia:</label>
        <select id="select_puesto" onchange="cambiarPuesto(this.value)">
            <option value="">-- Seleccione Puesto --</option>
            <?php
            // Solo cargamos puestos donde el operario ya tiene una polivalencia asignada
            $resPue = sqlsrv_query($conn, "SELECT Operacion FROM pol_Polivalencias WHERE ID_Operario = ?", array($op_seleccionado));
            while($p = @sqlsrv_fetch_array($resPue, SQLSRV_FETCH_ASSOC)) {
                $sel = ($puesto_seleccionado == $p['Operacion']) ? 'selected' : '';
                echo "<option value='".htmlspecialchars($p['Operacion'])."' $sel>".limpiar($p['Operacion'])."</option>";
            }
            ?>
        </select>
    </div>
    <?php endif; ?>

    <?php
    if($op_seleccionado && $puesto_seleccionado):
        $sqlSum = "SELECT SUM(HorasSesion) as Total FROM pol_FormacionSeguimiento WHERE ID_Operario = ? AND Operacion = ?";
        $resSum = sqlsrv_query($conn, $sqlSum, array($op_seleccionado, $puesto_seleccionado));
        $total = sqlsrv_fetch_array($resSum, SQLSRV_FETCH_ASSOC)['Total'] ?? 0;
    ?>
    <div class="sumatorio-card">
        <span style="text-transform: uppercase; font-size: 12px; letter-spacing: 1px;">Horas Acumuladas en <?php echo limpiar($puesto_seleccionado); ?>:</span><br>
        <span class="total-horas"><?php echo number_format($total, 1); ?> Horas</span>
    </div>

    <form method="POST">
        <input type="hidden" name="id_operario" value="<?php echo $op_seleccionado; ?>">
        <input type="hidden" name="operacion" value="<?php echo htmlspecialchars($puesto_seleccionado); ?>">

        <div style="background: #fff; border: 1px solid #eee; padding: 20px; border-radius: 8px;">
            <h4 style="margin-top:0; color: #8c181a;">Registrar Nueva Sesión</h4>
            <div class="form-row">
                <div style="flex-grow: 1;">
                    <label>Fecha Sesión:</label>
                    <input type="date" name="fecha_formacion" required value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div style="flex-grow: 1;">
                    <label>Horas Dedicadas:</label>
                    <input type="number" name="horas_sesion" step="0.5" min="0.5" required placeholder="Ej: 2.5">
                </div>
                <div style="align-self: flex-end; flex-grow: 1;">
                    <button type="submit" name="btnGuardarFormacion" class="btn btn-primary" style="width: 100%;">AÑADIR FORMACIÓN</button>
                </div>
            </div>
        </div>
    </form>

    <h3 style="margin-top:40px; color: #6e6d6b; border-bottom: 2px solid #eee; padding-bottom: 10px;">HISTORIAL DE SESIONES</h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Fecha de Formación</th>
                    <th>Horas de la Sesión</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $resHist = sqlsrv_query($conn, "SELECT FechaFormacion, HorasSesion FROM pol_FormacionSeguimiento WHERE ID_Operario = ? AND Operacion = ? ORDER BY FechaFormacion DESC", array($op_seleccionado, $puesto_seleccionado));
                while($h = sqlsrv_fetch_array($resHist, SQLSRV_FETCH_ASSOC)) {
                    echo "<tr>
                            <td>".($h['FechaFormacion'] ? $h['FechaFormacion']->format('d/m/Y') : '')."</td>
                            <td><b>{$h['HorasSesion']} h</b></td>
                          </tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

</div>

<script>
    function cambiarOperario(id) {
        window.location.href = 'seguimiento_formacion.php?id=' + id;
    }
    function cambiarPuesto(puesto) {
        const urlParams = new URLSearchParams(window.location.search);
        const id = urlParams.get('id');
        window.location.href = 'seguimiento_formacion.php?id=' + id + '&op=' + encodeURIComponent(puesto);
    }
</script>

</body>
</html>
