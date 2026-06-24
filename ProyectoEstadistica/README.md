# 🌡️ Dashboard IoT ESP32 - Monitoreo Climático

Sistema web moderno para visualizar en tiempo real los datos de humedad y temperatura enviados desde un ESP32 con sensor DHT11.

## 🔄 Migración a Supabase

Este proyecto ya fue adaptado para funcionar con Supabase en vez de MySQL local. La app ahora usa la API REST de Supabase desde PHP, por lo que puedes subirlo a un hosting web y seguir usando el mismo dashboard.

## 📁 Estructura del Proyecto

```
esp32/
│
├── index.php             → Dashboard principal (interfaz)
├── conexion.php          → Conexión a Supabase mediante REST API
├── guardar.php           → Recibe datos del ESP32 y los guarda
├── obtener_datos.php     → API JSON con los datos del dashboard
├── graficas.php          → Datos de gráficas + exportación CSV
│
├── css/
│   └── estilos.css       → Estilos modernos con modo oscuro
│
├── js/
│   └── app.js            → Lógica de gráficas, tabla, AJAX
│
└── assets/
    └── database_supabase.sql → Script SQL para Supabase
```

## ⚙️ Configuración necesaria en Supabase

1. Crea un proyecto en Supabase.
2. En el SQL Editor, ejecuta el archivo [assets/database_supabase.sql](assets/database_supabase.sql).
3. En el panel de proyecto ve a Settings > API y copia:
   - URL del proyecto
   - anon/service role key
4. En tu hosting define estas variables de entorno:

```
SUPABASE_URL=https://TU_PROYECTO.supabase.co
SUPABASE_KEY=TU_ANON_KEY
```

Si prefieres, puedes usar SUPABASE_SERVICE_ROLE_KEY en lugar de SUPABASE_KEY.

## 📡 Cómo enviar datos desde el ESP32

El ESP32 debe hacer una petición HTTP GET a:

```
https://TU_DOMINIO/guardar.php?humedad=55.4&temperatura=23.1
```

## ✨ Características

- ✅ Diseño moderno y responsive (Bootstrap 5)
- ✅ Modo claro/oscuro persistente
- ✅ Gráficas interactivas
- ✅ Tarjetas estadísticas
- ✅ Tabla con búsqueda, filtros y paginación
- ✅ Actualización automática cada 10 segundos
- ✅ Indicador de estado del ESP32
- ✅ Exportación a CSV
- ✅ Compatible con hosting web y Supabase
