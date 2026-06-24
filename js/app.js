/* ==========================================================
   app.js - Lógica del Dashboard IoT ESP32
   - Carga datos por AJAX cada 5 minutos
   - Renderiza gráficas con Chart.js
   - Gestiona tabla con búsqueda, filtros, paginación, ordenamiento
   - Modo oscuro persistente (localStorage)
   ========================================================== */

// ============= VARIABLES GLOBALES =============
let graficaHumedad      = null;
let graficaBarras       = null;
let graficaCircular     = null;

let registrosCompletos  = [];   // Todos los registros (tabla)
let registrosFiltrados  = [];   // Registros filtrados por búsqueda
let paginaActual        = 1;
let registrosPorPagina  = 10;
let ordenColumna        = 'id';
let ordenDireccion      = 'desc';

const INTERVALO_REFRESCO = 300000; // 5 minutos

/* ==========================================================
   INICIALIZACIÓN AL CARGAR LA PÁGINA
   ========================================================== */
document.addEventListener('DOMContentLoaded', () => {
    aplicarTemaGuardado();
    iniciarReloj();
    inicializarGraficas();
    cargarDatos();

    // Refrescar cada 5 minutos
    setInterval(cargarDatos, INTERVALO_REFRESCO);

    // Eventos de la interfaz
    configurarEventos();
});

/* ==========================================================
   CONFIGURACIÓN DE EVENTOS
   ========================================================== */
function configurarEventos() {
    // Toggle sidebar
    document.getElementById('btn-toggle-sidebar')?.addEventListener('click', () => {
        document.querySelector('.sidebar').classList.toggle('mostrar');
        document.querySelector('.sidebar').classList.toggle('colapsado');
        document.querySelector('.contenido-principal').classList.toggle('expandido');
    });

    // Modo oscuro
    document.getElementById('btn-modo-oscuro')?.addEventListener('click', cambiarTema);

    // Búsqueda en tabla
    document.getElementById('input-busqueda')?.addEventListener('input', (e) => {
        filtrarRegistros();
    });

    // Filtro por rango de humedad
    document.getElementById('filtro-rango')?.addEventListener('change', filtrarRegistros);

    // Cambiar registros por página
    document.getElementById('select-cantidad')?.addEventListener('change', (e) => {
        registrosPorPagina = parseInt(e.target.value);
        paginaActual = 1;
        renderizarTabla();
    });

    // Botón exportar CSV
    document.getElementById('btn-exportar')?.addEventListener('click', () => {
        window.location.href = 'graficas.php?export=csv';
    });

    // Botón refrescar manual
    document.getElementById('btn-refrescar')?.addEventListener('click', cargarDatos);

    // Ordenar al hacer clic en cabeceras
    document.querySelectorAll('.tabla-personalizada thead th[data-columna]').forEach(th => {
        th.addEventListener('click', () => {
            const columna = th.dataset.columna;
            if (ordenColumna === columna) {
                ordenDireccion = ordenDireccion === 'asc' ? 'desc' : 'asc';
            } else {
                ordenColumna = columna;
                ordenDireccion = 'asc';
            }
            renderizarTabla();
        });
    });
}

/* ==========================================================
   RELOJ EN TIEMPO REAL
   ========================================================== */
function iniciarReloj() {
    const actualizar = () => {
        const ahora = new Date();
        const opciones = {
            weekday: 'long', year: 'numeric', month: 'long',
            day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit'
        };
        const texto = ahora.toLocaleDateString('es-ES', opciones);
        const elemento = document.getElementById('fecha-hora-actual');
        if (elemento) elemento.textContent = texto;
    };
    actualizar();
    setInterval(actualizar, 1000);
}

/* ==========================================================
   MODO OSCURO
   ========================================================== */
function aplicarTemaGuardado() {
    const tema = localStorage.getItem('tema') || 'claro';
    document.documentElement.setAttribute('data-tema', tema);
    actualizarIconoTema(tema);
}

