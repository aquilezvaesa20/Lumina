-- ============================================================
-- LUMINA CORE MIGRATION - 001
-- ============================================================
-- Motor de Comprensión de Código PHP
-- MySQL 8.0+ | utf8mb4 | InnoDB
-- 
-- Esta migración actualiza el esquema existente para soportar
-- el análisis completo de codebases PHP con nikic/php-parser.
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- ============================================================
-- TAREA 2: ACTUALIZAR TABLA SourceChunks
-- ============================================================
-- Agregar campos específicos para análisis PHP con nikic/php-parser

-- Verificar y agregar columna visibility
SET @dbname = DATABASE();
SET @tablename = 'SourceChunks';
SET @columnname = 'visibility';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      TABLE_SCHEMA = @dbname
      AND TABLE_NAME = @tablename
      AND COLUMN_NAME = @columnname
  ) > 0,
  "SELECT 1",
  CONCAT("ALTER TABLE `", @tablename, "` ADD COLUMN `visibility` ENUM('public','private','protected','global') NULL DEFAULT NULL COMMENT 'Visibilidad del chunk (métodos, propiedades)' AFTER `signature`")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Verificar y agregar columna is_static
SET @columnname = 'is_static';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      TABLE_SCHEMA = @dbname
      AND TABLE_NAME = @tablename
      AND COLUMN_NAME = @columnname
  ) > 0,
  "SELECT 1",
  CONCAT("ALTER TABLE `", @tablename, "` ADD COLUMN `is_static` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = es estático' AFTER `visibility`")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Verificar y agregar columna is_abstract
SET @columnname = 'is_abstract';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      TABLE_SCHEMA = @dbname
      AND TABLE_NAME = @tablename
      AND COLUMN_NAME = @columnname
  ) > 0,
  "SELECT 1",
  CONCAT("ALTER TABLE `", @tablename, "` ADD COLUMN `is_abstract` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = es abstracto' AFTER `is_static`")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Verificar y agregar columna is_final
SET @columnname = 'is_final';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      TABLE_SCHEMA = @dbname
      AND TABLE_NAME = @tablename
      AND COLUMN_NAME = @columnname
  ) > 0,
  "SELECT 1",
  CONCAT("ALTER TABLE `", @tablename, "` ADD COLUMN `is_final` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = es final' AFTER `is_abstract`")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Verificar y agregar columna namespace
SET @columnname = 'namespace';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      TABLE_SCHEMA = @dbname
      AND TABLE_NAME = @tablename
      AND COLUMN_NAME = @columnname
  ) > 0,
  "SELECT 1",
  CONCAT("ALTER TABLE `", @tablename, "` ADD COLUMN `namespace` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Namespace PHP donde vive este chunk' AFTER `is_final`")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Verificar y agregar columna docblock
SET @columnname = 'docblock';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      TABLE_SCHEMA = @dbname
      AND TABLE_NAME = @tablename
      AND COLUMN_NAME = @columnname
  ) > 0,
  "SELECT 1",
  CONCAT("ALTER TABLE `", @tablename, "` ADD COLUMN `docblock` TEXT NULL DEFAULT NULL COMMENT 'PHPDoc completo del chunk' AFTER `namespace`")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Verificar y agregar columna return_type
SET @columnname = 'return_type';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      TABLE_SCHEMA = @dbname
      AND TABLE_NAME = @tablename
      AND COLUMN_NAME = @columnname
  ) > 0,
  "SELECT 1",
  CONCAT("ALTER TABLE `", @tablename, "` ADD COLUMN `return_type` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Tipo de retorno declarado' AFTER `docblock`")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Verificar y agregar columna parameters_json
SET @columnname = 'parameters_json';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      TABLE_SCHEMA = @dbname
      AND TABLE_NAME = @tablename
      AND COLUMN_NAME = @columnname
  ) > 0,
  "SELECT 1",
  CONCAT("ALTER TABLE `", @tablename, "` ADD COLUMN `parameters_json` JSON NULL DEFAULT NULL COMMENT 'Lista de parámetros: [{\"name\":\"$id\",\"type\":\"int\",\"default\":null}]' AFTER `return_type`")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Índices adicionales para SourceChunks (verificar existencia primero)
SET @tablename = 'SourceChunks';

