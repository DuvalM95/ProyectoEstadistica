<?php
/**
 * ============================================================
 * Archivo: graficas.php
 * Descripción: Endpoint para exportar CSV y devolver datos JSON.
 * ============================================================
 */

require_once 'conexion.php';

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $nombreArchivo = 'registros_clima_' . date('Y-m-d_H-i-s') . '.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $nombreArchivo);
    echo "\xEF\xBB\xBF";

    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Humedad (%)', 'Fecha y Hora']);

    try {
        $registros = obtener_registros_dashboard(1000);
        foreach ($registros as $fila) {
            fputcsv($output, [
                $fila['id'],
                $fila['humedad'],
                $fila['fecha'],
            ]);
        }
    } catch (Throwable $e) {
        fputcsv($output, ['Error', $e->getMessage()]);
    }

    fclose($output);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

$respuesta = [];
$datos = [];

try {
    $registros = obtener_registros_dashboard(50);
    $registros = array_reverse($registros);

    foreach ($registros as $fila) {
        $timestamp = strtotime($fila['fecha']);
        $datos[] = [
            'humedad' => $fila['humedad'],
            'hora' => $timestamp ? date('H:i:s', $timestamp) : '',
            'dia' => $timestamp ? date('Y-m-d', $timestamp) : '',
        ];
    }
} catch (Throwable $e) {
    $respuesta['error'] = $e->getMessage();
}

$respuesta['datos'] = $datos;
$respuesta['total'] = count($datos);
echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
?>
