<?php
include 'conexion.php';
error_reporting(E_ALL & ~E_DEPRECATED);

function limpiar($texto) {
    if ($texto === null) return "";
    $codificacion = mb_detect_encoding($texto, 'UTF-8, ISO-8859-1', true);
    if ($codificacion === 'ISO-8859-1') {
        $texto = mb_convert_encoding($texto, 'UTF-8', 'ISO-8859-1');
    }
    return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
}

$operarioSeleccionado = isset($_GET['operario']) ? $_GET['operario'] : '';

// --- LÓGICA: ELIMINAR ---
if (isset($_GET['eliminar_id'])) {
    sqlsrv_query($conn, "DELETE FROM [dbo].[pol_Relacion_Operarios_Puestos] WHERE Id_Polivalencia=?", array($_GET['eliminar_id']));
    header("Location: polivalencias.php?operario=".urlencode($operarioSeleccionado)); exit;
}

// --- LÓGICA: GUARDAR NUEVO ---
if (isset($_POST['btnGuardar'])) {
    $sqlM = sqlsrv_query($conn, "SELECT MAX(Id_Polivalencia) as m FROM [dbo].[pol_Relacion_Operarios_Puestos]");
    $id = (sqlsrv_fetch_array($sqlM, SQLSRV_FETCH_ASSOC)['m'] ?? 0) + 1;
    sqlsrv_query($conn, "INSERT INTO [dbo].[pol_Relacion_Operarios_Puestos] (Id_Polivalencia,NombreOperario,Operacion,Porcentaje,Baja) VALUES (?,?,?,?,'NO')", array($id,$_POST['nombre_op'],$_POST['nuevo_puesto'],$_POST['nuevo_pct']));
    header("Location: polivalencias.php?operario=".urlencode($_POST['nombre_op'])); exit;
}

