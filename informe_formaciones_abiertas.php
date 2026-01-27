<?php
include 'conexion.php';
error_reporting(E_ALL & ~E_DEPRECATED);

function limpiar($t) { return ($t === null) ? "" : htmlspecialchars(trim($t), ENT_QUOTES, 'UTF-8'); }

/** * Lógica de detección de columnas (Mantenemos la misma que funcionó)
 */
function detectarColumna($conn, $tabla, $opciones) {
    $tablaLimpia = str_replace(['[dbo].', '[', ']'], '', $tabla);
    $sql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '$tablaLimpia'";
    $stmt = sqlsrv_query($conn, $sql);
    $columnasEncontradas = [];
    if ($stmt) {
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) { $columnasEncontradas[] = $row['COLUMN_NAME']; }
        foreach ($opciones as $busqueda) {
            foreach ($columnasEncontradas as $colReal) {
                $simplificado = strtolower(str_replace([' ', '_', 'á', 'é', 'í', 'ó', 'ú'], '', $colReal));
                $busquedaSimp = strtolower(str_replace([' ', '_'], '', $busqueda));
                if (strpos($simplificado, $busquedaSimp) !== false) return "[" . $colReal . "]";
            }
        }
    }
    return null;
}

$t_seg = "[dbo].[pol_FormacionSeguimiento]"; 
$t_hor = "[dbo].[pol_Formacion_Imputacion]";   
$t_ref = "[dbo].[pol_MatrizDefinicion]";      
$t_ope = "[dbo].[pol_Operarios]";       

$c_seg_op  = detectarColumna($conn, $t_seg, ['Operario', 'Nombre']);
$c_seg_pt  = detectarColumna($conn, $t_seg, ['Puesto', 'Operacion']);
$c_seg_val = detectarColumna($conn, $t_seg, ['Validac', 'Estado']);
$c_hor_op  = detectarColumna($conn, $t_hor, ['Operario', 'Nombre']);
$c_hor_pt  = detectarColumna($conn, $t_hor, ['Puesto', 'Operacion']);
$c_hor_h   = detectarColumna($conn, $t_hor, ['Horas', 'Tiempo']);
$c_ref_pt  = detectarColumna($conn, $t_ref, ['Operacion', 'Puesto']);
$c_ref_req = detectarColumna($conn, $t_ref, ['Requerida', 'Objetivo']);
$c_ope_nom = detectarColumna($conn, $t_ope, ['Nombre', 'Operario']);
$c_ope_baj = detectarColumna($conn, $t_ope, ['Baja', 'Activo']);

$sql = "SELECT s.$c_seg_op AS Operario, s.$c_seg_pt AS Puesto, r.$c_ref_req AS HorasDefinidas,
            (SELECT SUM(ISNULL(h.$c_hor_h, 0)) FROM $t_hor h WHERE h.$c_hor_op = s.$c_seg_op AND h.$c_hor_pt = s.$c_seg_pt) AS HorasRealizadas
        FROM $t_seg s
        LEFT JOIN $t_ref r ON s.$c_seg_pt = r.$c_ref_pt
        LEFT JOIN $t_ope m ON s.$c_seg_op = m.$c_ope_nom
        WHERE (m.$c_ope_baj = 'NO' OR m.$c_ope_baj IS NULL OR m.$c_ope_baj = '0')
          AND (s.$c_seg_val IS NULL OR s.$c_seg_val <> 'OK')
        GROUP BY s.$c_seg_op, s.$c_seg_pt, r.$c_ref_req
        HAVING (ISNULL(r.$c_ref_req, 0) - ISNULL((SELECT SUM(h.$c_hor_h) FROM $t_hor h WHERE h.$c_hor_op = s.$c_seg_op AND h.$c_hor_pt = s.$c_seg_pt), 0)) > 0";

