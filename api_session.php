<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['logged_in' => false]);
    exit();
}

echo json_encode([
    'logged_in' => true,
    'usuario' => $_SESSION['usuario'],
    'rol' => $_SESSION['rol']
]);
?>