<?php
include 'conexion.php';
error_reporting(E_ALL & ~E_DEPRECATED);

function limpiar($texto) {
    return ($texto === null) ? "" : htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
}

$opSel = $_GET['operario'] ?? '';
$puestoSel = $_GET['puesto'] ?? '';

// --- LÓGICA: ELIMINAR IMPUTACIÓN ---
if (isset($_GET['eliminar_id'])) {
    sqlsrv_query($conn, "DELETE FROM [dbo].[pol_Formacion_Imputacion] WHERE Id_Imputacion = ?", array($_GET['eliminar_id']));
    header("Location: formacion.php?operario=".urlencode($opSel)."&puesto=".urlencode($puestoSel));
    exit;
}

// --- LÓGICA: GUARDAR NUEVA IMPUTACIÓN (CORREGIDA) ---
if (isset($_POST['btnImputar'])) {
    $sqlM = sqlsrv_query($conn, "SELECT MAX(Id_Imputacion) as m FROM [dbo].[pol_Formacion_Imputacion]");
    $newId = ($sqlM && $rM = sqlsrv_fetch_array($sqlM, SQLSRV_FETCH_ASSOC)) ? ($rM['m'] + 1) : 1;
    
    // Convertimos el string del input date a un objeto DateTime de PHP
    // Esto evita errores de formato nvarchar -> datetime en SQL Server
    $fecha_formateada = date_create($_POST['f_fecha']);

    $sqlIns = "INSERT INTO [dbo].[pol_Formacion_Imputacion] (Id_Imputacion, NombreOperario, Operacion, Fecha, Horas) VALUES (?,?,?,?,?)";
    
    // Pasamos $fecha_formateada directamente como objeto
    $params = array(
        $newId, 
        $_POST['f_op'], 
        $_POST['f_puesto'], 
        $fecha_formateada, 
        $_POST['f_horas']
    );
    
    $stmt = sqlsrv_query($conn, $sqlIns, $params);

    if($stmt) {
        header("Location: formacion.php?operario=".urlencode($_POST['f_op'])."&puesto=".urlencode($_POST['f_puesto']));
        exit;
    } else {
        // Si falla, mostramos el error detallado
        die(print_r(sqlsrv_errors(), true));
    }
}

// --- CÁLCULOS DE HORAS Y ESTADO ---
$horasRequeridas = 0;
$totalImputado = 0;

if ($puestoSel) {
    $resH = sqlsrv_query($conn, "SELECT HorasRequeridasFormacion FROM [dbo].[pol_MatrizDefinicion] WHERE Operacion = ?", array($puestoSel));
    if ($rowH = sqlsrv_fetch_array($resH, SQLSRV_FETCH_ASSOC)) $horasRequeridas = (int)$rowH['HorasRequeridasFormacion'];
}

if ($opSel && $puestoSel) {
    $resT = sqlsrv_query($conn, "SELECT SUM(Horas) as total FROM [dbo].[pol_Formacion_Imputacion] WHERE NombreOperario = ? AND Operacion = ?", array($opSel, $puestoSel));
    if ($resT) {
        $rowT = sqlsrv_fetch_array($resT, SQLSRV_FETCH_ASSOC);
        $totalImputado = $rowT['total'] ?? 0;
    }
}

