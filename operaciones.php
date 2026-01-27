<?php
include 'conexion.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>OPERACIONES - KH</title>
    <link rel="stylesheet" href="estilos.css">
</head>
<body>
<div class="container">
    <div class="form-header">
        <h1>MATRIZ DE DEFINICIÓN - OPERACIONES</h1>
        <img src="logo.png" alt="LOGO" style="height: 40px; background: white; padding: 2px;">
    </div>

    <div style="padding: 20px;">
        <table style="width: 100%;">
            <thead>
                <tr>
                    <th>SECCIÓN</th>
                    <th>OPERACIÓN / PUESTO</th>
                    <th>DIFICULTAD</th>
                    <th>OBSOLETO</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql = "SELECT [Seccion], [Operacion], [Dificultad], [Obsoleto] FROM [pol_MatrizDefinicion] ORDER BY [Seccion], [Operacion]";
                $res = sqlsrv_query($conn, $sql);
                
                if($res !== false) {
                    while($row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)) {
                        echo "<tr>
                                <td>{$row['Seccion']}</td>
                                <td><strong>{$row['Operacion']}</strong></td>
                                <td>{$row['Dificultad']}</td>
                                <td>" . ($row['Obsoleto'] == 'VERDADERO' ? '⚠️ SÍ' : 'NO') . "</td>
                              </tr>";
                    }
                }
                ?>
            </tbody>
        </table>
        <br>
        <a href="index.php" class="btn-nuevo" style="text-decoration:none;">VOLVER AL MENÚ</a>
    </div>
</div>
</body>
</html>