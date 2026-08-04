-- ============================================
-- LUMINA: Motor de comprensión de código
-- Esquema de base de datos para MySQL/MariaDB
-- ============================================
-- Este script crea las tablas necesarias para el sistema Lumina
-- que analiza codebases PHP y genera un grafo de conocimiento.
-- 
-- Motor: MySQL/MariaDB 8.0+
-- Charset: utf8mb4 (soporte completo de Unicode)
-- Collation: utf8mb4_unicode_ci
-- ============================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- ============================================
-- TABLA: files
-- ============================================
-- Almacena información de cada archivo PHP analizado.
-- Esta tabla es el punto de entrada principal para el análisis.
-- Cada archivo tiene una única entrada que agrupa todos sus chunks.
-- ============================================

CREATE TABLE IF NOT EXISTS `files` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `path` VARCHAR(500) NOT NULL UNIQUE COMMENT 'Ruta relativa del archivo desde el root del proyecto',
    `name` VARCHAR(255) NOT NULL COMMENT 'Nombre del archivo con extensión',
    `namespace` VARCHAR(255) NULL COMMENT 'Namespace PHP si aplica',
    `type` ENUM('class', 'interface', 'trait', 'function_file', 'mixed') NOT NULL COMMENT 'Tipo principal del archivo',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_path` (`path`),
    INDEX `idx_namespace` (`namespace`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Archivos PHP analizados por Lumina';

-- ============================================
-- TABLA: chunks
-- ============================================
-- Almacena cada "fragmento" de código: clases, funciones, métodos, propiedades.
-- Los chunks forman una estructura jerárquica dentro de cada archivo.
-- Un chunk puede tener un padre (ej: método -> clase) o ser independiente.
-- ============================================

CREATE TABLE IF NOT EXISTS `chunks` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `file_id` INT NOT NULL COMMENT 'Referencia al archivo que contiene este chunk',
    `parent_chunk_id` INT NULL COMMENT 'Si es un método/propiedad, apunta a su clase/clase padre',
    `name` VARCHAR(255) NOT NULL COMMENT 'Nombre del elemento (clase, función, método, etc.)',
    `type` ENUM('class', 'interface', 'trait', 'function', 'method', 'property', 'constant') NOT NULL COMMENT 'Tipo de chunk',
    `visibility` ENUM('public', 'private', 'protected', 'global') NULL COMMENT 'Visibilidad del elemento',
    `is_static` BOOLEAN DEFAULT FALSE COMMENT 'Si el elemento es estático',
    `is_abstract` BOOLEAN DEFAULT FALSE COMMENT 'Si el elemento es abstracto',
    `start_line` INT NULL COMMENT 'Línea de inicio en el archivo original',
    `end_line` INT NULL COMMENT 'Línea final en el archivo original',
    `signature` TEXT NULL COMMENT 'Firma completa de la función/método (parámetros, tipos)',
    `docblock` TEXT NULL COMMENT 'Comentario PHPDoc asociado',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`file_id`) REFERENCES `files`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`parent_chunk_id`) REFERENCES `chunks`(`id`) ON DELETE CASCADE,
    INDEX `idx_file_id` (`file_id`),
    INDEX `idx_type` (`type`),
    INDEX `idx_name` (`name`),
    INDEX `idx_parent` (`parent_chunk_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Fragmentos de código extraídos de archivos PHP';

-- ============================================
-- TABLA: chunk_relations
-- ============================================
-- Almacena las relaciones entre chunks (el grafo de dependencias).
-- Esta tabla es el corazón del grafo de conocimiento de Lumina.
-- Permite consultas recursivas para rastrear dependencias.
-- ============================================

CREATE TABLE IF NOT EXISTS `chunk_relations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `source_chunk_id` INT NOT NULL COMMENT 'Chunk que origina la relación (quién hace la acción)',
    `target_chunk_id` INT NOT NULL COMMENT 'Chunk destino de la relación (quién recibe la acción)',
    `relation_type` ENUM(
        'calls',            -- Llama a una función/método
        'imports',          -- use statement
        'extends',          -- Extiende una clase
        'implements',       -- Implementa una interfaz
        'uses_trait',       -- Usa un trait
        'instantiates',     -- Crea una instancia (new)
        'type_hints',       -- Usa como type hint
        'returns',          -- Tipo de retorno
        'contains'          -- Contiene (clase -> método)
    ) NOT NULL COMMENT 'Tipo de relación entre chunks',
    `context` VARCHAR(500) NULL COMMENT 'Línea o contexto donde ocurre la relación',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`source_chunk_id`) REFERENCES `chunks`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`target_chunk_id`) REFERENCES `chunks`(`id`) ON DELETE CASCADE,
    INDEX `idx_source` (`source_chunk_id`),
    INDEX `idx_target` (`target_chunk_id`),
    INDEX `idx_relation_type` (`relation_type`),
    INDEX `idx_source_type` (`source_chunk_id`, `relation_type`),
    INDEX `idx_target_type` (`target_chunk_id`, `relation_type`),
    UNIQUE KEY `unique_relation` (`source_chunk_id`, `target_chunk_id`, `relation_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Grafo de dependencias entre chunks de código';

