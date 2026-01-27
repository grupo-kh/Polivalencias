<?php
include 'conexion.php';
error_reporting(E_ALL & ~E_DEPRECATED);

function limpiar($t) { return ($t === null) ? "" : htmlspecialchars(trim($t), ENT_QUOTES, 'UTF-8'); }

// Consulta: Une la relación de puestos con la definición de horas y suma lo imputado
$sql = "SELECT 
            R.NombreOperario, 
            R.Operacion, 
            R.Porcentaje as NivelActual,
            ISNULL(M.HorasRequeridasFormacion, 0) as HorasObjetivo,
            ISNULL(SUM(I.Horas), 0) as HorasRealizadas
        FROM [dbo].[pol_Relacion_Operarios_Puestos] R
        INNER JOIN [dbo].[pol_MatrizDefinicion] M ON R.Operacion = M.Operacion
        LEFT JOIN [dbo].[pol_Formacion_Imputacion] I ON R.NombreOperario = I.NombreOperario AND R.Operacion = I.Operacion
        GROUP BY R.NombreOperario, R.Operacion, R.Porcentaje, M.HorasRequeridasFormacion
        ORDER BY R.NombreOperario ASC, R.Operacion ASC";

$res = sqlsrv_query($conn, $sql);
if (!$res) { die(print_r(sqlsrv_errors(), true)); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>KH - Seguimiento de Horas</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f4f4; margin: 0; padding: 20px; }
        .header-report { background: #8c181a; color: white; padding: 20px; display: flex; justify-content: space-between; align-items: center; border-radius: 8px 8px 0 0; }
        .container { background: white; padding: 20px; border-radius: 0 0 8px 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #f8f9fa; text-align: left; padding: 12px; font-size: 12px; color: #333; border-bottom: 2px solid #dee2e6; }
        td { padding: 12px; border-bottom: 1px solid #eee; font-size: 13px; }
        
        /* Estética de la barra de progreso */
        .progress-bg { background: #e9ecef; border-radius: 10px; width: 100%; height: 12px; overflow: hidden; border: 1px solid #ddd; }
        .progress-fill { background: #8c181a; height: 100%; transition: width 0.4s ease; }
        
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 10px; font-weight: bold; color: white; display: inline-block; }
        .bg-success { background: #28a745; }
        .bg-warning { background: #ffc107; color: #333; }

        @media print { .no-print { display: none; } body { padding: 0; } }
    </style>
</head>
<body>

<div class="header-report">
    <div style="display:flex; align-items:center; gap:15px;">
        <a href="index.php" style="color:white; text-decoration:none; font-size:20px;" class="no-print">🏠</a>
        <h2 style="margin:0; font-size: 1.2rem;">SEGUIMIENTO DE HORAS DE FORMACIÓN</h2>
    </div>
    <button onclick="window.print()" class="no-print" style="padding:8px 15px; cursor:pointer; font-weight:bold;">🖨️ IMPRIMIR</button>
</div>

<div class="container">
    <table>
        <thead>
            <tr>
                <th>OPERARIO</th>
                <th>PUESTO / POLIVALENCIA</th>
                <th style="text-align:center;">NIVEL</th>
                <th>PROGRESO DE HORAS (REAL / OBJETIVO)</th>
                <th style="text-align:center;">ESTADO</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)): 
                $hReal = (float)$row['HorasRealizadas'];
                $hObj = (float)$row['HorasObjetivo'];
                $pct = ($hObj > 0) ? min(100, round(($hReal / $hObj) * 100)) : 0;
                $isDone = ($hReal >= $hObj && $hObj > 0);
            ?>
            <tr>
                <td><strong><?php echo limpiar($row['NombreOperario']); ?></strong></td>
                <td><?php echo limpiar($row['Operacion']); ?></td>
                <td style="text-align:center;"><?php echo $row['NivelActual']; ?>%</td>
                <td style="width: 300px;">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <div class="progress-bg" style="flex-grow:1;">
                            <div class="progress-fill" style="width: <?php echo $pct; ?>%"></div>
                        </div>
                        <span style="font-size:11px; font-weight:bold; width: 60px;">
                            <?php echo $hReal; ?> / <?php echo $hObj; ?>h
                        </span>
                    </div>
                </td>
                <td style="text-align:center;">
                    <?php if($isDone): ?>
                        <span class="badge bg-success">COMPLETO</span>
                    <?php else: ?>
                        <span class="badge bg-warning">EN CURSO</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

</body>
</html>