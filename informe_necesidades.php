<?php
include 'conexion.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

function limpiar($t) { return ($t === null) ? "" : htmlspecialchars(trim($t), ENT_QUOTES, 'UTF-8'); }

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
    <title>KH - Informe de Necesidades</title>
    <style>
        body { font-family: sans-serif; background: #f4f4f4; padding: 20px; }
        .container { max-width: 1000px; margin: auto; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow: hidden; }
        .header { background: #8c181a; color: white; padding: 15px; display: flex; justify-content: space-between; align-items: center; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #eee; padding: 12px; font-size: 12px; border-bottom: 2px solid #ccc; }
        td { padding: 10px; border-bottom: 1px solid #eee; text-align: center; }
        .badge { padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 11px; }
        .bg-success { background: #d4edda; color: #155724; }
        .bg-danger { background: #f8d7da; color: #721c24; }
        .debug-box { margin-top: 30px; background: #333; color: #0f0; padding: 15px; font-family: monospace; font-size: 12px; border-radius: 5px; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h2 style="margin:0;">Necesidades de Producción (>0)</h2>
        <a href="index.php" style="color:white; text-decoration:none; border:1px solid white; padding:5px 10px; border-radius:4px;">Volver</a>
    </div>

    <table>
        <thead>
            <tr>
                <th style="text-align:left; padding-left:20px;">Puesto</th>
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
                <td style="text-align:left; padding-left:20px;"><strong><?php echo limpiar($info['display_name']); ?></strong></td>
                <td><?php echo $info['necesario']; ?></td>
                <td style="color:green; font-weight:bold;"><?php echo $info['expertos']; ?></td>
                <td style="color:blue;"><?php echo $info['en_formacion']; ?></td>
                <td style="color:#8c181a; font-weight:bold;"><?php echo $def; ?></td>
                <td>
                    <?php if($def <= 0): ?>
                        <span class="badge bg-success">OK</span>
                    <?php else: ?>
                        <span class="badge bg-danger">PENDIENTE</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="debug-box">
    <h3>Diagnóstico de Datos (Solo visible para desarrollo)</h3>
    <p>Si la tabla de arriba está vacía, mira aquí abajo. Estos son los primeros 10 registros que lee la base de datos:</p>
    <ul>
        <?php 
        for($i=0; $i < min(10, count($debug_log)); $i++) {
            echo "<li>Puesto: [".$debug_log[$i]['nombre']."] | Valor DB: [".$debug_log[$i]['valor_original']."] | Interpretado como: ".$debug_log[$i]['procesado']."</li>";
        }
        ?>
    </ul>
</div>

</body>
</html>