-- ============================================
-- TABLA: dossiers
-- ============================================
-- Almacena el Dossier generado por archivo (las 5 preguntas).
-- Cada dossier es un análisis semántico completo de un archivo.
-- Puede ser generado por IA o manualmente.
-- ============================================

CREATE TABLE IF NOT EXISTS `dossiers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `file_id` INT NOT NULL UNIQUE COMMENT 'Archivo al que pertenece este dossier',
    `where_is` TEXT NOT NULL COMMENT 'Pregunta 1: ¿Dónde está? (ruta y ubicación física)',
    `interacts_with` JSON NULL COMMENT 'Pregunta 2: ¿Con qué interactúa? (árbol de dependencias en formato JSON)',
    `what_does` TEXT NOT NULL COMMENT 'Pregunta 3: ¿Qué hace? (descripción a nivel humano/negocio)',
    `why_exists` TEXT NULL COMMENT 'Pregunta 3b: ¿Para qué? (necesidad del proyecto/razón de existir)',
    `how_does` TEXT NOT NULL COMMENT 'Pregunta 4: ¿Cómo lo hace? (descripción a nivel código)',
    `failure_causes` TEXT NULL COMMENT 'Pregunta 5: Causas de fallo conocidas (historial de debugging)',
    `ai_generated` BOOLEAN DEFAULT FALSE COMMENT 'Si fue generado automáticamente por IA',
    `confidence_score` DECIMAL(3,2) NULL COMMENT 'Qué tan confiable es el análisis (0.00 - 1.00)',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`file_id`) REFERENCES `files`(`id`) ON DELETE CASCADE,
    INDEX `idx_file_id` (`file_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Dossiers de análisis semántico por archivo';

-- ============================================
-- TABLA: analysis_sessions
-- ============================================
-- Registra cada ejecución del análisis (para saber cuándo se actualizó por última vez).
-- Permite tracking del progreso y auditoría de análisis previos.
-- ============================================

