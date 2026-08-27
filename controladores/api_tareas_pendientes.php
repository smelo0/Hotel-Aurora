<?php
// Este archivo NO tiene HTML, solo devuelve un número para el AJAX
require_once '../configuracion/conexion.php';

// Contamos solo las tareas que dicen "Pendiente"
$sql = "SELECT COUNT(*) as total FROM tarea WHERE est_tar = 'Pendiente'";
$resultado = $conexion->query($sql);

if ($fila = $resultado->fetch_assoc()) {
    echo $fila['total']; // Devuelve el número (ej: 8, 12, 0)
} else {
    echo "0";
}
?>