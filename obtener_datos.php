<?php
/**
 * ============================================================
 * Archivo: obtener_datos.php
 * Descripción: API JSON para el dashboard usando Supabase.
 *              Adaptada para mostrar solo datos de humedad.
 * ============================================================
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once 'conexion.php';

$respuesta = [];

try {
    $registros = obtener_registros_dashboard(5000);

    $respuesta['estadisticas'] = [
        'total' => count($registros),
        'humedad_promedio' => count($registros) > 0 ? round(array_sum(array_column($registros, 'humedad')) / count($registros), 2) : 0,
        'humedad_max' => count($registros) > 0 ? round(max(array_column($registros, 'humedad')), 2) : 0,
        'humedad_min' => count($registros) > 0 ? round(min(array_column($registros, 'humedad')), 2) : 0,
    ];

    $ultimo = $registros[0] ?? null;
    $respuesta['ultimo'] = $ultimo;

    if ($ultimo && !empty($ultimo['fecha'])) {
        $segundos = (int) abs(time() - strtotime($ultimo['fecha']));
        if ($segundos <= 60) {
            $respuesta['esp32'] = ['estado' => 'online', 'segundos' => $segundos];
        } elseif ($segundos <= 300) {
            $respuesta['esp32'] = ['estado' => 'inactivo', 'segundos' => $segundos];
        } else {
            $respuesta['esp32'] = ['estado' => 'offline', 'segundos' => $segundos];
        }
    } else {
        $respuesta['esp32'] = ['estado' => 'sin_datos', 'segundos' => 0];
    }

    $respuesta['registros'] = array_slice($registros, 0, 200);

    $grafica_lineas = ['labels' => [], 'humedad' => []];
    $lineas = array_slice($registros, 0, 30);
    $lineas = array_reverse($lineas);
    foreach ($lineas as $fila) {
        $timestamp = strtotime($fila['fecha']);
        $grafica_lineas['labels'][] = $timestamp ? date('H:i:s', $timestamp) : '';
        $grafica_lineas['humedad'][] = (float) $fila['humedad'];
    }
    $respuesta['grafica_lineas'] = $grafica_lineas;

    $promedio_diario = ['labels' => [], 'valores' => []];
    $porDia = [];
    foreach ($registros as $fila) {
        $dia = date('Y-m-d', strtotime($fila['fecha']));
        if (!isset($porDia[$dia])) {
            $porDia[$dia] = ['sum' => 0, 'count' => 0];
        }
        $porDia[$dia]['sum'] += $fila['humedad'];
        $porDia[$dia]['count']++;
    }
    ksort($porDia);
    foreach ($porDia as $dia => $datos) {
        $promedio_diario['labels'][] = $dia;
        $promedio_diario['valores'][] = round($datos['sum'] / $datos['count'], 2);
    }
    $respuesta['promedio_diario'] = $promedio_diario;

    $rangos = ['baja' => 0, 'normal' => 0, 'alta' => 0];
    foreach ($registros as $fila) {
        if ($fila['humedad'] < 40) {
            $rangos['baja']++;
        } elseif ($fila['humedad'] <= 70) {
            $rangos['normal']++;
        } else {
            $rangos['alta']++;
        }
    }
    $respuesta['rangos_humedad'] = $rangos;

    $respuesta['servidor_hora'] = date('Y-m-d H:i:s');
    $respuesta['estado_api'] = 'ok';
} catch (Throwable $e) {
    $respuesta['estado_api'] = 'error';
    $respuesta['mensaje'] = $e->getMessage();
}

echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
?>
