<?php
include 'conexion.php';
error_reporting(E_ALL & ~E_DEPRECATED);

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
    <?php include 'header_meta.php'; ?>
    <title>KH - Seguimiento de Horas</title>
    <style>
        .progress-bg { background: #e9ecef; border-radius: 10px; width: 100%; height: 12px; overflow: hidden; border: 1px solid #ddd; }
        .progress-fill { background: #8c181a; height: 100%; transition: width 0.4s ease; }
        @media print { .no-print { display: none !important; } body { padding: 0; } }
    </style>
</head>
<body style="padding: 20px 15px;">

<div class="header-kh no-print">
    <div style="display:flex; align-items:center; gap:15px;">
        <a href="index.html" style="color:white; text-decoration:none; font-size:24px;">🏠</a>
        <h2 style="margin:0; font-size: 1.2rem;">SEGUIMIENTO DE HORAS DE FORMACIÓN</h2>
    </div>
    <button onclick="window.print()" class="btn btn-accent">🖨️ IMPRIMIR</button>
</div>

<div class="container" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-top: 20px;">
    <div class="table-responsive">
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
                    <td style="min-width: 250px;">
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
                            <span class="badge alto">COMPLETO</span>
                        <?php else: ?>
                            <span class="badge medio">EN CURSO</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