function cambiarTema() {
    const temaActual = document.documentElement.getAttribute('data-tema');
    const nuevoTema = temaActual === 'oscuro' ? 'claro' : 'oscuro';
    document.documentElement.setAttribute('data-tema', nuevoTema);
    localStorage.setItem('tema', nuevoTema);
    actualizarIconoTema(nuevoTema);

    // Recrear las gráficas para que adopten los nuevos colores
    if (graficaHumedad) {
        graficaHumedad.destroy();
        graficaBarras.destroy();
        graficaCircular.destroy();
        inicializarGraficas();
        cargarDatos();
    }
}

function actualizarIconoTema(tema) {
    const icono = document.querySelector('#btn-modo-oscuro i');
    if (icono) {
        icono.className = tema === 'oscuro' ? 'fas fa-sun' : 'fas fa-moon';
    }
}

/* ==========================================================
   CARGAR DATOS DESDE EL SERVIDOR (AJAX)
   ========================================================== */
async function cargarDatos() {
    mostrarIndicadorActualizacion();

    try {
        const respuesta = await fetch('obtener_datos.php');
        const datos = await respuesta.json();

        if (datos.estado_api !== 'ok') {
            mostrarErrorAPI(datos.mensaje || 'No se pudieron cargar los datos.');
            console.error('Error en API:', datos.mensaje);
            return;
        }

        ocultarErrorAPI();

        // 1. Actualizar tarjetas estadísticas
        actualizarEstadisticas(datos.estadisticas);

        // 2. Actualizar último registro y estado del ESP32
        actualizarUltimoRegistro(datos.ultimo);
        actualizarEstadoESP32(datos.esp32);

        // 3. Actualizar gráficas
        actualizarGraficaLineas(datos.grafica_lineas);
        actualizarGraficaBarras(datos.promedio_diario);
        actualizarGraficaCircular(datos.rangos_humedad);

        // 4. Actualizar tabla
        registrosCompletos = datos.registros;
        filtrarRegistros();

    } catch (error) {
        console.error('Error al cargar datos:', error);
    } finally {
        ocultarIndicadorActualizacion();
    }
}

/* ==========================================================
   INDICADOR DE ACTUALIZACIÓN
   ========================================================== */
function mostrarIndicadorActualizacion() {
    document.getElementById('indicador-actualizacion')?.classList.add('visible');
}

function ocultarIndicadorActualizacion() {
    setTimeout(() => {
        document.getElementById('indicador-actualizacion')?.classList.remove('visible');
    }, 800);
}

/* ==========================================================
   ACTUALIZAR TARJETAS ESTADÍSTICAS
   ========================================================== */
function mostrarErrorAPI(mensaje) {
    const alerta = document.getElementById('alerta-api');
    if (!alerta) return;
    alerta.textContent = mensaje;
    alerta.classList.remove('d-none');
}

function ocultarErrorAPI() {
    const alerta = document.getElementById('alerta-api');
    if (!alerta) return;
    alerta.textContent = '';
    alerta.classList.add('d-none');
}

function actualizarEstadisticas(stats) {
    if (!stats) return;
    setText('stat-humedad-promedio', stats.humedad_promedio ?? '0');
    setText('stat-humedad-max',      stats.humedad_max      ?? '0');
    setText('stat-humedad-min',      stats.humedad_min      ?? '0');
    setText('stat-total',            stats.total            ?? '0');
}

function setText(id, valor) {
    const el = document.getElementById(id);
    if (el) el.textContent = valor;
}

/* ==========================================================
   ÚLTIMO REGISTRO Y ESTADO DEL ESP32
   ========================================================== */
function actualizarUltimoRegistro(ultimo) {
    if (!ultimo) {
        setText('ultimo-humedad', '--');
        setText('ultimo-fecha', 'Sin datos');
        return;
    }
    setText('ultimo-humedad', ultimo.humedad);
    setText('ultimo-fecha', formatearFecha(ultimo.fecha));
}

