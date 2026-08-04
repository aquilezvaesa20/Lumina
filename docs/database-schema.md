# Esquema de Base de Datos - Lumina

## Descripción General

Lumina utiliza MySQL/MariaDB para almacenar el grafo de conocimiento generado a partir del análisis de codebases PHP. El esquema está diseñado para soportar consultas recursivas de dependencias y almacenamiento de dossiers semánticos.

## Tablas Principales

### 1. `files`
Almacena información de cada archivo PHP analizado.

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | INT AUTO_INCREMENT | Primary Key |
| path | VARCHAR(500) UNIQUE | Ruta relativa del archivo desde el root del proyecto |
| name | VARCHAR(255) | Nombre del archivo con extensión |
| namespace | VARCHAR(255) NULL | Namespace PHP si aplica |
| type | ENUM | Tipo principal: 'class', 'interface', 'trait', 'function_file', 'mixed' |
| created_at | TIMESTAMP | Fecha de creación |
| updated_at | TIMESTAMP | Última actualización |

**Índices:** `idx_path`, `idx_namespace`

---

### 2. `chunks`
Almacena cada fragmento de código: clases, funciones, métodos, propiedades.

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | INT AUTO_INCREMENT | Primary Key |
| file_id | INT NOT NULL | Foreign Key → files(id) |
| parent_chunk_id | INT NULL | Foreign Key → chunks(id) (para jerarquía) |
| name | VARCHAR(255) | Nombre del elemento |
| type | ENUM | 'class', 'interface', 'trait', 'function', 'method', 'property', 'constant' |
| visibility | ENUM NULL | 'public', 'private', 'protected', 'global' |
| is_static | BOOLEAN | Si es estático |
| is_abstract | BOOLEAN | Si es abstracto |
| start_line | INT | Línea de inicio en el archivo |
| end_line | INT | Línea final en el archivo |
| signature | TEXT NULL | Firma completa (parámetros, tipos) |
| docblock | TEXT NULL | Comentario PHPDoc |
| created_at | TIMESTAMP | Fecha de creación |
| updated_at | TIMESTAMP | Última actualización |

**Índices:** `idx_file_id`, `idx_type`, `idx_name`, `idx_parent`

**Relaciones:**
- `file_id` → `files(id)` ON DELETE CASCADE
- `parent_chunk_id` → `chunks(id)` ON DELETE CASCADE (autoreferencia)

---

### 3. `chunk_relations` ⭐
El corazón del grafo de conocimiento. Almacena las relaciones entre chunks.

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | INT AUTO_INCREMENT | Primary Key |
| source_chunk_id | INT NOT NULL | FK → chunks(id) (quién hace la acción) |
| target_chunk_id | INT NOT NULL | FK → chunks(id) (quién recibe la acción) |
| relation_type | ENUM | Ver tipos abajo |
| context | VARCHAR(500) NULL | Línea o contexto donde ocurre |
| created_at | TIMESTAMP | Fecha de creación |

**Tipos de relación:**
- `calls` - Llama a una función/método
- `imports` - use statement
- `extends` - Extiende una clase
- `implements` - Implementa una interfaz
- `uses_trait` - Usa un trait
- `instantiates` - Crea una instancia (new)
- `type_hints` - Usa como type hint
- `returns` - Tipo de retorno
- `contains` - Contiene (clase → método)

**Índices:** `idx_source`, `idx_target`, `idx_relation_type`, `idx_source_type`, `idx_target_type`

**Unique:** `(source_chunk_id, target_chunk_id, relation_type)`

---

### 4. `dossiers`
Almacena el Dossier generado por archivo (las 5 preguntas).

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | INT AUTO_INCREMENT | Primary Key |
| file_id | INT NOT NULL UNIQUE | FK → files(id) |
| where_is | TEXT | Pregunta 1: ¿Dónde está? |
| interacts_with | JSON NULL | Pregunta 2: ¿Con qué interactúa? |
| what_does | TEXT | Pregunta 3: ¿Qué hace? (nivel humano) |
| why_exists | TEXT NULL | Pregunta 3b: ¿Para qué existe? |
| how_does | TEXT | Pregunta 4: ¿Cómo lo hace? (nivel código) |
| failure_causes | TEXT NULL | Pregunta 5: Causas de fallo conocidas |
| ai_generated | BOOLEAN | Si fue generado por IA |
| confidence_score | DECIMAL(3,2) NULL | Confiabilidad (0.00 - 1.00) |
| created_at | TIMESTAMP | Fecha de creación |
| updated_at | TIMESTAMP | Última actualización |

---

### 5. `analysis_sessions`
Registra cada ejecución del análisis.

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | INT AUTO_INCREMENT | Primary Key |
| started_at | TIMESTAMP | Cuándo inició |
| finished_at | TIMESTAMP NULL | Cuándo finalizó |
| files_analyzed | INT DEFAULT 0 | Archivos procesados |
| chunks_extracted | INT DEFAULT 0 | Chunks extraídos |
| relations_found | INT DEFAULT 0 | Relaciones encontradas |
| dossiers_generated | INT DEFAULT 0 | Dossiers generados |
| status | ENUM | 'running', 'completed', 'failed' |
| error_message | TEXT NULL | Mensaje de error si falló |