// --- LÓGICA: ACTUALIZAR EXISTENTE ---
if (isset($_POST['btnActualizar'])) {
    sqlsrv_query($conn, "UPDATE [dbo].[pol_Relacion_Operarios_Puestos] SET Porcentaje=? WHERE Id_Polivalencia=?", array($_POST['edit_pct'], $_POST['edit_id']));
    header("Location: polivalencias.php?operario=".urlencode($operarioSeleccionado)); exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <?php include 'header_meta.php'; ?>
    <title>KH - Polivalencias</title>
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    <style>
        .ts-control { border: 2px solid #8c181a !important; padding: 10px !important; }
        .btn-edit { color: #6e6d6b; text-decoration: none; font-size: 11px; margin-right: 10px; cursor: pointer; }
        .btn-edit:hover { color: #8c181a; }
        .form-asignar { display:flex; gap:10px; align-items:flex-end; flex-wrap: wrap; }
        @media (max-width: 600px) {
            .form-asignar { flex-direction: column; align-items: stretch; }
        }
    </style>
</head>
<body>
<div class="header-kh">
    <div style="display:flex; align-items:center; gap:15px;">
        <a href="gestion_operarios.php" style="color:white; text-decoration:none; font-size:20px;">🏠</a>
        <h1 style="color: white; margin: 0;">POLIVALENCIAS</h1>
    </div>
    <img src="logo.png" style="height:40px; background: white; padding: 2px; border-radius: 4px;">
</div>

<div class="container" style="background: white; min-height: 100vh; padding: 20px 15px;">
    <div>
        <label>Seleccionar Operario:</label>
        <div style="margin-top: 10px;">
            <select id="buscador-operarios">
                <option value="">-- Buscar... --</option>
                <?php
                $res = sqlsrv_query($conn, "SELECT NombreOperario FROM [dbo].[pol_Operarios] WHERE FechaBaja IS NULL OR FechaBaja='' ORDER BY NombreOperario");
                while($o = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)){
                    $n = trim($o['NombreOperario']);
                    $s = ($operarioSeleccionado == $n) ? 'selected' : '';
                    echo "<option value='".htmlspecialchars($n)."' $s>".limpiar($n)."</option>";
                }
                ?>
            </select>
        </div>

        <?php if($operarioSeleccionado): ?>
            <div style="background:#8c181a; color:white; padding:15px; text-align:center; font-size:22px; font-weight:bold; margin:20px 0; border-radius:4px;"><?=strtoupper(limpiar($operarioSeleccionado))?></div>

            <div style="background:#f9f9f9; border:2px dashed #8c181a; padding:15px; border-radius:8px;">
                <h3 style="margin-top:0; color: #8c181a; font-size: 14px;">ASIGNAR NUEVO PUESTO</h3>
                <form method="POST" class="form-asignar">
                    <input type="hidden" name="nombre_op" value="<?=htmlspecialchars($operarioSeleccionado)?>">
                    <div style="flex:3;">
                        <label>Puesto:</label>
                        <select name="nuevo_puesto" id="select-puesto" required>
                            <?php $rm = sqlsrv_query($conn,"SELECT Operacion FROM [dbo].[pol_MatrizDefinicion] ORDER BY Operacion");
                            while($m=sqlsrv_fetch_array($rm, SQLSRV_FETCH_ASSOC)) echo "<option value='".htmlspecialchars($m['Operacion'])."'>".limpiar($m['Operacion'])."</option>"; ?>
                        </select>
                    </div>
                    <div style="flex:1;">
                        <label>%:</label>
                        <input type="number" name="nuevo_pct" value="75" min="0" max="100" style="padding:11px; border: 2px solid #8c181a; border-radius:4px;">
                    </div>
                    <button type="submit" name="btnGuardar" class="btn btn-primary" style="margin-bottom: 2px;">AÑADIR</button>
                </form>
            </div>

            <h2 style="color:#6e6d6b; border-bottom:3px solid #6e6d6b; margin-top:30px; font-size: 18px;">HISTÓRICO DE OPERACIONES</h2>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr style="background: #eee;">
                            <th style="text-align:left;">OPERACIÓN</th>
                            <th style="text-align:center;">NIVEL (%)</th>
                            <th style="text-align:center;">ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $rp = sqlsrv_query($conn, "SELECT * FROM [dbo].[pol_Relacion_Operarios_Puestos] WHERE NombreOperario=? ORDER BY Operacion", array($operarioSeleccionado));
                    while($row=sqlsrv_fetch_array($rp, SQLSRV_FETCH_ASSOC)){
                        $p = (int)$row['Porcentaje'];
                        $c = ($p >= 75) ? 'alto' : (($p >= 25) ? 'medio' : 'bajo');
                        $id_fila = $row['Id_Polivalencia'];
                    ?>
                        <tr id="fila-<?=$id_fila?>">
                            <td><?=limpiar($row['Operacion'])?></td>
                            <td style="text-align:center;">
                                <span class="badge <?=$c?>"><?=$p?>%</span>
                            </td>
                            <td style="text-align:center; min-width: 120px;">
                                <a class="btn-edit" onclick="activarEdicion('<?=$id_fila?>', '<?=$p?>')">Editar</a>
                                <a href="?operario=<?=urlencode($operarioSeleccionado)?>&eliminar_id=<?=$id_fila?>" style="color:#dc3545; font-size:11px; text-decoration:none;" onclick="return confirm('¿Borrar?')">Eliminar</a>
                            </td>
                        </tr>
                        <tr id="edit-<?=$id_fila?>" style="display:none; background: #fffde7;">
                            <form method="POST">
                                <input type="hidden" name="edit_id" value="<?=$id_fila?>">
                                <td><?=limpiar($row['Operacion'])?></td>
                                <td style="text-align:center;">
                                    <input type="number" name="edit_pct" value="<?=$p?>" min="0" max="100" style="width:70px; padding: 5px;"> %
                                </td>
                                <td style="text-align:center;">
                                    <button type="submit" name="btnActualizar" class="btn btn-primary" style="padding: 5px 10px; font-size: 11px;">OK</button>
                                    <button type="button" onclick="cancelarEdicion('<?=$id_fila?>')" class="btn btn-secondary" style="padding: 5px 10px; font-size: 11px;">X</button>
                                </td>
                            </form>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    // Inicializar TomSelects
    new TomSelect("#buscador-operarios", { onChange: function(v){ if(v) window.location.href="?operario="+encodeURIComponent(v); } });
    if(document.getElementById('select-puesto')) new TomSelect("#select-puesto");

    // Funciones para la edición en línea
    function activarEdicion(id, valor) {
        document.getElementById('fila-' + id).style.display = 'none';
        document.getElementById('edit-' + id).style.display = 'table-row';
    }

    function cancelarEdicion(id) {
        document.getElementById('fila-' + id).style.display = 'table-row';
        document.getElementById('edit-' + id).style.display = 'none';
    }
</script>
</body>
</html>
