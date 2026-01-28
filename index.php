<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

include 'conexion.php';
error_reporting(E_ALL & ~E_DEPRECATED);

function tienePermiso($rolesPermitidos) {
    $rolActual = $_SESSION['rol'] ?? 'Usuario';
    return in_array($rolActual, $rolesPermitidos);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <?php include 'header_meta.php'; ?>

    <title>KH - Sistema de Gestión de Polivalencias</title>

    <style>
        :root {
            --kh-granate: #8c181a;
            --kh-dorado: #b18e3a;
            --kh-gris: #6e6d6b;
            --kh-fondo: #f4f4f4;
            --kh-blanco: #ffffff;
        }

        body {
            padding-bottom: 50px;
        }

        .header {
            width: 100%;
            background-color: var(--kh-granate);
            color: white;
            padding: 25px 20px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            margin-bottom: 30px;
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        @media (min-width: 768px) {
            .header {
                flex-direction: row;
                justify-content: space-between;
                text-align: left;
                padding: 25px 40px;
            }
        }

        .header img {
            height: 60px;
            background: white;
            padding: 5px;
            border-radius: 4px;
        }

        .user-panel {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 5px;
            margin-top: 20px;
        }

        @media (min-width: 768px) {
            .user-panel {
                position: absolute;
                top: 20px;
                right: 40px;
                align-items: flex-end;
                margin-top: 0;
            }
        }

        .btn-usuarios {
            background: var(--kh-dorado);
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 12px;
            font-weight: bold;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        .logout-link {
            color: #ffcccc;
            font-size: 11px;
            text-decoration: none;
        }

        .section-title {
            color: var(--kh-gris);
            font-size: 1.1rem;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            border-bottom: 2px solid #ddd;
            padding-bottom: 10px;
            margin: 40px 0 20px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .grid-menu {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }

        .card {
            background: var(--kh-blanco);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
            border-top: 5px solid var(--kh-granate);
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .card-icon {
            padding: 35px 10px;
            text-align: center;
            font-size: 45px;
            background-color: #fafafa;
        }

        .card-content {
            padding: 20px;
            text-align: center;
            flex-grow: 1;
        }

        .card-content h3 {
            margin: 0 0 10px 0;
            color: var(--kh-granate);
            font-size: 1.2rem;
        }

        .card-content p {
            margin: 0;
            color: var(--kh-gris);
            font-size: 0.9rem;
            line-height: 1.4;
        }

        .footer {
            text-align: center;
            margin-top: 60px;
            color: var(--kh-gris);
            font-size: 0.85rem;
        }
    </style>
</head>
<body>

<div class="header">
    <div style="display: flex; align-items: center; gap: 20px; flex-direction: inherit;">
        <img src="logo.png" alt="KH Logo">
        <h1 style="margin: 0; letter-spacing: 2px; font-size: 1.6rem;">SISTEMA DE GESTIÓN DE POLIVALENCIAS</h1>
    </div>

    <div class="user-panel">
        <?php if(tienePermiso(['Administrador'])): ?>
            <a href="gestion_usuarios.php" class="btn-usuarios">👥 GESTIÓN USUARIOS</a>
        <?php endif; ?>

        <div style="font-size: 12px;">👤 <b><?php echo htmlspecialchars($_SESSION['usuario']); ?></b></div>
        <a href="logout.php" class="logout-link">Cerrar Sesión (<?php echo $_SESSION['rol']; ?>)</a>
    </div>
</div>

<div class="container">

    <?php if(tienePermiso(['Administrador'])): ?>
    <div class="section-title">🛠️ Configuración y Maestros</div>
    <div class="grid-menu">
        <a href="gestion_matriz.php" class="card">
            <div class="card-icon">📋</div>
            <div class="card-content">
                <h3>Gestión Matriz</h3>
                <p>Definición de puestos, secciones y operarios necesarios.</p>
            </div>
        </a>
        <a href="gestion_operarios.php" class="card">
            <div class="card-icon">👥</div>
            <div class="card-content">
                <h3>Gestión Operarios</h3>
                <p>Alta de personal y control de bajas.</p>
            </div>
        </a>
    </div>
    <?php endif; ?>

    <div class="section-title">⚙️ Operativa Diaria</div>
    <div class="grid-menu">
        <?php if(tienePermiso(['Administrador', 'Tecnico'])): ?>
            <a href="polivalencias.php" class="card">
                <div class="card-icon">🎯</div>
                <div class="card-content">
                    <h3>Asignación de Niveles</h3>
                    <p>Vincular operarios a puestos y porcentajes.</p>
                </div>
            </a>
            <a href="formacion.php" class="card">
                <div class="card-icon">🎓</div>
                <div class="card-content">
                    <h3>Formación e Imputación</h3>
                    <p>Registro de horas de formación y aprendizaje.</p>
                </div>
            </a>
        <?php else: ?>
            <p style="color: #999; font-style: italic; padding: 10px;">Perfil de lectura: Sin acceso a edición.</p>
        <?php endif; ?>
    </div>

    <div class="section-title">📊 Informes y Análisis</div>
    <div class="grid-menu">
        <a href="informe_lineas.php" class="card" style="border-top-color: var(--kh-dorado);">
            <div class="card-icon" style="background-color: #fffcf5;">📉</div>
            <div class="card-content">
                <h3>Polivalencias por Línea</h3>
                <p>Consulta visual por sección.</p>
            </div>
        </a>
        <a href="informe_formaciones_abiertas.php" class="card" style="border-top-color: var(--kh-dorado);">
            <div class="card-icon" style="background-color: #fffcf5;">📂</div>
            <div class="card-content">
                <h3>Formaciones Abiertas</h3>
                <p>Seguimiento de horas pendientes.</p>
            </div>
        </a>
        <a href="informe_operaciones_activas.php" class="card" style="border-top-color: var(--kh-dorado);">
            <div class="card-icon" style="background-color: #fffcf5;">⚙️</div>
            <div class="card-content">
                <h3>Catálogo de Operaciones</h3>
                <p>Listado de puestos activos y obsoletos.</p>
            </div>
        </a>
    </div>

    <div class="footer">
        &copy; 2026 KH - Calidad & Formación | Perfil: <b><?php echo $_SESSION['rol']; ?></b>
    </div>
</div>

</body>
</html>
