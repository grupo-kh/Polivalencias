<?php
include 'conexion.php';
error_reporting(E_ALL & ~E_DEPRECATED);

// 1. Tablas y Columnas (Asegúrate de que coincidan con tu SQL Server)
$t_ref = "[dbo].[pol_MatrizDefinicion]";
$c_seccion = "[Seccion]";
$c_operacion = "[Operacion]";
$c_obsoleto = "[Obsoleto]";

$sql = "SELECT $c_seccion AS Seccion, $c_operacion AS Operacion, ISNULL($c_obsoleto, 0) AS EsObsoleto
        FROM $t_ref
        ORDER BY $c_seccion, $c_operacion";

$res = sqlsrv_query($conn, $sql);
$datos = [];
if ($res) {
    while ($row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)) {
        $sec = $row['Seccion'] ?: "SIN SECCIÓN";
        $datos[$sec][] = [
            'nombre' => $row['Operacion'],
            'obsoleto' => $row['EsObsoleto']
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <?php include 'header_meta.php'; ?>
    <title>Catálogo de Operaciones - KH</title>
    <style>
        .seccion-title {
            background: #eee; padding: 12px 20px; font-weight: bold;
            border-left: 5px solid #b18e3a; margin-top: 10px;
            text-transform: uppercase; font-size: 0.9rem; color: #333;
        }
        .btn-status {
            padding: 4px 10px; border-radius: 4px; text-decoration: none;
            font-size: 11px; font-weight: bold; float: right; transition: 0.2s;
        }
        .status-active { background: #e6f4ea; color: #1e7e34; border: 1px solid #1e7e34; }
        .status-obsolete { background: #fce8e6; color: #c5221f; border: 1px solid #c5221f; }
        .texto-obsoleto { color: #aaa; text-decoration: line-through; }
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; background: white; }
            .container { box-shadow: none; max-width: 100%; border: none; }
            .seccion-title { background: #f0f0f0 !important; -webkit-print-color-adjust: exact; }
            .texto-obsoleto::after {
                content: " (OBSOLETO)";
                font-size: 10px;
                font-weight: bold;
                text-decoration: none !important;
                display: inline-block;
            }
        }
    </style>
</head>
<body style="padding: 20px 15px;">

<div class="header-kh no-print">
    <div style="display:flex; align-items:center; gap:15px;">
        <a href="index.html" style="color:white; text-decoration:none; font-size:24px;">🏠</a>
        <h2 style="margin:0;">Catálogo de Operaciones</h2>
    </div>
    <div style="display: flex; gap: 10px;">
        <button onclick="window.print();" class="btn btn-accent">📄 Imprimir PDF</button>
    </div>
</div>

<div class="container" style="background: white; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); overflow: hidden; margin-top: 20px;">
    <?php if (empty($datos)): ?>
        <p style="padding:40px; text-align:center;">No hay datos disponibles.</p>
    <?php else: ?>
        <?php foreach ($datos as $seccion => $ops): ?>
            <div class="seccion-title">📂 <?php echo limpiar($seccion); ?></div>
            <div class="table-responsive">
                <table>
                    <?php foreach ($ops as $op):
                        $esObsoleto = ($op['obsoleto'] == 1);
                        $claseTexto = $esObsoleto ? 'texto-obsoleto' : '';
                        $labelBtn = $esObsoleto ? 'OBSOLETO' : 'ACTIVO';
                        $claseBtn = $esObsoleto ? 'status-obsolete' : 'status-active';
                    ?>
                    <tr>
                        <td style="padding: 12px 25px;">
                            <span class="<?php echo $claseTexto; ?>">
                                <?php echo $esObsoleto ? '⚪' : '⚙️'; ?> <?php echo limpiar($op['nombre']); ?>
                            </span>

                            <a href="toggle_obsoleto.php?op=<?php echo urlencode($op['nombre']); ?>&status=<?php echo $op['obsoleto']; ?>"
                               class="btn-status <?php echo $claseBtn; ?> no-print"
                               onclick="return confirm('¿Cambiar estado?')">
                                <?php echo $labelBtn; ?>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <div style="background: #222; color: #999; text-align: center; padding: 15px; font-size: 10px;">
        KH - Documento de Control de Procesos
    </div>
</div>

</body>
</html>