---

## Vistas

### `chunk_hierarchy`
Muestra la jerarquía completa de chunks con su ruta desde el archivo.

```sql
SELECT * FROM chunk_hierarchy WHERE file_path = 'src/Auth/AuthService.php';
```

### `dependency_graph`
Muestra el grafo de dependencias de forma legible.

```sql
SELECT * FROM dependency_graph WHERE source_name = 'login';
```

---

## Procedimientos Almacenados

### `get_chunk_dependencies(p_chunk_id, p_max_depth)`
Obtiene dependencias recursivas de un chunk hasta una profundidad máxima.

```sql
CALL get_chunk_dependencies(42, 5);  -- Chunk 42, hasta 5 niveles de profundidad
```

---

## Triggers

### `after_chunk_insert`
Actualiza automáticamente el contador de `chunks_extracted` en la sesión de análisis activa.

---

## Ejemplos de Consultas

### Obtener todas las dependencias de un archivo
```sql
SELECT 
    f.path AS file_path,
    c.name AS chunk_name,
    cr.relation_type,
    tc.name AS target_name,
    tf.path AS target_file
FROM files f
JOIN chunks c ON f.id = c.file_id
JOIN chunk_relations cr ON c.id = cr.source_chunk_id
JOIN chunks tc ON cr.target_chunk_id = tc.id
JOIN files tf ON tc.file_id = tf.id
WHERE f.path = 'src/Auth/AuthService.php';
```

### Obtener el dossier de un archivo con sus dependencias
```sql
SELECT 
    d.*,
    d.interacts_with->>'$.direct' AS direct_deps,
    d.interacts_with->>'$.transitive' AS transitive_deps
FROM dossiers d
JOIN files f ON d.file_id = f.id
WHERE f.path = 'src/Auth/AuthService.php';
```

### Contar dependencias por tipo
```sql
SELECT 
    relation_type,
    COUNT(*) AS count
FROM chunk_relations
GROUP BY relation_type
ORDER BY count DESC;
```

### Buscar todos los que llaman a una función específica
```sql
SELECT 
    sc.name AS caller,
    sf.path AS caller_file,
    cr.context
FROM chunk_relations cr
JOIN chunks sc ON cr.source_chunk_id = sc.id
JOIN files sf ON sc.file_id = sf.id
JOIN chunks tc ON cr.target_chunk_id = tc.id
WHERE tc.name = 'login' AND cr.relation_type = 'calls';
```

---

## Consideraciones de Rendimiento

### Índices Críticos
Los índices en `chunk_relations` son esenciales para consultas de grafo:
- `idx_source` - Para encontrar todas las salidas de un chunk
- `idx_target` - Para encontrar todas las entradas a un chunk
- `idx_source_type` y `idx_target_type` - Para filtrar por tipo de relación

### Consultas Recursivas
Para grafos profundos, usar el procedimiento `get_chunk_dependencies()` en lugar de JOINs manuales.

### Particionamiento (Futuro)
Para codebases muy grandes (>10,000 archivos), considerar particionamiento por `project_id` o por fecha.

---

## Migración desde Esquema Existente

El repositorio ya contiene un archivo `database/schema/adbbmis1_Cloud.sql` con tablas existentes para un sistema de chat y gestión de proyectos. Las tablas de Lumina son **completamente nuevas** y no entran en conflicto con las existentes.

**Tablas existentes relacionadas:**
- `SourceChunks` - Similar a la nueva tabla `chunks`, pero con estructura diferente
- `ChunkEmbeddings` - Para vectores de embedding de chunks
- `ProjectSources` - Para fuentes de proyectos

**Diferencias clave:**
| Característica | SourceChunks (existente) | chunks (nueva) |
|---------------|-------------------------|----------------|
| Enfoque | Almacena contenido completo | Almacena metadatos y firma |
| Jerarquía | plana con parent_name | jerárquica con parent_chunk_id FK |
| Relaciones | No tiene tabla dedicada | chunk_relations dedicada |
| Dossiers | No existe | dossiers con 5 preguntas |

**Recomendación:** Usar el nuevo esquema `lumina_schema.sql` para el motor Lumina. Las tablas existentes pueden coexistir para otras funcionalidades del sistema.

---

## Archivos del Proyecto

| Archivo | Descripción |
|---------|-------------|
| `lumina_schema.sql` | Script SQL completo con todas las tablas, vistas, procedimientos y triggers |
| `docs/database-schema.md` | Este documento - documentación del esquema |

---

## Motor de Base de Datos

- **Motor:** MySQL 8.0+ / MariaDB 10.3+
- **Charset:** utf8mb4
- **Collation:** utf8mb4_unicode_ci
- **Engine:** InnoDB

---

## Próximas Mejoras (Backlog)

1. [ ] Agregar `project_id` a las tablas para multi-proyecto
2. [ ] Tabla `analysis_history` para tracking de cambios entre sesiones
3. [ ] Índices full-text en `docblock` y `signature` para búsqueda
4. [ ] Tabla `code_smells` para almacenar problemas detectados
5. [ ] Vista materializada para `dependency_graph` en codebases grandes
