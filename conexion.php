<?php
/**
 * ============================================================
 * Archivo: conexion.php
 * Descripción: Conexión con Supabase mediante REST API.
 *              Compatible con hosting web compartido sin MySQL.
 *              Adaptada para trabajar solo con datos de humedad.
 * ============================================================
 */

declare(strict_types=1);

date_default_timezone_set('America/Guayaquil');

const SUPABASE_TABLE = 'humedad';

function obtener_supabase_config(): array
{
    $url = trim((string) getenv('SUPABASE_URL'));
    if ($url === '') {
        $url = trim((string) getenv('SUPABASE_PROJECT_URL'));
    }

    $url = rtrim($url, '/');
    if (preg_match('#/rest/v1/?$#', $url)) {
        $url = preg_replace('#/rest/v1/?$#', '', $url);
    }

    $key = trim((string) getenv('SUPABASE_SERVICE_ROLE_KEY'));
    if ($key === '') {
        $key = trim((string) getenv('SUPABASE_KEY'));
    }
    if ($key === '') {
        $key = trim((string) getenv('SUPABASE_ANON_KEY'));
    }

    return [
        'url' => rtrim($url, '/'),
        'key' => $key,
    ];
}

function supabase_request(string $method, string $path, ?array $data = null, array $query = []): array
{
    $config = obtener_supabase_config();
    $baseUrl = $config['url'];
    $apiKey = $config['key'];

    if ($baseUrl === '' || $apiKey === '') {
        throw new RuntimeException('Configura SUPABASE_URL y SUPABASE_KEY/SUPABASE_SERVICE_ROLE_KEY en el servidor.');
    }

    $url = $baseUrl . '/rest/v1/' . ltrim($path, '/');
    if ($query !== []) {
        $url .= '?' . http_build_query($query);
    }

    $headers = [
        'Accept: application/json',
        'apikey: ' . $apiKey,
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json',
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data ?? [], JSON_UNESCAPED_UNICODE));
    } elseif ($method === 'PATCH') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data ?? [], JSON_UNESCAPED_UNICODE));
    } elseif ($method !== 'GET') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if ($data !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
        }
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error !== '') {
        throw new RuntimeException('Error en la petición a Supabase: ' . $error);
    }

    if ($httpCode >= 400) {
        throw new RuntimeException('Supabase respondió con HTTP ' . $httpCode . ': ' . $response);
    }

    if ($response === '') {
        return [];
    }

    $decoded = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new RuntimeException('Respuesta inválida de Supabase.');
    }

    return is_array($decoded) ? $decoded : [];
}

function supabase_get(string $path, array $query = []): array
{
    return supabase_request('GET', $path, null, $query);
}

function supabase_post(string $path, array $data): array
{
    return supabase_request('POST', $path, $data);
}

function normalizar_registro(array $registro): array
{
    $fecha = $registro['fecha'] ?? $registro['fecha_hora'] ?? $registro['created_at'] ?? '';
    $timestamp = is_string($fecha) ? strtotime($fecha) : false;
    $fechaFormateada = $timestamp ? date('Y-m-d H:i:s', $timestamp) : '';

    return [
        'id' => $registro['id'] ?? null,
        'humedad' => isset($registro['humedad']) ? (float) $registro['humedad'] : 0,
        'fecha' => $fechaFormateada,
    ];
}

function obtener_registros_dashboard(int $limite = 5000): array
{
    $respuesta = supabase_get(SUPABASE_TABLE, [
        'select' => 'id,humedad,fecha_hora',
        'order' => 'id.desc',
        'limit' => (string) $limite,
    ]);

    $registros = [];
    foreach ($respuesta as $fila) {
        $registros[] = normalizar_registro($fila);
    }

    return $registros;
}
?>