$res = sqlsrv_query($conn, $sql);
$datos = []; $total_h_pendientes = 0; $total_puestos_abiertos = 0;
if ($res) {
    while ($row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)) {
        $op = $row['Operario']; $req = (float)$row['HorasDefinidas']; $real = (float)$row['HorasRealizadas']; $pend = $req - $real;
        if ($pend > 0) {
            $pct = ($req > 0) ? ($real / $req) * 100 : 0;
            $datos[$op][] = ['puesto' => $row['Puesto'], 'req' => $req, 'real' => $real, 'pend' => $pend, 'pct' => $pct];
            $total_h_pendientes += $pend; $total_puestos_abiertos++;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>KH - Reporte de Formaciones</title>
    <style>
        :root { --kh-red: #8c181a; --kh-gold: #b18e3a; }
        body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; margin: 0; padding: 20px; }
        .container { max-width: 1000px; margin: auto; background: white; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); overflow: hidden; }
        .header { background: var(--kh-red); color: white; padding: 20px; display: flex; justify-content: space-between; align-items: center; }
        .actions { display: flex; gap: 10px; }
        .btn { padding: 8px 15px; border-radius: 5px; text-decoration: none; font-size: 14px; cursor: pointer; border: 1px solid white; transition: 0.3s; }
        .btn-volver { color: white; background: transparent; }
        .btn-pdf { color: white; background: var(--kh-gold); border-color: var(--kh-gold); }
        .btn:hover { opacity: 0.8; background: white; color: var(--kh-red); }
        
        .op-card { border-bottom: 8px solid #f0f2f5; }
        .op-name { background: #f8f9fa; padding: 12px 20px; font-weight: bold; color: var(--kh-red); border-left: 5px solid var(--kh-gold); }
        table { width: 100%; border-collapse: collapse; }
        th { font-size: 11px; color: #888; text-transform: uppercase; padding: 10px; border-bottom: 2px solid #eee; }
        td { text-align: center; padding: 12px; border-bottom: 1px solid #f9f9f9; font-size: 14px; }
        .prog-bg { background: #eee; height: 8px; width: 80px; border-radius: 4px; display: inline-block; overflow: hidden; }
        .prog-fill { background: var(--kh-gold); height: 100%; }
        
        .footer-stats { background: #222; color: white; padding: 25px; display: flex; justify-content: space-around; text-align: center; }
        .stat-item b { display: block; font-size: 1.8rem; color: var(--kh-gold); }

        /* ESTILOS PARA IMPRESIÓN / PDF */
        @media print {
            .actions, .btn-volver, .btn-pdf { display: none !important; }
            body { padding: 0; background: white; }
            .container { box-shadow: none; border: none; max-width: 100%; }
            .header { background: white !important; color: black !important; border-bottom: 2px solid var(--kh-red); }
            .op-name { background: #eee !important; -webkit-print-color-adjust: exact; }
            .prog-bg { border: 1px solid #ccc; }
            .prog-fill { background: var(--kh-gold) !important; -webkit-print-color-adjust: exact; }
            .footer-stats { background: #eee !important; color: black !important; }
            .stat-item b { color: var(--kh-red) !important; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <div>
            <h2 style="margin:0;">Formaciones Abiertas</h2>
            <small style="opacity:0.8;">Fecha de reporte: <?php echo date('d/m/Y H:i'); ?></small>
        </div>
        <div class="actions">
            <button onclick="window.print();" class="btn btn-pdf">📄 IMPRIMIR PDF</button>
            <a href="index.php" class="btn btn-volver">← VOLVER AL MENÚ</a>
        </div>
    </div>

    <?php if (empty($datos)): ?>
        <p style="padding:60px; text-align:center; color:#999;">No hay formaciones pendientes.</p>
    <?php else: ?>
        <?php foreach ($datos as $op => $filas): ?>
            <div class="op-card">
                <div class="op-name">👤 <?php echo limpiar($op); ?></div>
                <table>
                    <thead>
                        <tr>
                            <th align="left" style="padding-left:20px;">Puesto / Operación</th>
                            <th>Objetivo</th>
                            <th>Realizado</th>
                            <th>Pendiente</th>
                            <th>Progreso</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($filas as $f): ?>
                        <tr>
                            <td align="left" style="padding-left:20px;"><b><?php echo limpiar($f['puesto']); ?></b></td>
                            <td><?php echo number_format($f['req'], 1); ?> h</td>
                            <td style="color:green;"><?php echo number_format($f['real'], 1); ?> h</td>
                            <td style="color:var(--kh-red); font-weight:bold;"><?php echo number_format($f['pend'], 1); ?> h</td>
                            <td>
                                <div class="prog-bg"><div class="prog-fill" style="width:<?php echo $f['pct']; ?>%"></div></div>
                                <small style="margin-left:5px;"><?php echo round($f['pct']); ?>%</small>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <div class="footer-stats">
        <div class="stat-item">
            <b><?php echo count($datos); ?></b>
            <span>Operarios</span>
        </div>
        <div class="stat-item">
            <b><?php echo $total_puestos_abiertos; ?></b>
            <span>Puestos Abiertos</span>
        </div>
        <div class="stat-item">
            <b><?php echo number_format($total_h_pendientes, 1); ?></b>
            <span>Horas Pendientes</span>
        </div>
    </div>
</div>

</body>
</html>