<?php
include 'conexion.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * Filtro de extracción numérica radical.
 * Extrae cualquier dígito de una cadena como '0,50%' o '1 persona'.
 */
function soloNumeros($dato) {
    if (!$dato) return 0;
    // Reemplazamos coma por punto para que floatval lo entienda
    $dato = str_replace(',', '.', $dato);
    // Extraemos solo números y puntos decimales
    preg_match('/[0-9]*\.?[0-9]+/', $dato, $matches);
    return isset($matches[0]) ? (float)$matches[0] : 0;
}

$puestos = [];
$debug_log = []; // Para saber qué está pasando

// 1. CARGAR MAESTRO
$sql1 = "SELECT Operacion, [Operarios Necesarios] FROM [dbo].[pol_MatrizDefinicion]";
$res1 = sqlsrv_query($conn, $sql1);

if ($res1 === false) {
    $sql1 = "SELECT Operacion, OperariosNecesarios FROM [dbo].[pol_MatrizDefinicion]";
    $res1 = sqlsrv_query($conn, $sql1);
}

if ($res1) {
    while ($row = sqlsrv_fetch_array($res1, SQLSRV_FETCH_ASSOC)) {
        $rawName = $row['Operacion'];
        $rawVal = isset($row['Operarios Necesarios']) ? $row['Operarios Necesarios'] : ($row['OperariosNecesarios'] ?? '0');

        $num = soloNumeros($rawVal);
        $nombreLimpio = strtoupper(trim($rawName));

        // Solo guardamos si es mayor a 0
        if ($num > 0) {
            $puestos[$nombreLimpio] = [
                'display_name' => $rawName,
                'necesario' => ceil($num),
                'expertos' => 0,
                'en_formacion' => 0
            ];
        }
        // Guardamos para diagnóstico
        $debug_log[] = ["nombre" => $rawName, "valor_original" => $rawVal, "procesado" => $num];
    }
}

// 2. CARGAR EXPERTOS
$sql2 = "SELECT Operacion, COUNT(*) as Total FROM [dbo].[pol_Relacion_Operarios_Puestos] WHERE Porcentaje >= 50 GROUP BY Operacion";
$res2 = sqlsrv_query($conn, $sql2);
if ($res2) {
    while ($row = sqlsrv_fetch_array($res2, SQLSRV_FETCH_ASSOC)) {
        $op = strtoupper(trim($row['Operacion']));
        if (isset($puestos[$op])) { $puestos[$op]['expertos'] = (int)$row['Total']; }
    }
}

// 3. CARGAR FORMACIÓN
$sql3 = "SELECT Operacion, COUNT(DISTINCT NombreOperario) as Total FROM [dbo].[pol_Formacion_Imputacion] GROUP BY Operacion";
$res3 = sqlsrv_query($conn, $sql3);
if ($res3) {
    while ($row = sqlsrv_fetch_array($res3, SQLSRV_FETCH_ASSOC)) {
        $op = strtoupper(trim($row['Operacion']));
        if (isset($puestos[$op])) { $puestos[$op]['en_formacion'] = (int)$row['Total']; }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <?php include 'header_meta.php'; ?>
    <title>KH - Informe de Necesidades</title>
    <style>
        .debug-box { margin-top: 30px; background: #333; color: #0f0; padding: 15px; font-family: monospace; font-size: 12px; border-radius: 5px; overflow-x: auto; }
        .bg-ok { background: #d4edda; color: #155724; }
        .bg-pending { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body style="padding: 20px 15px;">

<div class="header-kh">
    <div style="display:flex; align-items:center; gap:20px;">
        <a href="index.html" style="color:white; text-decoration:none; font-size:24px;">🏠</a>
        <h2 style="margin:0;">Necesidades de Producción (>0)</h2>
    </div>
    <img src="logo.png" style="height:40px; background: white; padding: 2px; border-radius: 4px;">
</div>

<div class="container" style="background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow: hidden; margin-top: 20px;">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th style="text-align:left;">Puesto</th>
                    <th>Necesario</th>
                    <th>Expertos</th>
                    <th>En Formación</th>
                    <th>Déficit</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php
                ksort($puestos);
                foreach ($puestos as $opKey => $info):
                    $def = $info['necesario'] - ($info['expertos'] + $info['en_formacion']);
                    if ($def < 0) $def = 0;
                ?>
                <tr>
                    <td style="text-align:left;"><strong><?php echo limpiar($info['display_name']); ?></strong></td>
                    <td style="text-align:center;"><?php echo $info['necesario']; ?></td>
                    <td style="text-align:center; color:green; font-weight:bold;"><?php echo $info['expertos']; ?></td>
                    <td style="text-align:center; color:blue;"><?php echo $info['en_formacion']; ?></td>
                    <td style="text-align:center; color:#8c181a; font-weight:bold;"><?php echo $def; ?></td>
                    <td style="text-align:center;">
                        <?php if($def <= 0): ?>
                            <span class="badge bg-ok">OK</span>
                        <?php else: ?>
                            <span class="badge bg-pending">PENDIENTE</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="container" style="padding: 0;">
    <div class="debug-box">
        <h3>Diagnóstico de Datos (Solo desarrollo)</h3>
        <p>Si la tabla está vacía, revisa estos registros:</p>
        <ul>
            <?php
            for($i=0; $i < min(10, count($debug_log)); $i++) {
                echo "<li>Puesto: [".$debug_log[$i]['nombre']."] | DB: [".$debug_log[$i]['valor_original']."] | Proc: ".$debug_log[$i]['procesado']."</li>";
            }
            ?>
        </ul>
    </div>
</div>

</body>
</html>
