# Base de Datos - Lumina

## Estructura de Carpetas

```
database/
├── schema/
│   └── adbbmis1_Cloud.sql    # Dump completo actualizado de la base de datos
├── migrations/
│   └── 001_lumina_core.sql   # Migración para actualizar esquema Lumina
└── README.md                  # Este archivo
```

### `schema/` vs `migrations/`

| Carpeta | Propósito | Cuándo usar |
|---------|-----------|-------------|
| `schema/` | Contiene el dump **completo** y actualizado de la base de datos | Después de aplicar migraciones, generar nuevo dump |
| `migrations/` | Scripts incrementales que modifican el esquema existente | Para actualizar una base de datos ya creada sin perder datos |

---

## Cómo Aplicar Migraciones

### Opción 1: Desde línea de comandos

```bash
# Conectarse a MySQL y ejecutar la migración
mysql -u root -p adbbmis1_Cloud < database/migrations/001_lumina_core.sql
```

### Opción 2: Desde cliente MySQL

```sql
-- Seleccionar la base de datos
USE adbbmis1_Cloud;

-- Ejecutar el script
SOURCE /ruta/al/repositorio/database/migrations/001_lumina_core.sql;
```

### Opción 3: Generar nuevo dump después de migrar

```bash
mysqldump -u root -p adbbmis1_Cloud \
  --routines --triggers --events \
  --set-charset --default-character-set=utf8mb4 \
  --single-transaction \
  > database/schema/adbbmis1_Cloud.sql
```

---

## Tablas de Lumina

### Tablas Actualizadas

#### `SourceChunks`
Almacena fragmentos de código PHP (clases, funciones, métodos).

**Nuevos campos:**
- `visibility` - Visibilidad (public/private/protected/global)
- `is_static` - Si es estático
- `is_abstract` - Si es abstracto
- `is_final` - Si es final
- `namespace` - Namespace PHP
- `docblock` - PHPDoc completo
- `return_type` - Tipo de retorno declarado
- `parameters_json` - Lista de parámetros en JSON

#### `ProjectSources`
Archivos fuente del proyecto.

**Nuevos campos:**
- `file_type` - Tipo principal (class/interface/trait/enum/function_file/config/template/mixed/unknown)
- `namespace` - Namespace principal del archivo
- `chunk_count` - Cantidad de chunks extraídos (cache)
- `relation_count` - Cantidad de relaciones detectadas (cache)
- `has_dossier` - Si ya tiene dossier generado
- `last_analyzed_at` - Última vez que se analizó

### Tablas Nuevas

#### `ChunkRelations` ⭐
El corazón del grafo de conocimiento. Almacena dependencias entre chunks.

| Columna | Tipo | Descripción |
|---------|------|-------------|
| `id_` | BIGINT UNSIGNED | Primary Key |
| `source_chunk_id_` | BIGINT UNSIGNED | Chunk que origina la relación |
| `target_chunk_id_` | BIGINT UNSIGNED | Chunk destino de la relación |
| `project_id_` | INT | Desnormalizado para consultas rápidas |
| `relation_type` | ENUM | Tipo de relación (calls, imports, extends, etc.) |
| `context` | VARCHAR(500) | Línea de código donde ocurre |
| `context_line` | INT | Número de línea |
| `is_confirmed` | TINYINT(1) | Confirmada por parser o inferida por IA |
| `meta` | JSON | Info extra: argumentos, condiciones |

**Tipos de relación:**
- `calls` - Llama a una función/método
- `imports` - use statement / require / include
- `extends` - Extiende una clase
- `implements` - Implementa una interfaz
- `uses_trait` - Usa un trait
- `instantiates` - Crea instancia con `new`
- `type_hints` - Usa como type hint en parámetros
- `returns` - Usa como tipo de retorno
- `throws` - Lanza una excepción de ese tipo
- `contains` - Contención: clase → método, archivo → clase
- `references` - Referencia general (constante, propiedad)
- `overrides` - Sobrescribe método de padre

#### `FileDossiers`
Dossiers de comprensión por archivo (las 5 preguntas de Lumina).

| Columna | Tipo | Descripción |
|---------|------|-------------|
| `id_` | BIGINT UNSIGNED | Primary Key |
| `source_id_` | BIGINT UNSIGNED | Archivo fuente (ProjectSources) |
| `project_id_` | INT | Desnormalizado para consultas |
| `where_is` | TEXT | P1: ¿Dónde está? |
| `interacts_with` | JSON | P2: ¿Con qué interactúa? |
| `what_does` | TEXT | P3: ¿Qué hace? (nivel humano) |
| `why_exists` | TEXT | P3b: ¿Para qué existe? |
| `how_does` | TEXT | P4: ¿Cómo lo hace? (nivel técnico) |
| `failure_causes` | TEXT | P5: Causas de fallo conocidas |
| `ai_generated` | TINYINT(1) | Generado por IA |
| `confidence_score` | DECIMAL(3,2) | Confiabilidad (0.00 - 1.00) |
| `generated_by` | VARCHAR(120) | Modelo IA o usuario |
| `version` | INT UNSIGNED | Versión del dossier |
| `meta` | JSON | Info extra |

