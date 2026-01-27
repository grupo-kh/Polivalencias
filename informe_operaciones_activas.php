<?php
include 'conexion.php';
error_reporting(E_ALL & ~E_DEPRECATED);

function limpiar($t) { return ($t === null) ? "" : htmlspecialchars(trim($t), ENT_QUOTES, 'UTF-8'); }

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
    <title>Catálogo de Operaciones - KH</title>
    <style>
        :root { --kh-red: #8c181a; --kh-gold: #b18e3a; --kh-bg: #f4f7f6; }
        body { font-family: 'Segoe UI', sans-serif; background: var(--kh-bg); margin: 0; padding: 20px; }
        .container { max-width: 900px; margin: auto; background: white; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        
        .header { 
            background: var(--kh-red); color: white; padding: 20px; 
            display: flex; justify-content: space-between; align-items: center; 
            border-radius: 10px 10px 0 0; 
        }

        .actions { display: flex; gap: 10px; }
        .btn-top { 
            padding: 8px 15px; border-radius: 5px; text-decoration: none; 
            font-size: 13px; border: 1px solid white; color: white; transition: 0.3s;
            cursor: pointer; background: transparent;
        }
        .btn-top:hover { background: white; color: var(--kh-red); }
        .btn-print { background: var(--kh-gold); border-color: var(--kh-gold); }

        .seccion-title { 
            background: #eee; padding: 12px 20px; font-weight: bold; 
            border-left: 5px solid var(--kh-gold); margin-top: 10px;
            text-transform: uppercase; font-size: 0.9rem; color: #333;
        }

        table { width: 100%; border-collapse: collapse; }
        td { padding: 12px 25px; border-bottom: 1px solid #eee; font-size: 14px; position: relative; }

        /* Botones de Estado */
        .btn-status {
            padding: 4px 10px; border-radius: 4px; text-decoration: none;
            font-size: 11px; font-weight: bold; float: right; transition: 0.2s;
        }
        .status-active { background: #e6f4ea; color: #1e7e34; border: 1px solid #1e7e34; }
        .status-obsolete { background: #fce8e6; color: #c5221f; border: 1px solid #c5221f; }
        
        .texto-obsoleto { color: #aaa; text-decoration: line-through; }

        /* --- CONFIGURACIÓN PARA PDF / IMPRESIÓN --- */
        @media print {
            body { padding: 0; background: white; }
            .container { box-shadow: none; max-width: 100%; border: none; }
            .actions, .btn-status { display: none !important; } /* Ocultamos botones */
            .header { background: white !important; color: black !important; border-bottom: 2px solid var(--kh-red); }
            .seccion-title { background: #f0f0f0 !important; -webkit-print-color-adjust: exact; }
            
            /* Marcador visual impreso para obsoletos ya que no hay botón */
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
<body>

<div class="container">
    <div class="header">
        <div>
            <h2 style="margin:0;">Catálogo de Operaciones</h2>
            <small>Generado el: <?php echo date('d/m/Y H:i'); ?></small>
        </div>
        <div class="actions">
            <button onclick="window.print();" class="btn-top btn-print">📄 Imprimir PDF</button>
            <a href="index.php" class="btn-top">Volver</a>
        </div>
    </div>

    <?php if (empty($datos)): ?>
        <p style="padding:40px; text-align:center;">No hay datos disponibles.</p>
    <?php else: ?>
        <?php foreach ($datos as $seccion => $ops): ?>
            <div class="seccion-title">📂 <?php echo limpiar($seccion); ?></div>
            <table>
                <?php foreach ($ops as $op): 
                    $esObsoleto = ($op['obsoleto'] == 1);
                    $claseTexto = $esObsoleto ? 'texto-obsoleto' : '';
                    $labelBtn = $esObsoleto ? 'OBSOLETO' : 'ACTIVO';
                    $claseBtn = $esObsoleto ? 'status-obsolete' : 'status-active';
                ?>
                <tr>
                    <td>
                        <span class="<?php echo $claseTexto; ?>">
                            <?php echo $esObsoleto ? '⚪' : '⚙️'; ?> <?php echo limpiar($op['nombre']); ?>
                        </span>
                        
                        <a href="toggle_obsoleto.php?op=<?php echo urlencode($op['nombre']); ?>&status=<?php echo $op['obsoleto']; ?>" 
                           class="btn-status <?php echo $claseBtn; ?>"
                           onclick="return confirm('¿Cambiar estado?')">
                            <?php echo $labelBtn; ?>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php endforeach; ?>
    <?php endif; ?>

    <div style="background: #222; color: #999; text-align: center; padding: 15px; font-size: 10px; border-radius: 0 0 10px 10px;">
        KH - Documento de Control de Procesos
    </div>
</div>

</body>
</html>