function actualizarEstadoESP32(esp32) {
    const elemento = document.getElementById('estado-esp32');
    const texto    = document.getElementById('texto-estado');
    if (!elemento || !esp32) return;

    elemento.classList.remove('online', 'offline', 'inactivo');

    let etiqueta = '';
    switch (esp32.estado) {
        case 'online':
            elemento.classList.add('online');
            etiqueta = `ESP32 ONLINE (hace ${esp32.segundos}s)`;
            break;
        case 'inactivo':
            elemento.classList.add('inactivo');
            etiqueta = `ESP32 INACTIVO (hace ${Math.floor(esp32.segundos/60)} min)`;
            break;
        case 'offline':
            elemento.classList.add('offline');
            etiqueta = `ESP32 OFFLINE`;
            break;
        default:
            elemento.classList.add('offline');
            etiqueta = 'SIN DATOS';
    }
    if (texto) texto.textContent = etiqueta;

    // Contador en banner
    const contador = document.getElementById('contador-tiempo');
    if (contador && esp32.estado === 'online') {
        contador.textContent = `Hace ${esp32.segundos}s`;
    } else if (contador) {
        contador.textContent = `Inactivo`;
    }
}

/* ==========================================================
   INICIALIZAR GRÁFICAS (CHART.JS)
   ========================================================== */
function inicializarGraficas() {
    const colorTexto = obtenerColorTexto();
    const colorGrid  = obtenerColorGrid();

    // Configuración común
    Chart.defaults.color = colorTexto;
    Chart.defaults.font.family = "'Segoe UI', sans-serif";

    // ============ A) GRÁFICA LÍNEA HUMEDAD ============
    const ctxH = document.getElementById('grafica-humedad').getContext('2d');
    const gradienteH = ctxH.createLinearGradient(0, 0, 0, 300);
    gradienteH.addColorStop(0, 'rgba(0, 180, 216, 0.4)');
    gradienteH.addColorStop(1, 'rgba(0, 180, 216, 0.02)');

    graficaHumedad = new Chart(ctxH, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Humedad (%)',
                data: [],
                borderColor: '#00b4d8',
                backgroundColor: gradienteH,
                tension: 0.4,
                fill: true,
                borderWidth: 3,
                pointRadius: 4,
                pointBackgroundColor: '#00b4d8',
                pointHoverRadius: 7
            }]
        },
        options: opcionesGraficaLinea(colorGrid, '%')
    });

    // ============ B) GRÁFICA BARRAS - PROMEDIO DIARIO ============
    const ctxB = document.getElementById('grafica-barras').getContext('2d');
    graficaBarras = new Chart(ctxB, {
        type: 'bar',
        data: {
            labels: [],
            datasets: [{
                label: 'Humedad promedio (%)',
                data: [],
                backgroundColor: [
                    'rgba(67, 97, 238, 0.8)',
                    'rgba(58, 12, 163, 0.8)',
                    'rgba(0, 180, 216, 0.8)',
                    'rgba(6, 214, 160, 0.8)',
                    'rgba(255, 209, 102, 0.8)',
                    'rgba(239, 71, 111, 0.8)',
                    'rgba(114, 9, 183, 0.8)'
                ],
                borderRadius: 8,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(0,0,0,0.8)',
                    padding: 12,
                    cornerRadius: 8,
                    callbacks: { label: ctx => `Promedio: ${ctx.parsed.y}%` }
                }
            },
            scales: {
                x: { grid: { display: false }, ticks: { color: colorTexto } },
                y: {
                    beginAtZero: true,
                    grid: { color: colorGrid },
                    ticks: {
                        color: colorTexto,
                        callback: v => v + '%'
                    }
                }
            }
        }
    });

    // ============ C) GRÁFICA CIRCULAR ============
    const ctxC = document.getElementById('grafica-circular').getContext('2d');
    graficaCircular = new Chart(ctxC, {
        type: 'doughnut',
        data: {
            labels: ['Baja (<40%)', 'Normal (40-70%)', 'Alta (>70%)'],
            datasets: [{
                data: [0, 0, 0],
                backgroundColor: ['#ffd166', '#06d6a0', '#00b4d8'],
                borderColor: 'transparent',
                borderWidth: 3,
                hoverOffset: 12
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '65%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: colorTexto,
                        padding: 15,
                        font: { size: 12 },
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0,0,0,0.8)',
                    padding: 12,
                    cornerRadius: 8,
                    callbacks: {
                        label: ctx => {
                            const total = ctx.dataset.data.reduce((a,b) => a+b, 0);
                            const pct = total ? ((ctx.parsed/total)*100).toFixed(1) : 0;
                            return `${ctx.label}: ${ctx.parsed} (${pct}%)`;
                        }
                    }
                }
            }
        }
    });
}