SET @indexname = 'idx_chunks_namespace';
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
   WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND INDEX_NAME = @indexname) > 0,
  "SELECT 1",
  CONCAT("ALTER TABLE `", @tablename, "` ADD INDEX `", @indexname, "` (`namespace`)")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @indexname = 'idx_chunks_visibility';
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
   WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND INDEX_NAME = @indexname) > 0,
  "SELECT 1",
  CONCAT("ALTER TABLE `", @tablename, "` ADD INDEX `", @indexname, "` (`visibility`)")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @indexname = 'idx_chunks_parent_name';
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
   WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND INDEX_NAME = @indexname) > 0,
  "SELECT 1",
  CONCAT("ALTER TABLE `", @tablename, "` ADD INDEX `", @indexname, "` (`parent_name`(100))")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @indexname = 'idx_chunks_project_namespace';
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
   WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND INDEX_NAME = @indexname) > 0,
  "SELECT 1",
  CONCAT("ALTER TABLE `", @tablename, "` ADD INDEX `", @indexname, "` (`project_id_`, `namespace`)")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @indexname = 'idx_chunks_type_name';
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
   WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND INDEX_NAME = @indexname) > 0,
  "SELECT 1",
  CONCAT("ALTER TABLE `", @tablename, "` ADD INDEX `", @indexname, "` (`chunk_type`, `name`(100))")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- ============================================================
-- TAREA 3: ACTUALIZAR TABLA ProjectSources
-- ============================================================
-- Agregar clasificación de archivo para el chunker

-- Verificar y agregar columna file_type
SET @tablename = 'ProjectSources';
SET @columnname = 'file_type';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      TABLE_SCHEMA = @dbname
      AND TABLE_NAME = @tablename
      AND COLUMN_NAME = @columnname
  ) > 0,
  "SELECT 1",
  CONCAT("ALTER TABLE `", @tablename, "` ADD COLUMN `file_type` ENUM('class','interface','trait','enum','function_file','config','template','mixed','unknown') NULL DEFAULT 'unknown' COMMENT 'Tipo principal de declaración en el archivo' AFTER `language`")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Verificar y agregar columna namespace
SET @columnname = 'namespace';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      TABLE_SCHEMA = @dbname
      AND TABLE_NAME = @tablename
      AND COLUMN_NAME = @columnname
  ) > 0,
  "SELECT 1",
  CONCAT("ALTER TABLE `", @tablename, "` ADD COLUMN `namespace` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Namespace principal del archivo' AFTER `file_type`")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Verificar y agregar columna chunk_count
SET @columnname = 'chunk_count';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      TABLE_SCHEMA = @dbname
      AND TABLE_NAME = @tablename
      AND COLUMN_NAME = @columnname
  ) > 0,
  "SELECT 1",
  CONCAT("ALTER TABLE `", @tablename, "` ADD COLUMN `chunk_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Cantidad de chunks extraídos (cache)' AFTER `namespace`")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Verificar y agregar columna relation_count
SET @columnname = 'relation_count';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      TABLE_SCHEMA = @dbname
      AND TABLE_NAME = @tablename
      AND COLUMN_NAME = @columnname
  ) > 0,
  "SELECT 1",
  CONCAT("ALTER TABLE `", @tablename, "` ADD COLUMN `relation_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Cantidad de relaciones detectadas (cache)' AFTER `chunk_count`")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Verificar y agregar columna has_dossier
SET @columnname = 'has_dossier';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      TABLE_SCHEMA = @dbname
      AND TABLE_NAME = @tablename
      AND COLUMN_NAME = @columnname
  ) > 0,
  "SELECT 1",
  CONCAT("ALTER TABLE `", @tablename, "` ADD COLUMN `has_dossier` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = ya tiene dossier generado' AFTER `relation_count`")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Verificar y agregar columna last_analyzed_at
SET @columnname = 'last_analyzed_at';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      TABLE_SCHEMA = @dbname
      AND TABLE_NAME = @tablename
      AND COLUMN_NAME = @columnname
  ) > 0,
  "SELECT 1",
  CONCAT("ALTER TABLE `", @tablename, "` ADD COLUMN `last_analyzed_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Última vez que se analizó este archivo' AFTER `has_dossier`")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Índices adicionales para ProjectSources (verificar existencia primero)
SET @tablename = 'ProjectSources';

