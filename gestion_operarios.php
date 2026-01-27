<head>
    <meta charset="UTF-8">
    <?php include 'header_meta.php'; ?>
    <title>Nombre de la página</title>
    <style> ... </style>
</head>
<?php
include 'conexion.php';
error_reporting(E_ALL & ~E_DEPRECATED);

// 1. LÓGICA DE BAJA DE OPERARIO
if (isset($_GET['delete_id'])) {
    $id_del = $_GET['delete_id'];
    $fechaHoy = date('Y-m-d H:i:s');
    $sqlBaja = "UPDATE [dbo].[pol_Operarios] SET [FechaBaja] = ? WHERE [Operario] = ?";
    sqlsrv_query($conn, $sqlBaja, array($fechaHoy, $id_del));
    header("Location: gestion_operarios.php?msg=baja");
    exit;
}

// 2. LÓGICA DE BÚSQUEDA
$search = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';

// 3. CONSULTA (Solo operarios que NO tienen FechaBaja)
$query = "SELECT [Operario], [NombreOperario], [Cargo1] 
          FROM [dbo].[pol_Operarios] 
          WHERE ([FechaBaja] IS NULL OR [FechaBaja] = '')";
$params = array();

if($search !== '') {
    $query .= " AND ([NombreOperario] LIKE ? OR [Operario] LIKE ?)";
    $params = array("%$search%", "%$search%");
}
$query .= " ORDER BY [NombreOperario] ASC";
$res = sqlsrv_query($conn, $query, $params);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>KH - Gestión de Operarios</title>
    <style>
        body { font-family: sans-serif; margin: 0; color: #6e6d6b; background: #fff; }
        .header-kh { background: #8c181a; color: white; padding: 15px 40px; display: flex; justify-content: space-between; align-items: center; }
        .content { padding: 30px 40px; }
        .toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .search-box { border: 2px solid #8c181a; padding: 10px; width: 300px; border-radius: 4px; outline: none; }
        .btn-new { background: #8c181a; color: white; padding: 12px 20px; text-decoration: none; font-weight: bold; border-radius: 4px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f2f2f2; color: #8c181a; padding: 12px; text-align: left; border-bottom: 2px solid #8c181a; }
        td { padding: 12px; border-bottom: 1px solid #eee; }
        .btn-action { padding: 6px 12px; text-decoration: none; font-size: 11px; font-weight: bold; border-radius: 3px; color: white; display: inline-block; margin-right: 5px; }
        .bg-poly { background: #b18e3a; } 
        .bg-edit { background: #6e6d6b; }
        .bg-del { background: #8c181a; border: none; cursor: pointer; }
    </style>
</head>
<body>

<div class="header-kh">
    <div style="display:flex; align-items:center; gap:20px;">
        <a href="index.php" style="color:white; text-decoration:none; font-size:20px;">🏠</a>
        <h2 style="margin:0;">GESTIÓN DE OPERARIOS</h2>
    </div>
    <img src="logo.png" style="height:40px;">
</div>

<div class="content">
    <div class="toolbar">
        <form method="GET">
            <input type="text" name="buscar" class="search-box" placeholder="Buscar operario..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" style="padding:10px; cursor:pointer; background:#6e6d6b; color:white; border:none; border-radius:4px;">BUSCAR</button>
        </form>
        <a href="alta_operario.php" class="btn-new">+ NUEVO OPERARIO</a>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>APELLIDOS, NOMBRE</th>
                <th>CARGO / SECCIÓN</th>
                <th style="text-align:center;">ACCIONES</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)): ?>
            <tr>
                <td><?php echo $row['Operario']; ?></td>
                <td><strong><?php echo strtoupper(htmlspecialchars($row['NombreOperario'])); ?></strong></td>
                <td><?php echo htmlspecialchars($row['Cargo1'] ?? ''); ?></td>
                <td style="text-align:center;">
                    <a href="polivalencias.php?operario=<?php echo urlencode(trim($row['NombreOperario'])); ?>" class="btn-action bg-poly">🎯 MATRIZ</a>
                    
                    <a href="editar_operario.php?id=<?php echo urlencode($row['Operario']); ?>" class="btn-action bg-edit">EDITAR</a>
                    
                    <button onclick="confirmarBaja('<?php echo $row['Operario']; ?>', '<?php echo addslashes($row['NombreOperario']); ?>')" class="btn-action bg-del">BAJA</button>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<script>
function confirmarBaja(id, nombre) {
    if(confirm("¿Dar de baja a " + nombre + "?")) {
        window.location.href = "gestion_operarios.php?delete_id=" + id;
    }
}
</script>
</body>
</html>