#### `AnalysisSessions`
Registro de cada ejecución del pipeline de análisis.

| Columna | Tipo | Descripción |
|---------|------|-------------|
| `id_` | BIGINT UNSIGNED | Primary Key |
| `project_id_` | INT | Proyecto analizado |
| `triggered_by` | ENUM | Origen (manual/cron/webhook/ai_request/file_change) |
| `started_at` | TIMESTAMP | Cuándo inició |
| `finished_at` | TIMESTAMP | Cuándo finalizó |
| `files_analyzed` | INT | Archivos procesados |
| `chunks_extracted` | INT | Chunks extraídos |
| `relations_found` | INT | Relaciones encontradas |
| `dossiers_generated` | INT | Dossiers generados |
| `dossiers_updated` | INT | Dossiers actualizados |
| `errors_count` | INT | Errores encontrados |
| `status` | ENUM | running/completed/failed/cancelled |
| `error_message` | TEXT | Mensaje de error si falló |
| `config_json` | JSON | Parámetros de configuración usados |

---

## Diagrama ER

```
┌─────────────┐
│   Projects  │
│   id_       │
│   name      │
│   slug      │
└──────┬──────┘
       │ 1:N
       ├──────────────────────┐
       │                      │
       ▼                      ▼
┌──────────────┐      ┌───────────────────┐
│ProjectSources│      │ AnalysisSessions  │
│   id_        │      │   id_             │
│   project_id_│      │   project_id_     │
│   filename   │      │   triggered_by    │
│   file_type  │      │   status          │
│   namespace  │      └───────────────────┘
│   has_dossier│
└──────┬───────┘
       │ 1:N
       ├────────────────────┐
       │                    │
       ▼                    ▼
┌─────────────┐      ┌──────────────┐
│SourceChunks │      │ FileDossiers │
│   id_       │◄─────│   source_id_ │
│   source_id_│      │   where_is   │
│   chunk_type│      │   what_does  │
│   name      │      │   how_does   │
│   namespace │      │   interacts_with │
│   visibility│      └──────────────┘
└──────┬──────┘
       │
       │ N:N (self-referential via ChunkRelations)
       ▼
┌────────────────┐
│ ChunkRelations │
│   id_          │
│   source_chunk_id_ ──────┐
│   target_chunk_id_ ──┐   │
│   relation_type    │   │   │
│   context          │   │   │
│   is_confirmed     │   │   │
└────────────────────┼───┼───┘
                     │   │
                     └───┘
```

### Relaciones Principales

```
Projects ──1:N──> ProjectSources ──1:N──> SourceChunks
   │                    │                      │
   │                    │                      │
   │                    ▼                      ▼
   │               FileDossiers          ChunkRelations
   │                                   (source ↔ target)
   │
   └──1:N──> AnalysisSessions
```

---

## Convenciones

### Naming

| Elemento | Convención | Ejemplo |
|----------|-----------|---------|
| Tablas | CamelCase | `ChunkRelations`, `FileDossiers` |
| IDs numéricos | Sufijo `_` | `id_`, `source_id_`, `project_id_` |
| Campos normales | snake_case | `file_type`, `created_at` |

### Motor y Charset

- **Engine:** InnoDB
- **Charset:** utf8mb4
- **Collation:** utf8mb4_0900_ai_ci (MySQL 8.0)
- **Versión mínima:** MySQL 8.0+ o MariaDB 10.3+

### Foreign Keys

- Todas las FKs usan `ON DELETE CASCADE` cuando el hijo depende completamente del padre
- FKs desnormalizadas (`project_id_`) en tablas de grafo para consultas rápidas

### Índices

- Índices simples para columnas de filtrado frecuente
- Índices compuestos para consultas comunes del grafo
- Unique constraints para prevenir duplicados en relaciones

---

## Flujo de Trabajo Recomendado

1. **Desarrollo:** Crear migración en `database/migrations/NNN_descripcion.sql`
2. **Aplicar:** Ejecutar migración contra la base de datos
3. **Verificar:** Confirmar que los cambios se aplicaron correctamente
4. **Actualizar dump:** Generar nuevo dump en `database/schema/adbbmis1_Cloud.sql`
5. **Commit:** Hacer commit de migración + dump actualizado

---

## Notas Importantes

- ⚠️ **NO modificar** tablas que no sean de Lumina (Users, AccessControl, ChatMessages, ChatSessions, FileS3, S3Folders, calls, etc.)
- ✅ Las migraciones son **idempotentes**: verifican si columnas/tablas existen antes de crear
- 📊 El grafo de dependencias vive en `ChunkRelations` - es el núcleo de Lumina
- 🤖 Los dossiers pueden ser generados por IA (`ai_generated = 1`) o escritos por humanos