$porcentaje = ($horasRequeridas > 0) ? min(100, round(($totalImputado / $horasRequeridas) * 100)) : 0;
$estado = ($totalImputado >= $horasRequeridas && $horasRequeridas > 0) ? "CUMPLIMENTADAS" : "NO CUMPLIMENTADAS";
$colorEstado = ($estado == "CUMPLIMENTADAS") ? "#28a745" : "#dc3545";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>KH - Formación</title>
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
    <style>
        body { font-family: sans-serif; background: #f4f4f4; margin: 0; }
        .container { max-width: 1000px; margin: auto; background: white; min-height: 100vh; box-shadow: 0 0 15px rgba(0,0,0,0.1); }
        .header { background: #8c181a; color: white; padding: 20px; display: flex; justify-content: space-between; align-items: center; }
        .section { padding: 25px; border-bottom: 1px solid #eee; }
        label { display: block; font-weight: bold; margin-bottom: 8px; color: #8c181a; font-size: 13px; }
        .badge-horas { float: right; background: white; color: #8c181a; padding: 15px; border: 3px solid #8c181a; border-radius: 8px; font-size: 35px; font-weight: bold; text-align: center; min-width: 100px; }
        .status-bar { padding: 15px; border-radius: 4px; color: white; font-weight: bold; text-align: center; margin-top: 15px; font-size: 18px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background: #8c181a; color: white; padding: 10px; text-align: left; }
        td { padding: 10px; border-bottom: 1px solid #ddd; }
        .input-form { padding: 8px; border: 1px solid #ccc; border-radius: 4px; width: 95%; }
        .btn-add { background: #8c181a; color: white; border: none; padding: 10px 20px; cursor: pointer; font-weight: bold; border-radius: 4px; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <div style="display:flex; align-items:center; gap:15px;">
            <a href="index.php" style="color:white; text-decoration:none; font-size:24px;">🏠</a>
            <h1 style="margin:0; font-size: 22px;">FORMACIÓN DE OPERARIOS</h1>
        </div>
        <img src="logo.png" style="height:40px;">
    </div>

    <div class="section">
        <div class="badge-horas">
            <div style="font-size:11px; text-transform:uppercase; line-height:10px;">Horas formación:</div>
            <?php echo $horasRequeridas; ?>
        </div>

        <div style="max-width: 500px;">
            <label>Operario:</label>
            <select id="sel-op">
                <option value="">-- Seleccionar --</option>
                <?php
                $resOp = sqlsrv_query($conn, "SELECT NombreOperario FROM [dbo].[pol_Operarios] WHERE FechaBaja IS NULL OR FechaBaja='' ORDER BY NombreOperario");
                while($o = sqlsrv_fetch_array($resOp, SQLSRV_FETCH_ASSOC)) {
                    $n = trim($o['NombreOperario']);
                    echo "<option value='".htmlspecialchars($n)."' ".($opSel==$n?'selected':'').">".limpiar($n)."</option>";
                }
                ?>
            </select>

            <label style="margin-top:20px;">Puesto / Polivalencia:</label>
            <select id="sel-puesto">
                <option value="">-- Seleccionar --</option>
                <?php
                $resP = sqlsrv_query($conn, "SELECT Operacion FROM [dbo].[pol_MatrizDefinicion] ORDER BY Operacion");
                while($p = sqlsrv_fetch_array($resP, SQLSRV_FETCH_ASSOC)) {
                    $pn = trim($p['Operacion']);
                    echo "<option value='".htmlspecialchars($pn)."' ".($puestoSel==$pn?'selected':'').">".limpiar($pn)."</option>";
                }
                ?>
            </select>
        </div>

        <?php if($puestoSel): ?>
            <div class="status-bar" style="background: <?php echo $colorEstado; ?>;">
                <?php echo $estado; ?> (<?php echo $porcentaje; ?>% - <?php echo (float)$totalImputado; ?> de <?php echo $horasRequeridas; ?>h)
            </div>
        <?php endif; ?>
    </div>

    <?php if($opSel && $puestoSel): ?>
    <div class="section">
        <h3 style="color:#8c181a; font-size:16px;">IMPUTAR JORNADA DE FORMACIÓN</h3>
        <table>
            <thead>
                <tr><th>Fecha</th><th>Horas</th><th>Acción</th></tr>
            </thead>
            <tbody>
                <form method="POST">
                    <input type="hidden" name="f_op" value="<?php echo htmlspecialchars($opSel); ?>">
                    <input type="hidden" name="f_puesto" value="<?php echo htmlspecialchars($puestoSel); ?>">
                    <tr style="background: #fff8f8;">
                        <td><input type="date" name="f_fecha" class="input-form" value="<?php echo date('Y-m-d'); ?>" required></td>
                        <td><input type="number" name="f_horas" class="input-form" step="0.5" min="0.5" max="12" placeholder="Ej: 8" required></td>
                        <td><button type="submit" name="btnImputar" class="btn-add">REGISTRAR</button></td>
                    </tr>
                </form>
                <?php
                $resList = sqlsrv_query($conn, "SELECT * FROM [dbo].[pol_Formacion_Imputacion] WHERE NombreOperario = ? AND Operacion = ? ORDER BY Fecha DESC", array($opSel, $puestoSel));
                if ($resList) {
                    while($rowL = sqlsrv_fetch_array($resList, SQLSRV_FETCH_ASSOC)):
                ?>
                <tr>
                    <td><?php echo ($rowL['Fecha'] instanceof DateTime) ? $rowL['Fecha']->format('d/m/Y') : $rowL['Fecha']; ?></td>
                    <td><strong><?php echo (float)$rowL['Horas']; ?> h</strong></td>
                    <td><a href="?operario=<?php echo urlencode($opSel); ?>&puesto=<?php echo urlencode($puestoSel); ?>&eliminar_id=<?php echo $rowL['Id_Imputacion']; ?>" style="color:#dc3545; font-size:11px;" onclick="return confirm('¿Borrar registro?')">Eliminar</a></td>
                </tr>
                <?php 
                    endwhile; 
                }
                ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<script>
    var sOp = new TomSelect("#sel-op", { onChange: function(v){ if(v) window.location.href="formacion.php?operario="+encodeURIComponent(v)+"&puesto=<?php echo urlencode($puestoSel); ?>"; } });
    var sPu = new TomSelect("#sel-puesto", { onChange: function(v){ if(v) window.location.href="formacion.php?operario=<?php echo urlencode($opSel); ?>&puesto="+encodeURIComponent(v); } });
</script>
</body>
</html>