<?php
// Indicar que la respuesta es de tipo JSON
header('Content-Type: application/json');

// Como este archivo está dentro de la carpeta CLI, el session.json está en la misma ruta
$sessionFile = __DIR__ . '/../session.json';

// file_exists() devuelve true si existe o false si no, 
// y json_encode lo convierte en el booleano que lee JavaScript
echo json_encode(file_exists($sessionFile));
?>