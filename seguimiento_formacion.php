<?php
include 'conexion.php';

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
    <title>KH - Seguimiento de Formación</title>
    <style>
        body { font-family: sans-serif; margin: 0; color: #6e6d6b; background: #fff; }
        .header-kh { background: #8c181a; color: white; padding: 15px 40px; display: flex; justify-content: space-between; align-items: center; }
        .btn-menu { background: white; color: #8c181a; padding: 8px 15px; text-decoration: none; font-weight: bold; border-radius: 4px; }
        .content { padding: 30px 40px; }
        
        .row { display: flex; align-items: center; margin-bottom: 15px; }
        .label-red { color: #8c181a; width: 180px; font-size: 14px; font-weight: bold; }
        .input-box { border: 1px solid #8c181a; padding: 8px; color: #6e6d6b; font-size: 15px; font-weight: bold; width: 400px; outline: none; }
        
        /* Cuadro de Sumatorio */
        .sumatorio-card { 
            background: #f8f8f8; 
            border-left: 5px solid #8c181a; 
            padding: 20px; 
            margin-bottom: 30px; 
            display: <?php echo $puesto_seleccionado ? 'block' : 'none'; ?>;
        }
        .total-horas { font-size: 24px; color: #8c181a; font-weight: bold; }

        .btn-save { background: #8c181a; color: white; border: none; padding: 10px 25px; cursor: pointer; font-weight: bold; border-radius: 4px; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background: #f2f2f2; color: #8c181a; padding: 10px; text-align: left; border-bottom: 2px solid #8c181a; }
        td { padding: 10px; border-bottom: 1px solid #eee; }
    </style>
</head>
<body>

<div class="header-kh">
    <div style="display:flex; align-items:center; gap:20px;">
        <a href="index.php" class="btn-menu">Ir al Menú</a>
        <h2 style="margin:0;">INPUT INFORMACIÓN FORMACIONES</h2>
    </div>
    <img src="logo.png" style="height:35px; background:white; padding:5px; border-radius:4px;">
</div>

<div class="content">
    
    <div class="row">
        <span class="label-red">Seleccionar Operario:</span>
        <select id="select_op" class="input-box" onchange="cambiarOperario(this.value)">
            <option value="">-- Seleccione Operario --</option>
            <?php
            $resOp = sqlsrv_query($conn, "SELECT ID_Operario, Nombre FROM pol_Operarios WHERE Activo = 1 ORDER BY Nombre");
            while($o = sqlsrv_fetch_array($resOp, SQLSRV_FETCH_ASSOC)) {
                $sel = ($op_seleccionado == $o['ID_Operario']) ? 'selected' : '';
                echo "<option value='{$o['ID_Operario']}' $sel>{$o['Nombre']}</option>";
            }
            ?>
        </select>
    </div>

    <?php if($op_seleccionado): ?>
    <div class="row">
        <span class="label-red">Puesto / Polivalencia:</span>
        <select id="select_puesto" class="input-box" onchange="cambiarPuesto(this.value)">
            <option value="">-- Seleccione Puesto --</option>
            <?php
            // Solo cargamos puestos donde el operario ya tiene una polivalencia asignada
            $resPue = sqlsrv_query($conn, "SELECT Operacion FROM pol_Polivalencias WHERE ID_Operario = ?", array($op_seleccionado));
            while($p = sqlsrv_fetch_array($resPue, SQLSRV_FETCH_ASSOC)) {
                $sel = ($puesto_seleccionado == $p['Operacion']) ? 'selected' : '';
                echo "<option value='".htmlspecialchars($p['Operacion'])."' $sel>{$p['Operacion']}</option>";
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
        <span style="text-transform: uppercase; font-size: 12px; letter-spacing: 1px;">Horas Acumuladas en <?php echo $puesto_seleccionado; ?>:</span><br>
        <span class="total-horas"><?php echo number_format($total, 1); ?> Horas</span>
    </div>

    <form method="POST">
        <input type="hidden" name="id_operario" value="<?php echo $op_seleccionado; ?>">
        <input type="hidden" name="operacion" value="<?php echo htmlspecialchars($puesto_seleccionado); ?>">
        
        <div style="background: #fff; border: 1px solid #eee; padding: 20px; border-radius: 8px;">
            <h4 style="margin-top:0; color: #8c181a;">Registrar Nueva Sesión</h4>
            <div style="display: flex; gap: 20px;">
                <div>
                    <span class="label-red" style="display:block; margin-bottom:5px;">Fecha Sesión:</span>
                    <input type="date" name="fecha_formacion" class="input-box" style="width:200px;" required value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div>
                    <span class="label-red" style="display:block; margin-bottom:5px;">Horas Dedicadas:</span>
                    <input type="number" name="horas_sesion" step="0.5" min="0.5" class="input-box" style="width:150px;" required placeholder="Ej: 2.5">
                </div>
                <div style="align-self: flex-end;">
                    <button type="submit" name="btnGuardarFormacion" class="btn-save">AÑADIR FORMACIÓN</button>
                </div>
            </div>
        </div>
    </form>

    <h3 style="margin-top:40px; color: #6e6d6b; border-bottom: 1px solid #eee; padding-bottom: 10px;">Historial de sesiones</h3>
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