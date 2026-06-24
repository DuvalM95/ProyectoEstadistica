<?php
/**
 * ============================================================
 * Archivo: guardar.php
 * Descripción: Recibe los datos de humedad del ESP32 y los guarda en Supabase.
 * ============================================================
 */

require_once 'conexion.php';

if (!isset($_GET['humedad'])) {
    echo "Error: Falta el parámetro humedad";
    exit;
}

$humedad = filter_var($_GET['humedad'], FILTER_VALIDATE_FLOAT);

if ($humedad === false) {
    echo "Error: El valor de humedad debe ser numérico";
    exit;
}

if ($humedad < 0 || $humedad > 100) {
    echo "Error: Humedad fuera de rango (0-100)";
    exit;
}

try {
    $payload = [
        'humedad' => $humedad,
        'fecha_hora' => isset($_GET['fecha_hora']) ? $_GET['fecha_hora'] : date('Y-m-d H:i:s'),
    ];

    supabase_post(SUPABASE_TABLE, [$payload]);

    echo 'Datos guardados correctamente';
} catch (Throwable $e) {
    echo 'Error al guardar: ' . $e->getMessage();
}
?>