SET @indexname = 'idx_ps_file_type';
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
   WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND INDEX_NAME = @indexname) > 0,
  "SELECT 1",
  CONCAT("ALTER TABLE `", @tablename, "` ADD INDEX `", @indexname, "` (`file_type`)")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @indexname = 'idx_ps_namespace';
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
   WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND INDEX_NAME = @indexname) > 0,
  "SELECT 1",
  CONCAT("ALTER TABLE `", @tablename, "` ADD INDEX `", @indexname, "` (`namespace`)")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @indexname = 'idx_ps_has_dossier';
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
   WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND INDEX_NAME = @indexname) > 0,
  "SELECT 1",
  CONCAT("ALTER TABLE `", @tablename, "` ADD INDEX `", @indexname, "` (`has_dossier`)")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @indexname = 'idx_ps_project_status';
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
   WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND INDEX_NAME = @indexname) > 0,
  "SELECT 1",
  CONCAT("ALTER TABLE `", @tablename, "` ADD INDEX `", @indexname, "` (`project_id_`, `status`)")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- ============================================================
-- TAREA 4: CREAR TABLA ChunkRelations
-- ============================================================
-- Grafo de dependencias entre chunks de código

CREATE TABLE IF NOT EXISTS `ChunkRelations` (
  `id_` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `source_chunk_id_` BIGINT UNSIGNED NOT NULL
    COMMENT 'Chunk que ORIGINA la relación (el que llama, extiende, importa)',
  `target_chunk_id_` BIGINT UNSIGNED NOT NULL
    COMMENT 'Chunk DESTINO de la relación (el llamado, extendido, importado)',
  `project_id_` INT NOT NULL
    COMMENT 'Desnormalizado para consultas rápidas por proyecto',
  `relation_type` ENUM(
    'calls',           -- Llama a una función/método
    'imports',         -- use statement / require / include
    'extends',         -- Extiende una clase
    'implements',      -- Implementa una interfaz
    'uses_trait',      -- Usa un trait
    'instantiates',    -- Crea instancia con new
    'type_hints',      -- Usa como type hint en parámetros
    'returns',         -- Usa como tipo de retorno
    'throws',          -- Lanza una excepción de ese tipo
    'contains',        -- Contención: clase → método, archivo → clase
    'references',      -- Referencia general (constante, propiedad)
    'overrides'        -- Sobrescribe método de padre
  ) NOT NULL,
  `context` VARCHAR(500) NULL
    COMMENT 'Línea de código o contexto donde ocurre la relación',
  `context_line` INT UNSIGNED NULL
    COMMENT 'Número de línea donde ocurre',
  `is_confirmed` TINYINT(1) NOT NULL DEFAULT 1
    COMMENT '1 = confirmada por parser, 0 = inferida por IA',
  `meta` JSON DEFAULT NULL
    COMMENT 'Info extra: argumentos, condiciones, etc.',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_`),
  UNIQUE KEY `uq_chunk_relation` (`source_chunk_id_`, `target_chunk_id_`, `relation_type`),
  KEY `idx_cr_source` (`source_chunk_id_`),
  KEY `idx_cr_target` (`target_chunk_id_`),
  KEY `idx_cr_project` (`project_id_`),
  KEY `idx_cr_type` (`relation_type`),
  KEY `idx_cr_project_type` (`project_id_`, `relation_type`),
  KEY `idx_cr_confirmed` (`is_confirmed`),
  CONSTRAINT `fk_cr_source_chunk`
    FOREIGN KEY (`source_chunk_id_`) REFERENCES `SourceChunks` (`id_`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_cr_target_chunk`
    FOREIGN KEY (`target_chunk_id_`) REFERENCES `SourceChunks` (`id_`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_cr_project`
    FOREIGN KEY (`project_id_`) REFERENCES `Projects` (`id_`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
COMMENT='Lumina: Grafo de dependencias entre chunks de código';

-- ============================================================
-- TAREA 5: CREAR TABLA FileDossiers
-- ============================================================
-- Dossiers de comprensión por archivo (las 5 preguntas de Lumina)

CREATE TABLE IF NOT EXISTS `FileDossiers` (
  `id_` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `source_id_` BIGINT UNSIGNED NOT NULL
    COMMENT 'Archivo fuente (ProjectSources) al que pertenece',
  `project_id_` INT NOT NULL
    COMMENT 'Desnormalizado para consultas rápidas',
  `where_is` TEXT NOT NULL
    COMMENT 'P1: ¿Dónde está? Ruta, namespace, contexto en el proyecto',
  `interacts_with` JSON NULL
    COMMENT 'P2: ¿Con qué interactúa? Árbol de dependencias estructurado',
  `what_does` TEXT NOT NULL
    COMMENT 'P3: ¿Qué hace? Descripción a nivel humano',
  `why_exists` TEXT NULL
    COMMENT 'P3b: ¿Para qué existe? Necesidad del proyecto que cubre',
  `how_does` TEXT NOT NULL
    COMMENT 'P4: ¿Cómo lo hace? Descripción técnica del funcionamiento',
  `failure_causes` TEXT NULL
    COMMENT 'P5: Causas de fallo conocidas, bugs, debugging histórico',
  `ai_generated` TINYINT(1) NOT NULL DEFAULT 0
    COMMENT '1 = Generado por IA, 0 = Escrito por humano',
  `confidence_score` DECIMAL(3,2) NULL
    COMMENT 'Confiabilidad del análisis: 0.00 a 1.00',
  `generated_by` VARCHAR(120) NULL
    COMMENT 'Modelo IA o usuario que lo generó',
  `version` INT UNSIGNED NOT NULL DEFAULT 1
    COMMENT 'Versión del dossier (se incrementa al regenerar)',
  `meta` JSON DEFAULT NULL
    COMMENT 'Info extra: tokens usados, tiempo de generación, etc.',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_`),
  UNIQUE KEY `uq_dossier_source` (`source_id_`),
  KEY `idx_fd_project` (`project_id_`),
  KEY `idx_fd_ai` (`ai_generated`),
  KEY `idx_fd_confidence` (`confidence_score`),
  CONSTRAINT `fk_fd_source`
    FOREIGN KEY (`source_id_`) REFERENCES `ProjectSources` (`id_`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_fd_project`
    FOREIGN KEY (`project_id_`) REFERENCES `Projects` (`id_`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
COMMENT='Lumina: Dossiers de comprensión de código (las 5 preguntas)';

-- ============================================================
-- TAREA 6: CREAR TABLA AnalysisSessions
-- ============================================================
-- Registro de cada ejecución del pipeline de análisis de Lumina

CREATE TABLE IF NOT EXISTS `AnalysisSessions` (
  `id_` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id_` INT NOT NULL
    COMMENT 'Proyecto analizado',
  `triggered_by` ENUM('manual','cron','webhook','ai_request','file_change')
    NOT NULL DEFAULT 'manual'
    COMMENT 'Origen del análisis',
  `started_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `finished_at` TIMESTAMP NULL DEFAULT NULL,
  `files_analyzed` INT UNSIGNED NOT NULL DEFAULT 0,
  `chunks_extracted` INT UNSIGNED NOT NULL DEFAULT 0,
  `relations_found` INT UNSIGNED NOT NULL DEFAULT 0,
  `dossiers_generated` INT UNSIGNED NOT NULL DEFAULT 0,
  `dossiers_updated` INT UNSIGNED NOT NULL DEFAULT 0,
  `errors_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `status` ENUM('running','completed','failed','cancelled')
    NOT NULL DEFAULT 'running',
  `error_message` TEXT NULL,
  `config_json` JSON DEFAULT NULL
    COMMENT 'Parámetros de configuración usados en este análisis',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_`),
  KEY `idx_as_project` (`project_id_`),
  KEY `idx_as_status` (`status`),
  KEY `idx_as_started` (`started_at`),
  KEY `idx_as_project_status` (`project_id_`, `status`),
  CONSTRAINT `fk_as_project`
    FOREIGN KEY (`project_id_`) REFERENCES `Projects` (`id_`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
COMMENT='Lumina: Registro de cada ejecución del análisis';

-- ============================================================
-- ÍNDICES ADICIONALES PARA CONSULTAS DE GRAFO
-- ============================================================

-- Índices compuestos para consultas eficientes del grafo
-- Índices compuestos para consultas eficientes del grafo (verificar existencia primero)
SET @tablename = 'ChunkRelations';

SET @indexname = 'idx_cr_source_type';
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
   WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND INDEX_NAME = @indexname) > 0,
  "SELECT 1",
  CONCAT("ALTER TABLE `", @tablename, "` ADD INDEX `", @indexname, "` (`source_chunk_id_`, `relation_type`)")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @indexname = 'idx_cr_target_type';
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
   WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND INDEX_NAME = @indexname) > 0,
  "SELECT 1",
  CONCAT("ALTER TABLE `", @tablename, "` ADD INDEX `", @indexname, "` (`target_chunk_id_`, `relation_type`)")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- ============================================================
-- FIN DE LA MIGRACIÓN
-- ============================================================

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
