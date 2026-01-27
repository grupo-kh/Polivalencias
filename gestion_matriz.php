<?php
include 'conexion.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

function limpiar($texto) {
    if ($texto === null) return "";
    return htmlspecialchars(trim($texto), ENT_QUOTES, 'UTF-8');
}

// --- LÓGICA DE GUARDADO ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['btnGuardarMatriz'])) {
    $seccion = trim($_POST['seccion']);
    $operacion = trim($_POST['operacion']);
    $op_necesarios = intval($_POST['op_necesarios']);
    $horas = intval($_POST['horas']);
    $obsoleto = isset($_POST['obsoleto']) ? 1 : 0;
    $necesidad = intval($_POST['necesidad']);
    $dificultad = intval($_POST['dificultad']);

    $sqlCheck = "SELECT Operacion FROM [dbo].[pol_MatrizDefinicion] WHERE Operacion = ?";
    $resCheck = sqlsrv_query($conn, $sqlCheck, array($operacion));

    if (sqlsrv_has_rows($resCheck)) {
        // ACTUALIZAR - Usando nombres reales de tu BD
        $sql = "UPDATE [dbo].[pol_MatrizDefinicion] 
                SET Seccion=?, OperariosNecesarios=?, HorasRequeridasFormacion=?, Obsoleto=?, Necesidad=?, Dificultad=? 
                WHERE Operacion=?";
        $params = array($seccion, $op_necesarios, $horas, $obsoleto, $necesidad, $dificultad, $operacion);
    } else {
        // INSERTAR - Usando nombres reales de tu BD
        $sql = "INSERT INTO [dbo].[pol_MatrizDefinicion] 
                (Seccion, Operacion, OperariosNecesarios, HorasRequeridasFormacion, Obsoleto, Necesidad, Dificultad) 
                VALUES (?,?,?,?,?,?,?)";
        $params = array($seccion, $operacion, $op_necesarios, $horas, $obsoleto, $necesidad, $dificultad);
    }

    $stmt = sqlsrv_query($conn, $sql, $params);
    
    if ($stmt === false) {
        die("<pre style='color:red;'>Error crítico en SQL: " . print_r(sqlsrv_errors(), true) . "</pre>");
    } else {
        header("Location: gestion_matriz.php?msg=ok");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>KH - Matriz de Operaciones</title>
    <style>
        body { font-family: sans-serif; padding: 0; margin: 0; background: #fff; color: #6e6d6b; }
        .header-kh { background: #8c181a; color: white; padding: 15px 40px; display: flex; justify-content: space-between; align-items: center; }
        .btn-menu { background: white; color: #8c181a; padding: 8px 15px; text-decoration: none; font-weight: bold; border-radius: 4px; }
        .content { padding: 30px 40px; }
        
        .row { display: flex; align-items: center; margin-bottom: 12px; }
        .label-red { color: #8c181a; width: 160px; font-size: 14px; font-weight: bold; }
        
        /* Inputs con color #6e6d6b */
        .input-box { border: 1px solid #8c181a; padding: 5px 10px; color: #6e6d6b; font-size: 16px; font-weight: bold; outline: none; background: #fff; }
        .input-full { width: 500px; }
        .input-short { width: 80px; }

        .selectors-wrapper { display: flex; gap: 50px; margin-left: 160px; margin-top: 20px; }
        .selector-column { width: 120px; }
        .selector-title { background: #8c181a; color: white; padding: 4px; font-weight: bold; text-align: center; margin-bottom: 5px; }
        .selector-list { border: 1px solid #8c181a; height: 220px; overflow-y: auto; }
        .item { padding: 5px; color: #6e6d6b; cursor: pointer; text-align: center; font-weight: bold; border-bottom: 1px solid #eee; }
        .item.selected { background: #000 !important; color: #fff !important; }

        .btn-save { background: #8c181a; color: white; border: none; padding: 12px 30px; cursor: pointer; font-weight: bold; border-radius: 4px; margin-top: 20px; font-size: 14px; }
        .btn-new { background: #6e6d6b; color: white; border: none; padding: 12px 30px; cursor: pointer; font-weight: bold; border-radius: 4px; margin-left: 10px; font-size: 14px; }

        table { width: 100%; border-collapse: collapse; margin-top: 40px; color: #6e6d6b; font-size: 14px; }
        th { background: #f8f8f8; color: #8c181a; padding: 12px; text-align: left; border-bottom: 2px solid #8c181a; }
        td { padding: 10px; border-bottom: 1px solid #eee; }
        .btn-edit { background: #6e6d6b; color: white; border: none; padding: 5px 12px; cursor: pointer; border-radius: 3px; font-size: 12px; }
    </style>
</head>
<body>

<div class="header-kh">
    <div style="display:flex; align-items:center; gap:20px;">
        <a href="index.php" class="btn-menu">Ir al Menú</a>
        <h2 style="margin:0;">DEFINICIÓN DE OPERACIÓN</h2>
    </div>
    <div style="background:white; padding:5px; border-radius:4px;"><img src="logo.png" style="height:35px;"></div>
</div>

<div class="content">
    <?php if(isset($_GET['msg'])) echo "<p style='color:green; font-weight:bold; margin-bottom:20px;'>✔ Datos guardados correctamente en la base de datos.</p>"; ?>

    <form method="POST">
        <div class="row">
            <span class="label-red">Sección:</span>
            <select name="seccion" id="seccion" class="input-box" style="width: 422px;">
                <?php
                $resSec = sqlsrv_query($conn, "SELECT DISTINCT Seccion FROM [dbo].[pol_MatrizDefinicion] WHERE Seccion IS NOT NULL ORDER BY Seccion");
                while($s = @sqlsrv_fetch_array($resSec, SQLSRV_FETCH_ASSOC)) {
                    echo "<option value='".htmlspecialchars($s['Seccion'])."'>".limpiar($s['Seccion'])."</option>";
                }
                ?>
            </select>
            <button type="button" onclick="nuevaSeccion()" style="margin-left:10px; padding:5px; cursor:pointer;">+ Nueva</button>
        </div>

        <div class="row">
            <span class="label-red">Operación:</span>
            <input type="text" name="operacion" id="operacion" class="input-box input-full" required>
        </div>

        <div class="row">
            <span class="label-red">OP.Necesarios:</span>
            <input type="number" name="op_necesarios" id="op_necesarios" class="input-box input-short" required>
        </div>

        <div class="row">
            <span class="label-red">Horas Neces.:</span>
            <input type="number" name="horas" id="horas" class="input-box input-short" required>
        </div>

        <div class="row">
            <span class="label-red">Obsoleto:</span>
            <input type="checkbox" name="obsoleto" id="obsoleto" style="width:18px; height:18px;">
        </div>

        <div class="selectors-wrapper">
            <div class="selector-column">
                <div class="selector-title">Necesidad:</div>
                <div class="selector-list" id="list-necesidad">
                    <?php for($i=0; $i<=10; $i++) echo "<div class='item' data-val='$i'>$i</div>"; ?>
                </div>
                <input type="hidden" name="necesidad" id="val-necesidad" value="0">
            </div>
            <div class="selector-column">
                <div class="selector-title">Dificultad:</div>
                <div class="selector-list" id="list-dificultad">
                    <?php for($i=0; $i<=10; $i++) echo "<div class='item' data-val='$i'>$i</div>"; ?>
                </div>
                <input type="hidden" name="dificultad" id="val-dificultad" value="0">
            </div>
        </div>

        <button type="submit" name="btnGuardarMatriz" class="btn-save">GUARDAR DATOS</button>
        <button type="button" class="btn-new" onclick="location.href='gestion_matriz.php'">+ NUEVA OPERACIÓN</button>
    </form>

    <hr style="margin-top:40px; border:0; border-top: 1px solid #eee;">

    <h3 style="color: #8c181a;">Operaciones registradas</h3>
    <table>
        <thead>
            <tr>
                <th>Sección</th>
                <th>Operación</th>
                <th style="text-align:center;">Necesidad</th>
                <th style="text-align:center;">Dificultad</th>
                <th style="text-align:center;">H. Formación</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $res = sqlsrv_query($conn, "SELECT * FROM [dbo].[pol_MatrizDefinicion] ORDER BY Seccion, Operacion");
            while($row = @sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)){
                echo "<tr>
                    <td>".limpiar($row['Seccion'])."</td>
                    <td><strong>".limpiar($row['Operacion'])."</strong></td>
                    <td style='text-align:center;'>{$row['Necesidad']}</td>
                    <td style='text-align:center;'>{$row['Dificultad']}</td>
                    <td style='text-align:center;'>{$row['HorasRequeridasFormacion']}</td>
                    <td><button class='btn-edit' onclick='cargarEdicion(".json_encode($row).")'>Editar</button></td>
                </tr>";
            }
            ?>
        </tbody>
    </table>
</div>

<script>
    // Gestión de selectores visuales (fondo negro al marcar)
    function setupSelector(idList, idInput) {
        const list = document.getElementById(idList);
        list.addEventListener('click', function(e) {
            if(e.target.classList.contains('item')) {
                list.querySelectorAll('.item').forEach(i => i.classList.remove('selected'));
                e.target.classList.add('selected');
                document.getElementById(idInput).value = e.target.dataset.val;
            }
        });
    }
    setupSelector('list-necesidad', 'val-necesidad');
    setupSelector('list-dificultad', 'val-dificultad');

    function nuevaSeccion() {
        let n = prompt("Nombre de la nueva sección:");
        if(n) {
            let s = document.getElementById('seccion');
            let o = document.createElement("option");
            o.text = n.toUpperCase(); o.value = n;
            s.add(o); s.value = n;
        }
    }

    function cargarEdicion(d) {
        document.getElementById('seccion').value = d.Seccion;
        document.getElementById('operacion').value = d.Operacion;
        document.getElementById('op_necesarios').value = d.OperariosNecesarios;
        document.getElementById('horas').value = d.HorasRequeridasFormacion;
        document.getElementById('obsoleto').checked = (d.Obsoleto == 1);
        
        // Marcar selectores
        document.querySelectorAll('.item').forEach(i => i.classList.remove('selected'));
        
        let n = document.querySelector(`#list-necesidad [data-val='${d.Necesidad}']`);
        if(n) n.classList.add('selected');
        document.getElementById('val-necesidad').value = d.Necesidad;

        let dif = document.querySelector(`#list-dificultad [data-val='${d.Dificultad}']`);
        if(dif) dif.classList.add('selected');
        document.getElementById('val-dificultad').value = d.Dificultad;
        
        window.scrollTo(0,0);
    }
</script>
</body>
</html>