<?php
include 'conexion.php';
error_reporting(E_ALL & ~E_DEPRECATED);

function limpiar($t) { return ($t === null) ? "" : htmlspecialchars(trim($t), ENT_QUOTES, 'UTF-8'); }

/**
 * CONSULTA MAESTRA
 * Cruza planes de seguimiento e imputaciones reales.
 */
$sql = "SELECT DISTINCT
            COALESCE(LTRIM(RTRIM(s.Operario)), LTRIM(RTRIM(h.NombreOperario))) AS Operario,
            COALESCE(LTRIM(RTRIM(s.Puesto)), LTRIM(RTRIM(h.Operacion))) AS Puesto,
            ISNULL(r.HorasRequeridasFormacion, 0) AS HorasDefinidas,
            (SELECT SUM(Horas) FROM [dbo].[pol_Formacion_Imputacion] i 
             WHERE LTRIM(RTRIM(i.NombreOperario)) = COALESCE(LTRIM(RTRIM(s.Operario)), LTRIM(RTRIM(h.NombreOperario))) 
             AND LTRIM(RTRIM(i.Operacion)) = COALESCE(LTRIM(RTRIM(s.Puesto)), LTRIM(RTRIM(h.Operacion)))) AS HorasRealizadas
        FROM [dbo].[pol_FormacionSeguimiento] s
        FULL OUTER JOIN [dbo].[pol_Formacion_Imputacion] h 
            ON LTRIM(RTRIM(s.Operario)) = LTRIM(RTRIM(h.NombreOperario)) 
            AND LTRIM(RTRIM(s.Puesto)) = LTRIM(RTRIM(h.Operacion))
        LEFT JOIN [dbo].[pol_MatrizDefinicion] r 
            ON LTRIM(RTRIM(COALESCE(s.Puesto, h.Operacion))) = LTRIM(RTRIM(r.Operacion))
        WHERE ISNULL(r.HorasRequeridasFormacion, 0) > 0
        ORDER BY Operario ASC";

$res = sqlsrv_query($conn, $sql);

if ($res === false) {
    die("<pre>" . print_r(sqlsrv_errors(), true) . "</pre>");
}

$datos = [];
$total_h_pendientes = 0;
$total_puestos_abiertos = 0;

while ($row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)) {
    $op = $row['Operario'];
    $req = (float)$row['HorasDefinidas'];
    $real = (float)$row['HorasRealizadas'];
    $pend = $req - $real;

    if ($pend > 0) {
        $pct = ($req > 0) ? ($real / $req) * 100 : 0;
        $datos[$op][] = [
            'puesto' => $row['Puesto'], 
            'req' => $req, 
            'real' => $real, 
            'pend' => $pend, 
            'pct' => $pct
        ];
        $total_h_pendientes += $pend;
        $total_puestos_abiertos++;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Informe de Formaciones KH</title>
    <style>
        :root { --kh-red: #8c181a; --kh-gold: #b18e3a; }
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7f6; padding: 20px; color: #333; }
        .container { max-width: 950px; margin: auto; background: white; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); overflow: hidden; }
        .header { background: var(--kh-red); color: white; padding: 20px; display: flex; justify-content: space-between; align-items: center; }
        .op-header { background: #fdfdfd; padding: 12px 20px; font-weight: bold; color: var(--kh-red); border-left: 5px solid var(--kh-gold); border-bottom: 1px solid #eee; font-size: 1.1em; }
        table { width: 100%; border-collapse: collapse; }
        th { font-size: 11px; color: #777; text-transform: uppercase; padding: 12px; background: #fafafa; letter-spacing: 1px; }
        td { padding: 14px; text-align: center; border-bottom: 1px solid #f0f0f0; font-size: 14px; }
        .prog-bg { background: #eee; height: 12px; width: 120px; border-radius: 6px; display: inline-block; overflow: hidden; vertical-align: middle; }
        .prog-fill { background: var(--kh-gold); height: 100%; }
        
        /* Estilos del nuevo Pie de Página */
        .footer-stats { background: #2c3e50; color: white; padding: 25px; display: flex; justify-content: space-around; text-align: center; }
        .stat-item b { display: block; font-size: 1.8rem; color: var(--kh-gold); margin-bottom: 5px; }
        .stat-item span { font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px; color: #bdc3c7; }
        
        .btn { background: transparent; color: white; border: 1px solid white; text-decoration: none; padding: 8px 18px; border-radius: 4px; font-size: 13px; transition: 0.3s; }
        .btn:hover { background: white; color: var(--kh-red); }
        @media print { .no-print { display: none; } .container { box-shadow: none; border: 1px solid #eee; } }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h2 style="margin:0;">FORMACIONES EN CURSO</h2>
        <div class="no-print">
            <a href="index.php" class="btn">VOLVER AL MENÚ</a>
            <button onclick="window.print()" class="btn" style="margin-left:10px; cursor:pointer;">IMPRIMIR</button>
        </div>
    </div>

    <?php if (empty($datos)): ?>
        <div style="padding:100px 20px; text-align:center;">
            <h3 style="color:#999;">No hay formaciones pendientes</h3>
            <p style="color:#bbb;">Todos los operarios han completado sus horas requeridas.</p>
        </div>
    <?php else: ?>
        <?php foreach ($datos as $operario => $puestos): ?>
            <div class="op-header">👤 <?=limpiar($operario)?></div>
            <table>
                <thead>
                    <tr>
                        <th align="left" style="padding-left:20px;">Puesto / Operación</th>
                        <th>Objetivo</th>
                        <th>Realizado</th>
                        <th>Faltan</th>
                        <th>Progreso</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($puestos as $p): ?>
                    <tr>
                        <td align="left" style="padding-left:20px;"><b><?=limpiar($p['puesto'])?></b></td>
                        <td><?=number_format($p['req'], 1)?> h</td>
                        <td style="color:#27ae60; font-weight:bold;"><?=number_format($p['real'], 1)?> h</td>
                        <td style="color:var(--kh-red); font-weight:bold;"><?=number_format($p['pend'], 1)?> h</td>
                        <td>
                            <div class="prog-bg"><div class="prog-fill" style="width:<?=$p['pct']?>%"></div></div>
                            <span style="font-size:12px; margin-left:8px; font-weight:bold;"><?=round($p['pct'])?>%</span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endforeach; ?>

        <div class="footer-stats">
            <div class="stat-item">
                <b><?=count($datos)?></b>
                <span>Operarios con formación</span>
            </div>
            <div class="stat-item">
                <b><?=$total_puestos_abiertos?></b>
                <span>Puestos abiertos</span>
            </div>
            <div class="stat-item">
                <b><?=number_format($total_h_pendientes, 1)?></b>
                <span>Total horas pendientes</span>
            </div>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
