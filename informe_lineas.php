<head>
    <meta charset="UTF-8">
    <?php include 'header_meta.php'; ?>
    <title>Nombre de la página</title>
    <style> ... </style>
</head>

<?php
include 'conexion.php';
error_reporting(E_ALL & ~E_DEPRECATED);

function limpiar($t) { return ($t === null) ? "" : htmlspecialchars(trim($t), ENT_QUOTES, 'UTF-8'); }

// Capturamos el filtro si existe
$filtroPuesto = $_GET['f_puesto'] ?? '';

// 1. Obtener la lista de puestos para el SELECT del buscador
$sqlCombo = "SELECT Operacion FROM [dbo].[pol_MatrizDefinicion] ORDER BY Operacion";
$resCombo = sqlsrv_query($conn, $sqlCombo);

// 2. Obtener las polivalencias/operaciones según el filtro
$params = [];
$sqlPuestos = "SELECT Seccion, Operacion FROM [dbo].[pol_MatrizDefinicion]";
if ($filtroPuesto != '') {
    $sqlPuestos .= " WHERE Operacion = ?";
    $params = array($filtroPuesto);
}
$sqlPuestos .= " ORDER BY Seccion, Operacion";
$resPuestos = sqlsrv_query($conn, $sqlPuestos, $params);

$puestos = [];
while ($row = sqlsrv_fetch_array($resPuestos, SQLSRV_FETCH_ASSOC)) {
    $puestos[] = $row;
}

// 3. Obtener la relación de operarios
$relaciones = [];
$sqlRel = "SELECT NombreOperario, Operacion, Porcentaje FROM [dbo].[pol_Relacion_Operarios_Puestos] ORDER BY NombreOperario ASC";
$resRel = sqlsrv_query($conn, $sqlRel);
while ($row = sqlsrv_fetch_array($resRel, SQLSRV_FETCH_ASSOC)) {
    $relaciones[$row['Operacion']][] = [
        'nombre' => $row['NombreOperario'],
        'pct' => (int)$row['Porcentaje']
    ];
}

function obtenerEtiquetaNivel($pct) {
    if ($pct >= 100) return "Formador";
    if ($pct >= 75)  return "Formado 100%";
    if ($pct >= 50)  return "Formado para trabajar";
    if ($pct >= 25)  return "En fase formación inicial";
    return "Sin capacitación";
}

function obtenerColorNivel($pct) {
    if ($pct >= 100) return "#1a237e"; 
    if ($pct >= 75)  return "#28a745"; 
    if ($pct >= 50)  return "#ffc107"; 
    if ($pct >= 25)  return "#e65100"; 
    return "#6e6d6b"; 
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>KH - Informe de Polivalencias</title>
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f4f4; margin: 0; padding: 20px; color: #333; }
        .no-print { background: #8c181a; color: white; padding: 15px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        
        /* Estilo del buscador */
        .search-container { background: white; padding: 15px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: flex-end; gap: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .search-container label { display: block; font-weight: bold; margin-bottom: 5px; color: #8c181a; font-size: 13px; }
        
        .grid-informe { display: grid; grid-template-columns: repeat(auto-fill, minmax(380px, 1fr)); gap: 20px; }
        
        .card-puesto { background: white; border: 1px solid #ddd; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.05); page-break-inside: avoid; }
        .card-header { background: #8c181a; color: white; padding: 10px 15px; }
        .card-header h3 { margin: 0; font-size: 16px; text-transform: uppercase; }
        .card-header span { font-size: 11px; opacity: 0.8; font-weight: bold; }
        
        .item-op { display: flex; justify-content: space-between; align-items: center; padding: 10px 15px; border-bottom: 1px solid #eee; font-size: 13px; }
        .pct-badge { padding: 4px 8px; border-radius: 4px; color: white; font-weight: bold; font-size: 11px; min-width: 40px; text-align: center; }
        .label-nivel { font-size: 11px; color: #666; font-style: italic; }

        .btn-reset { background: #6e6d6b; color: white; text-decoration: none; padding: 10px 15px; border-radius: 4px; font-size: 13px; font-weight: bold; }

        @media print { .no-print, .search-container { display: none !important; } body { background: white; padding: 0; } }
    </style>
</head>
<body>

<div class="no-print">
    <div style="display:flex; align-items:center; gap:15px;">
        <a href="index.php" style="color:white; text-decoration:none; font-size:20px;">🏠</a>
        <h2 style="margin:0;">Informe de Polivalencias</h2>
    </div>
    <button onclick="window.print()" style="padding:8px 15px; cursor:pointer; font-weight:bold; border-radius:4px; border:none;">🖨️ Imprimir PDF</button>
</div>

<div class="search-container no-print">
    <div style="flex-grow: 1;">
        <label>Seleccionar Polivalencia:</label>
        <select id="sel-puesto" placeholder="Escribe para buscar polivalencia...">
            <option value="">-- Ver todas las polivalencias --</option>
            <?php while($rowC = sqlsrv_fetch_array($resCombo, SQLSRV_FETCH_ASSOC)): 
                $opName = trim($rowC['Operacion']);
            ?>
                <option value="<?php echo htmlspecialchars($opName); ?>" <?php echo ($filtroPuesto == $opName ? 'selected' : ''); ?>>
                    <?php echo $opName; ?>
                </option>
            <?php endwhile; ?>
        </select>
    </div>
    <a href="informe_lineas.php" class="btn-reset">Limpiar Filtros</a>
</div>

<div class="grid-informe">
    <?php if (empty($puestos)): ?>
        <div style="grid-column: 1/-1; text-align: center; padding: 50px; background: white; border-radius: 8px; color: #999;">
            No se han encontrado polivalencias con los criterios seleccionados.
        </div>
    <?php endif; ?>

    <?php foreach ($puestos as $puesto): 
        $nombrePuesto = $puesto['Operacion'];
        $seccion = $puesto['Seccion'] ?: 'Sin Sección';
        $lista = $relaciones[$nombrePuesto] ?? [];
    ?>
    <div class="card-puesto">
        <div class="card-header">
            <span>SECCIÓN: <?php echo limpiar($seccion); ?></span>
            <h3><?php echo limpiar($nombrePuesto); ?></h3>
        </div>
        <div class="lista-operarios">
            <?php if (empty($lista)): ?>
                <div style="padding:20px; font-size:12px; color:#999; text-align:center;">No hay operarios capacitados en este puesto</div>
            <?php else: ?>
                <?php foreach ($lista as $op): ?>
                <div class="item-op">
                    <div>
                        <strong><?php echo limpiar($op['nombre']); ?></strong><br>
                        <span class="label-nivel"><?php echo obtenerEtiquetaNivel($op['pct']); ?></span>
                    </div>
                    <div class="pct-badge" style="background: <?php echo obtenerColorNivel($op['pct']); ?>">
                        <?php echo $op['pct']; ?>%
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<script>
    var sPu = new TomSelect("#sel-puesto", {
        create: false,
        sortField: { field: "text", direction: "asc" },
        onChange: function(val) {
            window.location.href = "informe_lineas.php?f_puesto=" + encodeURIComponent(val);
        }
    });
</script>

</body>
</html>