CREATE TABLE IF NOT EXISTS `analysis_sessions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `started_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Cuándo inició el análisis',
    `finished_at` TIMESTAMP NULL COMMENT 'Cuándo finalizó el análisis',
    `files_analyzed` INT DEFAULT 0 COMMENT 'Cantidad de archivos procesados',
    `chunks_extracted` INT DEFAULT 0 COMMENT 'Cantidad de chunks extraídos',
    `relations_found` INT DEFAULT 0 COMMENT 'Cantidad de relaciones encontradas',
    `dossiers_generated` INT DEFAULT 0 COMMENT 'Cantidad de dossiers generados',
    `status` ENUM('running', 'completed', 'failed') DEFAULT 'running' COMMENT 'Estado actual de la sesión',
    `error_message` TEXT NULL COMMENT 'Mensaje de error si el análisis falló',
    INDEX `idx_status` (`status`),
    INDEX `idx_started_at` (`started_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de sesiones de análisis de código';

-- ============================================
-- VISTA: chunk_hierarchy
-- ============================================
-- Vista útil para consultar la jerarquía completa de chunks.
-- Muestra la ruta completa desde el archivo hasta el chunk específico.
-- ============================================

CREATE OR REPLACE VIEW `chunk_hierarchy` AS
SELECT 
    c.id AS chunk_id,
    c.name AS chunk_name,
    c.type AS chunk_type,
    f.path AS file_path,
    f.name AS file_name,
    f.namespace,
    CASE 
        WHEN pc.name IS NOT NULL THEN CONCAT(pc.name, ' -> ', c.name)
        ELSE c.name
    END AS full_path
FROM chunks c
JOIN files f ON c.file_id = f.id
LEFT JOIN chunks pc ON c.parent_chunk_id = pc.id;

-- ============================================
-- VISTA: dependency_graph
-- ============================================
-- Vista para consultar el grafo de dependencias de forma legible.
-- Muestra relaciones source -> target con sus tipos.
-- ============================================

CREATE OR REPLACE VIEW `dependency_graph` AS
SELECT 
    sc.name AS source_name,
    sc.type AS source_type,
    sf.path AS source_file,
    cr.relation_type,
    tc.name AS target_name,
    tc.type AS target_type,
    tf.path AS target_file,
    cr.context
FROM chunk_relations cr
JOIN chunks sc ON cr.source_chunk_id = sc.id
JOIN files sf ON sc.file_id = sf.id
JOIN chunks tc ON cr.target_chunk_id = tc.id
JOIN files tf ON tc.file_id = tf.id;

-- ============================================
-- PROCEDIMIENTO: get_dependencies_recursive
-- ============================================
-- Procedimiento almacenado para obtener dependencias recursivas de un chunk.
-- Útil para trazabilidad completa de dependencias.
-- ============================================

DELIMITER //

CREATE PROCEDURE IF NOT EXISTS `get_chunk_dependencies`(
    IN p_chunk_id INT,
    IN p_max_depth INT
)
BEGIN
    DECLARE v_depth INT DEFAULT 0;
    
    -- Tabla temporal para resultados
    CREATE TEMPORARY TABLE IF NOT EXISTS temp_dependencies (
        chunk_id INT,
        related_chunk_id INT,
        relation_type VARCHAR(50),
        depth INT,
        path VARCHAR(1000),
        PRIMARY KEY (chunk_id, related_chunk_id, relation_type)
    );
    
    -- Insertar dependencias directas
    INSERT IGNORE INTO temp_dependencies
    SELECT 
        source_chunk_id,
        target_chunk_id,
        relation_type,
        0,
        CONCAT(source_chunk_id, ' -> ', target_chunk_id)
    FROM chunk_relations
    WHERE source_chunk_id = p_chunk_id;
    
    -- Bucle para dependencias recursivas (simplificado, max 10 niveles)
    WHILE v_depth < p_max_depth AND v_depth < 10 DO
        INSERT IGNORE INTO temp_dependencies
        SELECT 
            td.chunk_id,
            cr.target_chunk_id,
            cr.relation_type,
            v_depth + 1,
            CONCAT(td.path, ' -> ', cr.target_chunk_id)
        FROM temp_dependencies td
        JOIN chunk_relations cr ON td.related_chunk_id = cr.source_chunk_id
        WHERE td.depth = v_depth;
        
        SET v_depth = v_depth + 1;
        
        -- Si no hay más inserciones, salir
        IF ROW_COUNT() = 0 THEN
            LEAVE;
        END IF;
    END WHILE;
    
    -- Retornar resultados con información detallada
    SELECT 
        td.chunk_id,
        sc.name AS source_name,
        td.related_chunk_id,
        tc.name AS target_name,
        td.relation_type,
        td.depth,
        td.path
    FROM temp_dependencies td
    JOIN chunks sc ON td.chunk_id = sc.id
    JOIN chunks tc ON td.related_chunk_id = tc.id
    ORDER BY td.depth, td.relation_type;
    
    DROP TEMPORARY TABLE temp_dependencies;
END //

DELIMITER ;

-- ============================================
-- TRIGGER: update_analysis_session_on_chunk_insert
-- ============================================
-- Trigger para actualizar contadores de sesión automáticamente.
-- ============================================

DELIMITER //

CREATE TRIGGER IF NOT EXISTS `after_chunk_insert`
AFTER INSERT ON chunks
FOR EACH ROW
BEGIN
    -- Actualizar contador de chunks en la sesión activa más reciente
    UPDATE analysis_sessions 
    SET chunks_extracted = chunks_extracted + 1
    WHERE status = 'running'
    ORDER BY started_at DESC
    LIMIT 1;
END //

DELIMITER ;

-- ============================================
-- DATOS DE PRUEBA (OPCIONAL)
-- ============================================
-- Descomentar para insertar datos de ejemplo

/*
INSERT INTO analysis_sessions (status) VALUES ('running');

INSERT INTO files (path, name, namespace, type) VALUES
('src/Auth/AuthService.php', 'AuthService.php', 'App\\Auth', 'class'),
('src/User/User.php', 'User.php', 'App\\User', 'class'),
('src/Database/Connection.php', 'Connection.php', 'App\\Database', 'class');

INSERT INTO chunks (file_id, name, type, visibility, start_line, end_line) VALUES
(1, 'AuthService', 'class', 'public', 10, 150),
(1, 'login', 'method', 'public', 25, 60),
(1, 'logout', 'method', 'public', 65, 90),
(2, 'User', 'class', 'public', 15, 100),
(3, 'Connection', 'class', 'public', 20, 200);

INSERT INTO chunk_relations (source_chunk_id, target_chunk_id, relation_type) VALUES
(2, 4, 'instantiates'),  -- login instantiates User
(2, 5, 'calls'),         -- login calls Connection
(1, 4, 'contains'),      -- AuthService contains User (referencia)
(1, 5, 'contains');      -- AuthService contains Connection (referencia)
*/

-- ============================================
-- RESTAURAR CONFIGURACIÓN ORIGINAL
-- ============================================

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

COMMIT;

-- ============================================
-- FIN DEL SCRIPT DE BASE DE DATOS LUMINA
-- ============================================
