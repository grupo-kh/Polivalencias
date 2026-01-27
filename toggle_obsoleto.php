<?php
include 'conexion.php';

if (isset($_GET['op']) && isset($_GET['status'])) {
    $operacion = $_GET['op'];
    // Invertimos el estado: si es 1 pasa a 0, si es 0 pasa a 1
    $nuevoEstado = ($_GET['status'] == 1) ? 0 : 1;

    $sql = "UPDATE [dbo].[pol_MatrizDefinicion] 
            SET Obsoleto = ? 
            WHERE Operacion = ?";
    
    $params = array($nuevoEstado, $operacion);
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }
}

// Volver al informe automáticamente
header("Location: informe_operaciones_activas.php");
exit;
?>