<?php
include_once 'conexion.php';
error_reporting(E_ALL & ~E_DEPRECATED);

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
$c_seg_h   = detectarColumna($conn, $t_seg, ['Horas', 'Sesion', 'Tiempo']);
$c_hor_op  = detectarColumna($conn, $t_hor, ['Operario', 'Nombre']);
$c_hor_pt  = detectarColumna($conn, $t_hor, ['Puesto', 'Operacion']);
$c_hor_h   = detectarColumna($conn, $t_hor, ['Horas', 'Tiempo']);
$c_ref_pt  = detectarColumna($conn, $t_ref, ['Operacion', 'Puesto']);
$c_ref_req = detectarColumna($conn, $t_ref, ['Requerida', 'Objetivo']);
$c_ope_nom = detectarColumna($conn, $t_ope, ['Nombre', 'Operario']);
$c_ope_baj = detectarColumna($conn, $t_ope, ['Baja', 'Activo']);

// Nueva tabla para asegurar que mostramos polivalencias asignadas
$t_rel = "[dbo].[pol_Relacion_Operarios_Puestos]";
$c_rel_op = detectarColumna($conn, $t_rel, ['Operario', 'Nombre']);
$c_rel_pt = detectarColumna($conn, $t_rel, ['Puesto', 'Operacion']);

// Base de horas: Suma de ambas tablas de imputación (con control de nulos en columnas)
$col_h_hor = $c_hor_h ?: "0";
$col_h_seg = $c_seg_h ?: "0";

$sub_horas = "(
    ISNULL((SELECT SUM(ISNULL(h.$col_h_hor, 0)) FROM $t_hor h WHERE h.$c_hor_op = base.Operario AND h.$c_hor_pt = base.Puesto), 0) +
    ISNULL((SELECT SUM(ISNULL(s2.$col_h_seg, 0)) FROM $t_seg s2 WHERE s2.$c_seg_op = base.Operario AND s2.$c_seg_pt = base.Puesto), 0)
)";

$sql = "SELECT base.Operario, base.Puesto, r.$c_ref_req AS HorasDefinidas,
            ($sub_horas) AS HorasRealizadas
        FROM (
            SELECT $c_seg_op AS Operario, $c_seg_pt AS Puesto FROM $t_seg WHERE $c_seg_op IS NOT NULL
            UNION
            SELECT $c_hor_op AS Operario, $c_hor_pt AS Puesto FROM $t_hor WHERE $c_hor_op IS NOT NULL
            UNION
            SELECT $c_rel_op AS Operario, $c_rel_pt AS Puesto FROM $t_rel WHERE $c_rel_op IS NOT NULL
        ) AS base
        LEFT JOIN $t_ref r ON base.Puesto = r.$c_ref_pt
        LEFT JOIN $t_ope m ON base.Operario = m.$c_ope_nom
        WHERE (m.$c_ope_baj = 'NO' OR m.$c_ope_baj IS NULL OR m.$c_ope_baj = '0')
        GROUP BY base.Operario, base.Puesto, r.$c_ref_req
        HAVING (ISNULL(r.$c_ref_req, 0) - ($sub_horas)) > 0";

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
    <?php include 'header_meta.php'; ?>
    <title>KH - Reporte de Formaciones</title>
    <style>
        .op-card { border-bottom: 8px solid #f0f2f5; }
        .op-name { background: #f8f9fa; padding: 12px 20px; font-weight: bold; color: #8c181a; border-left: 5px solid #b18e3a; }
        .prog-bg { background: #eee; height: 8px; width: 80px; border-radius: 4px; display: inline-block; overflow: hidden; }
        .prog-fill { background: #b18e3a; height: 100%; }
        .footer-stats { background: #222; color: white; padding: 25px; display: flex; justify-content: space-around; text-align: center; flex-wrap: wrap; gap: 20px; }
        .stat-item b { display: block; font-size: 1.8rem; color: #b18e3a; }

        @media print {
            .no-print { display: none !important; }
            body { padding: 0; background: white; }
            .container { box-shadow: none; border: none; max-width: 100%; }
            .op-name { background: #eee !important; -webkit-print-color-adjust: exact; }
            .prog-bg { border: 1px solid #ccc; }
            .prog-fill { background: #b18e3a !important; -webkit-print-color-adjust: exact; }
            .footer-stats { background: #eee !important; color: black !important; }
            .stat-item b { color: #8c181a !important; }
        }
    </style>
</head>
<body style="padding: 20px 15px;">

<div class="header-kh no-print">
    <div style="display:flex; align-items:center; gap:15px;">
        <a href="index.php" style="color:white; text-decoration:none; font-size:24px;">🏠</a>
        <h2 style="margin:0;">Formaciones Abiertas</h2>
    </div>
    <div style="display: flex; gap: 10px;">
        <button onclick="window.print();" class="btn btn-accent">📄 IMPRIMIR PDF</button>
    </div>
</div>

<div class="container" style="background: white; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); overflow: hidden; margin-top: 20px;">
    <?php if (empty($datos)): ?>
        <p style="padding:60px; text-align:center; color:#999;">No hay formaciones pendientes.</p>
    <?php else: ?>
        <?php foreach ($datos as $op => $filas): ?>
            <div class="op-card">
                <div class="op-name">👤 <?php echo limpiar($op); ?></div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th style="text-align:left; padding-left:20px;">Puesto / Operación</th>
                                <th>Objetivo</th>
                                <th>Realizado</th>
                                <th>Pendiente</th>
                                <th>Progreso</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($filas as $f): ?>
                            <tr>
                                <td style="text-align:left; padding-left:20px;"><b><?php echo limpiar($f['puesto']); ?></b></td>
                                <td style="text-align:center;"><?php echo number_format($f['req'], 1); ?> h</td>
                                <td style="text-align:center; color:green;"><?php echo number_format($f['real'], 1); ?> h</td>
                                <td style="text-align:center; color:#8c181a; font-weight:bold;"><?php echo number_format($f['pend'], 1); ?> h</td>
                                <td style="text-align:center;">
                                    <div class="prog-bg"><div class="prog-fill" style="width:<?php echo $f['pct']; ?>%"></div></div>
                                    <small style="margin-left:5px;"><?php echo round($f['pct']); ?>%</small>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
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
