<?php
include 'conexion.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <?php include 'header_meta.php'; ?>
    <title>OPERACIONES - KH</title>
</head>
<body>
<div class="header-kh">
    <div style="display:flex; align-items:center; gap:20px;">
        <a href="index.html" class="btn btn-secondary">🏠</a>
        <h2 style="margin:0;">CATÁLOGO DE OPERACIONES</h2>
    </div>
    <img src="logo.png" style="height:40px; background: white; padding: 2px; border-radius: 4px;">
</div>

<div class="container" style="padding: 20px 15px;">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>SECCIÓN</th>
                    <th>OPERACIÓN / PUESTO</th>
                    <th style="text-align:center;">DIFICULTAD</th>
                    <th style="text-align:center;">OBSOLETO</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql = "SELECT [Seccion], [Operacion], [Dificultad], [Obsoleto] FROM [pol_MatrizDefinicion] ORDER BY [Seccion], [Operacion]";
                $res = sqlsrv_query($conn, $sql);

                if($res !== false) {
                    while($row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)) {
                        echo "<tr>
                                <td>" . limpiar($row['Seccion']) . "</td>
                                <td><strong>" . limpiar($row['Operacion']) . "</strong></td>
                                <td style='text-align:center;'>" . $row['Dificultad'] . "</td>
                                <td style='text-align:center;'>" . ($row['Obsoleto'] == 'VERDADERO' || $row['Obsoleto'] == 1 ? '⚠️ SÍ' : 'NO') . "</td>
                              </tr>";
                    }
                }
                ?>
            </tbody>
        </table>
    </div>
    <div style="margin-top: 20px;">
        <a href="index.html" class="btn btn-secondary">VOLVER AL MENÚ</a>
    </div>
</div>
</body>
</html>
