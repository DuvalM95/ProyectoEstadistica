-- ============================================================
-- Script SQL para crear la base de datos del proyecto IoT ESP32
-- Ejecutar en phpMyAdmin: http://localhost/phpmyadmin
-- ============================================================

-- Crear base de datos
CREATE DATABASE IF NOT EXISTS clima_esp32
DEFAULT CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE clima_esp32;

-- Crear tabla de registros
CREATE TABLE IF NOT EXISTS registros (
    id INT AUTO_INCREMENT PRIMARY KEY,
    humedad DECIMAL(5,2) NOT NULL,
    temperatura DECIMAL(5,2) NOT NULL,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_fecha (fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- (OPCIONAL) Insertar datos de ejemplo para probar el dashboard
-- Borra estas líneas si no quieres datos de prueba
-- ============================================================
INSERT INTO registros (humedad, temperatura, fecha) VALUES
(45.5, 22.3, NOW() - INTERVAL 6 DAY),
(48.2, 23.1, NOW() - INTERVAL 6 DAY + INTERVAL 1 HOUR),
(52.0, 24.0, NOW() - INTERVAL 5 DAY),
(55.8, 25.2, NOW() - INTERVAL 5 DAY + INTERVAL 2 HOUR),
(60.3, 26.1, NOW() - INTERVAL 4 DAY),
(63.7, 27.3, NOW() - INTERVAL 4 DAY + INTERVAL 3 HOUR),
(58.4, 25.8, NOW() - INTERVAL 3 DAY),
(54.2, 24.5, NOW() - INTERVAL 3 DAY + INTERVAL 4 HOUR),
(50.1, 23.7, NOW() - INTERVAL 2 DAY),
(47.9, 22.9, NOW() - INTERVAL 2 DAY + INTERVAL 5 HOUR),
(72.5, 28.4, NOW() - INTERVAL 1 DAY),
(75.1, 29.2, NOW() - INTERVAL 1 DAY + INTERVAL 2 HOUR),
(38.5, 21.8, NOW() - INTERVAL 5 HOUR),
(42.3, 22.5, NOW() - INTERVAL 4 HOUR),
(46.7, 23.4, NOW() - INTERVAL 3 HOUR),
(51.2, 24.6, NOW() - INTERVAL 2 HOUR),
(56.8, 25.9, NOW() - INTERVAL 1 HOUR),
(59.4, 26.3, NOW() - INTERVAL 30 MINUTE),
(62.1, 26.8, NOW() - INTERVAL 10 MINUTE),
(64.5, 27.1, NOW());