function opcionesGraficaLinea(colorGrid, unidad) {
    return {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { intersect: false, mode: 'index' },
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: 'rgba(0,0,0,0.8)',
                padding: 12,
                cornerRadius: 8,
                callbacks: { label: ctx => ` ${ctx.parsed.y} ${unidad}` }
            }
        },
        scales: {
            x: {
                grid: { display: false },
                ticks: { color: obtenerColorTexto(), maxRotation: 0, autoSkip: true, maxTicksLimit: 8 }
            },
            y: {
                grid: { color: colorGrid },
                ticks: { color: obtenerColorTexto(), callback: v => v + unidad }
            }
        }
    };
}

function obtenerColorTexto() {
    return document.documentElement.getAttribute('data-tema') === 'oscuro'
        ? '#adb5bd' : '#6c757d';
}
function obtenerColorGrid() {
    return document.documentElement.getAttribute('data-tema') === 'oscuro'
        ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)';
}

/* ==========================================================
   ACTUALIZAR DATOS DE LAS GRÁFICAS
   ========================================================== */
function actualizarGraficaLineas(datos) {
    if (!datos) return;
    graficaHumedad.data.labels = datos.labels;
    graficaHumedad.data.datasets[0].data = datos.humedad;
    graficaHumedad.update('none');
}

function actualizarGraficaBarras(datos) {
    if (!datos) return;
    graficaBarras.data.labels = datos.labels;
    graficaBarras.data.datasets[0].data = datos.valores;
    graficaBarras.update('none');
}

function actualizarGraficaCircular(rangos) {
    if (!rangos) return;
    graficaCircular.data.datasets[0].data = [
        rangos.baja, rangos.normal, rangos.alta
    ];
    graficaCircular.update('none');
}

/* ==========================================================
   TABLA: FILTRAR, ORDENAR, PAGINAR
   ========================================================== */
function filtrarRegistros() {
    const busqueda = (document.getElementById('input-busqueda')?.value || '').toLowerCase();
    const rango    = document.getElementById('filtro-rango')?.value || 'todos';

    registrosFiltrados = registrosCompletos.filter(r => {
        // Búsqueda
        const coincideBusqueda =
            r.id.toString().includes(busqueda) ||
            r.humedad.toString().includes(busqueda) ||
            r.fecha.toLowerCase().includes(busqueda);

        // Filtro por rango
        const h = parseFloat(r.humedad);
        let coincideRango = true;
        if (rango === 'baja')   coincideRango = h < 40;
        if (rango === 'normal') coincideRango = h >= 40 && h <= 70;
        if (rango === 'alta')   coincideRango = h > 70;

        return coincideBusqueda && coincideRango;
    });

    paginaActual = 1;
    renderizarTabla();
}

