<?php
// Historial de facturas para recepción: ve las últimas 5; para ver todas pide permiso al administrador.
/** @var mysqli $conexion */
if (!usuario_tiene_permiso($conexion, 'facturas.ver')) {
    return;
}
?>
<section id="sec-facturas" class="seccion-contenido hidden">
    <?php require __DIR__ . '/../../../includes/facturas_historial.php'; ?>
</section>