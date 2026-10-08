<?php
// ARCHIVO: controladores/api_tareas_pendientes.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

// Obtención flexible de datos de sesión
$idUsuario = $_SESSION['emp_auth']['id_usuario'] ?? $_SESSION['id_usuario'] ?? null;
$rolUsuario = (int) ($_SESSION['emp_auth']['rol_usuario'] ?? $_SESSION['rol_usuario'] ?? 0);

// Si no hay sesión válida de admin/gestor, responde 0 sin arrojar error 403 de servidor
if (!$idUsuario || !in_array($rolUsuario, [1, 2], true)) {
    echo json_encode(['total' => 0, 'status' => 'unauthorized']);
    exit();
}

require_once __DIR__ . '/../configuracion/conexion.php';

/** @var mysqli $conexion */
$sql = "SELECT COUNT(*) as total FROM tarea WHERE est_tar = 'Pendiente'";
$resultado = $conexion->query($sql);

if ($resultado && $fila = $resultado->fetch_assoc()) {
    echo json_encode(['total' => (int) $fila['total'], 'status' => 'exito']);
} else {
    echo json_encode(['total' => 0, 'status' => 'vacio']);
}
exit();