function renderizarTabla() {
    // Ordenar
    registrosFiltrados.sort((a, b) => {
        let valA = a[ordenColumna];
        let valB = b[ordenColumna];
        if (!isNaN(parseFloat(valA))) {
            valA = parseFloat(valA);
            valB = parseFloat(valB);
        }
        if (valA < valB) return ordenDireccion === 'asc' ? -1 : 1;
        if (valA > valB) return ordenDireccion === 'asc' ?  1 : -1;
        return 0;
    });

    // Paginación
    const total = registrosFiltrados.length;
    const totalPaginas = Math.ceil(total / registrosPorPagina) || 1;
    if (paginaActual > totalPaginas) paginaActual = totalPaginas;

    const inicio = (paginaActual - 1) * registrosPorPagina;
    const fin    = inicio + registrosPorPagina;
    const pagina = registrosFiltrados.slice(inicio, fin);

    // Renderizar filas
    const tbody = document.getElementById('tbody-tabla');
    tbody.innerHTML = '';

    if (pagina.length === 0) {
        tbody.innerHTML = `<tr><td colspan="4" class="text-center py-4 text-muted">
            <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
            No se encontraron registros
        </td></tr>`;
    } else {
        pagina.forEach(r => {
            const h = parseFloat(r.humedad);
            const claseH = h < 40 ? 'baja' : (h <= 70 ? 'normal' : 'alta');

            tbody.innerHTML += `
                <tr class="animar">
                    <td><strong>#${r.id}</strong></td>
                    <td><span class="badge-humedad ${claseH}">
                        <i class="fas fa-tint"></i> ${r.humedad}%
                    </span></td>
                    <td><i class="far fa-clock me-1 text-muted"></i>${r.fecha}</td>
                </tr>
            `;
        });
    }

    // Info y botones de paginación
    document.getElementById('info-paginacion').textContent =
        `Mostrando ${total === 0 ? 0 : inicio+1}-${Math.min(fin, total)} de ${total} registros`;

    renderizarBotonesPaginacion(totalPaginas);
    actualizarIndicadoresOrden();
}

function renderizarBotonesPaginacion(totalPaginas) {
    const cont = document.getElementById('botones-paginacion');
    cont.innerHTML = '';

    // Botón anterior
    cont.innerHTML += `<button class="btn-paginacion" ${paginaActual===1?'disabled':''} onclick="cambiarPagina(${paginaActual-1})">
        <i class="fas fa-chevron-left"></i>
    </button>`;

    // Páginas (mostrar máximo 5)
    let inicio = Math.max(1, paginaActual - 2);
    let fin = Math.min(totalPaginas, inicio + 4);
    if (fin - inicio < 4) inicio = Math.max(1, fin - 4);

    if (inicio > 1) {
        cont.innerHTML += `<button class="btn-paginacion" onclick="cambiarPagina(1)">1</button>`;
        if (inicio > 2) cont.innerHTML += `<span class="btn-paginacion" style="border:none">...</span>`;
    }

    for (let i = inicio; i <= fin; i++) {
        cont.innerHTML += `<button class="btn-paginacion ${i===paginaActual?'activo':''}" onclick="cambiarPagina(${i})">${i}</button>`;
    }

    if (fin < totalPaginas) {
        if (fin < totalPaginas-1) cont.innerHTML += `<span class="btn-paginacion" style="border:none">...</span>`;
        cont.innerHTML += `<button class="btn-paginacion" onclick="cambiarPagina(${totalPaginas})">${totalPaginas}</button>`;
    }

    // Botón siguiente
    cont.innerHTML += `<button class="btn-paginacion" ${paginaActual===totalPaginas?'disabled':''} onclick="cambiarPagina(${paginaActual+1})">
        <i class="fas fa-chevron-right"></i>
    </button>`;
}

function cambiarPagina(num) {
    paginaActual = num;
    renderizarTabla();
}

function actualizarIndicadoresOrden() {
    document.querySelectorAll('.tabla-personalizada thead th[data-columna]').forEach(th => {
        const flecha = th.querySelector('.flecha-orden');
        if (!flecha) return;
        if (th.dataset.columna === ordenColumna) {
            flecha.innerHTML = ordenDireccion === 'asc'
                ? '<i class="fas fa-sort-up"></i>'
                : '<i class="fas fa-sort-down"></i>';
        } else {
            flecha.innerHTML = '<i class="fas fa-sort" style="opacity:0.4"></i>';
        }
    });
}

/* ==========================================================
   UTILIDADES
   ========================================================== */
function formatearFecha(fechaStr) {
    if (!fechaStr) return '';
    const fecha = new Date(fechaStr.replace(' ', 'T'));
    return fecha.toLocaleString('es-ES', {
        day:'2-digit', month:'2-digit', year:'numeric',
        hour:'2-digit', minute:'2-digit', second:'2-digit'
    });
}
