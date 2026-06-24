<?php
/**
 * ============================================================
 * Archivo: index.php
 * Descripción: Vista principal del Dashboard IoT.
 *              Renderiza la estructura HTML del dashboard.
 *              Toda la lógica dinámica se maneja con app.js
 * ============================================================
 */
require_once 'conexion.php';

$datos_iniciales = [];
try {
    $registros = obtener_registros_dashboard(5000);
    $datos_iniciales = [
        'total' => count($registros),
        'h_prom' => count($registros) > 0 ? round(array_sum(array_column($registros, 'humedad')) / count($registros), 1) : 0,
        'h_max' => count($registros) > 0 ? round(max(array_column($registros, 'humedad')), 1) : 0,
        'h_min' => count($registros) > 0 ? round(min(array_column($registros, 'humedad')), 1) : 0,
    ];
} catch (Throwable $e) {
    $datos_iniciales = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard IoT - Monitoreo Climático ESP32</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="css/estilos.css">

    <!-- Favicon -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🌡️</text></svg>">
</head>
<body>

<!-- ============= SIDEBAR ============= -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <i class="fas fa-microchip"></i>
        <h4>ESP32 IoT</h4>
    </div>
    <ul class="sidebar-menu">
        <li class="activo">
            <a href="#dashboard">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li>
            <a href="#estadisticas">
                <i class="fas fa-chart-line"></i>
                <span>Gráficas</span>
            </a>
        </li>
        <li>
            <a href="#tabla">
                <i class="fas fa-table"></i>
                <span>Registros</span>
            </a>
        </li>
        <li>
            <a href="graficas.php?export=csv">
                <i class="fas fa-file-csv"></i>
                <span>Exportar CSV</span>
            </a>
        </li>
        <li>
            <a href="https://github.com" target="_blank">
                <i class="fas fa-info-circle"></i>
                <span>Acerca de</span>
            </a>
        </li>
    </ul>
</aside>

<!-- ============= CONTENIDO PRINCIPAL ============= -->
<main class="contenido-principal" id="contenido-principal">

    <!-- NAVBAR SUPERIOR -->
    <nav class="navbar-superior">
        <div class="d-flex align-items-center gap-3">
            <button class="btn-toggle" id="btn-toggle-sidebar" title="Mostrar/ocultar menú">
                <i class="fas fa-bars"></i>
            </button>
            <div>
                <h5 class="mb-0 fw-bold">Sistema de Monitoreo Ambiental</h5>
                <small class="text-muted">Dashboard en tiempo real</small>
            </div>
        </div>

        <div class="navbar-info">
            <!-- Fecha y hora -->
            <div class="fecha-hora">
                <i class="far fa-calendar-alt"></i>
                <span id="fecha-hora-actual">Cargando...</span>
            </div>

            <!-- Estado ESP32 -->
            <div class="estado-esp32 offline" id="estado-esp32">
                <span class="punto-estado"></span>
                <span id="texto-estado">Verificando...</span>
            </div>

            <!-- Modo oscuro -->
            <button class="btn-modo" id="btn-modo-oscuro" title="Cambiar tema">
                <i class="fas fa-moon"></i>
            </button>
        </div>
    </nav>

    <!-- CONTENEDOR PRINCIPAL -->
    <div class="contenedor">

        <!-- TÍTULO -->
        <div class="titulo-pagina" id="dashboard">
            <h2><i class="fas fa-cloud-sun text-primary"></i> Dashboard de Clima</h2>
            <p>Monitoreo en tiempo real de humedad desde el ESP32 con sensor de humedad</p>
        </div>

        <!-- BANNER ÚLTIMO REGISTRO -->
        <div class="banner-ultimo">
            <div class="info">
                <div class="icono-banner">
                    <i class="fas fa-broadcast-tower"></i>
                </div>
                <div>
                    <h6>ÚLTIMO REGISTRO RECIBIDO</h6>
                    <div class="valores">
                        <i class="fas fa-tint"></i> <span id="ultimo-humedad">--</span>%
                    </div>
                    <small id="ultimo-fecha">Esperando datos...</small>
                </div>
            </div>
            <div class="contador-tiempo" id="contador-tiempo">
                <i class="fas fa-clock"></i> Esperando...
            </div>
        </div>

        <div id="alerta-api" class="alert alert-warning d-none" role="alert"></div>

        <!-- TARJETAS ESTADÍSTICAS -->
        <div class="row g-3 mb-4" id="estadisticas">

            <div class="col-12 col-sm-6 col-lg-4 col-xl-2">
                <div class="tarjeta-stat">
                    <div class="icono icono-cyan">
                        <i class="fas fa-tint"></i>
                    </div>
                    <div class="titulo">Humedad Promedio</div>
                    <div class="valor"><span id="stat-humedad-promedio"><?= $datos_iniciales['h_prom'] ?? '0' ?></span><span class="unidad">%</span></div>
                    <div class="sub-info">Media histórica</div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-lg-4 col-xl-2">
                <div class="tarjeta-stat">
                    <div class="icono icono-azul">
                        <i class="fas fa-arrow-up"></i>
                    </div>
                    <div class="titulo">Humedad Máxima</div>
                    <div class="valor"><span id="stat-humedad-max"><?= $datos_iniciales['h_max'] ?? '0' ?></span><span class="unidad">%</span></div>
                    <div class="sub-info">Pico máximo</div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-lg-4 col-xl-2">
                <div class="tarjeta-stat">
                    <div class="icono icono-naranja">
                        <i class="fas fa-arrow-down"></i>
                    </div>
                    <div class="titulo">Humedad Mínima</div>
                    <div class="valor"><span id="stat-humedad-min"><?= $datos_iniciales['h_min'] ?? '0' ?></span><span class="unidad">%</span></div>
                    <div class="sub-info">Valor mínimo</div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-lg-4 col-xl-2">
                <div class="tarjeta-stat">
                    <div class="icono icono-verde">
                        <i class="fas fa-database"></i>
                    </div>
                    <div class="titulo">Total Registros</div>
                    <div class="valor"><span id="stat-total"><?= $datos_iniciales['total'] ?? '0' ?></span></div>
                    <div class="sub-info">Lecturas totales</div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-lg-4 col-xl-2">
                <div class="tarjeta-stat">
                    <div class="icono icono-morado">
                        <i class="fas fa-sync-alt"></i>
                    </div>
                    <div class="titulo">Refresco Auto</div>
                    <div class="valor">5<span class="unidad">min</span></div>
                    <div class="sub-info">Actualización en vivo</div>
                </div>
            </div>

        </div>

        <!-- GRÁFICA DE HUMEDAD -->
        <div class="row g-3">
            <div class="col-12">
                <div class="card-personalizada">
                    <div class="card-header-personalizado">
                        <h5><i class="fas fa-tint"></i> Humedad en el Tiempo</h5>
                        <span class="badge bg-info">Últimos 30 registros</span>
                    </div>
                    <div class="card-body-personalizado">
                        <div class="contenedor-grafica">
                            <canvas id="grafica-humedad"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- GRÁFICA DE BARRAS Y CIRCULAR -->
        <div class="row g-3">
            <div class="col-12 col-lg-7">
                <div class="card-personalizada">
                    <div class="card-header-personalizado">
                        <h5><i class="fas fa-chart-bar"></i> Promedio Diario de Humedad</h5>
                        <span class="badge bg-primary">Últimos 7 días</span>
                    </div>
                    <div class="card-body-personalizado">
                        <div class="contenedor-grafica">
                            <canvas id="grafica-barras"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-5">
                <div class="card-personalizada">
                    <div class="card-header-personalizado">
                        <h5><i class="fas fa-chart-pie"></i> Distribución por Rango</h5>
                        <span class="badge bg-success">Humedad</span>
                    </div>
                    <div class="card-body-personalizado">
                        <div class="contenedor-grafica-pie">
                            <canvas id="grafica-circular"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TABLA DE REGISTROS -->
        <div class="card-personalizada" id="tabla">
            <div class="card-header-personalizado">
                <h5><i class="fas fa-table"></i> Registros Recientes</h5>
                <button class="btn-personalizado btn-exportar" id="btn-exportar">
                    <i class="fas fa-file-csv"></i> Exportar CSV
                </button>
            </div>
            <div class="card-body-personalizado">

                <!-- Controles -->
                <div class="controles-tabla">
                    <input type="text" class="input-busqueda" id="input-busqueda"
                           placeholder="Buscar por ID, humedad o fecha...">
                    <select class="select-filtro" id="filtro-rango">
                        <option value="todos">Todos los rangos</option>
                        <option value="baja">Humedad baja (&lt;40%)</option>
                        <option value="normal">Humedad normal (40-70%)</option>
                        <option value="alta">Humedad alta (&gt;70%)</option>
                    </select>
                    <select class="select-filtro" id="select-cantidad">
                        <option value="10">10 por página</option>
                        <option value="25">25 por página</option>
                        <option value="50">50 por página</option>
                        <option value="100">100 por página</option>
                    </select>
                    <button class="btn-personalizado" id="btn-refrescar" title="Refrescar ahora">
                        <i class="fas fa-sync-alt"></i> Refrescar
                    </button>
                </div>

                <!-- Tabla -->
                <div class="table-responsive">
                    <table class="tabla-personalizada">
                        <thead>
                            <tr>
                                <th data-columna="id">
                                    <i class="fas fa-hashtag"></i> ID
                                    <span class="flecha-orden"><i class="fas fa-sort"></i></span>
                                </th>
                                <th data-columna="humedad">
                                    <i class="fas fa-tint"></i> Humedad
                                    <span class="flecha-orden"><i class="fas fa-sort"></i></span>
                                </th>
                                <th data-columna="fecha">
                                    <i class="far fa-clock"></i> Fecha y Hora
                                    <span class="flecha-orden"><i class="fas fa-sort"></i></span>
                                </th>
                            </tr>
                        </thead>
                        <tbody id="tbody-tabla">
                            <tr>
                                <td colspan="3" class="text-center py-4">
                                    <i class="fas fa-spinner fa-spin"></i> Cargando datos...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div class="paginacion">
                    <div class="info-paginacion" id="info-paginacion">
                        Cargando...
                    </div>
                    <div class="botones-paginacion" id="botones-paginacion"></div>
                </div>

            </div>
        </div>

        <!-- PIE DE PÁGINA -->
        <footer class="text-center mt-4 mb-2">
            <p class="text-muted small">
                <i class="fas fa-microchip"></i>
                Sistema de Monitoreo IoT &copy; <?= date('Y') ?> &middot;
                Desarrollado con PHP, MySQL, Bootstrap 5 y Chart.js
            </p>
        </footer>

    </div>
</main>

<!-- INDICADOR DE ACTUALIZACIÓN -->
<div class="indicador-actualizacion" id="indicador-actualizacion">
    <i class="fas fa-sync-alt"></i> Actualizando datos...
</div>

<!-- ============= SCRIPTS ============= -->
<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<!-- Lógica de la aplicación -->
<script src="js/app.js"></script>

</body>
</html>
