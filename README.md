# Lumina

**Motor de Comprensión de Código PHP**

Lumina es un sistema que analiza codebases PHP para generar un grafo de conocimiento y dossiers detallados, permitiendo a humanos e IAs comprender la arquitectura completa del proyecto antes de programar.

## Arquitectura Conceptual

1. **Chunker**: Analiza archivos PHP con `nikic/php-parser` y extrae `SourceChunks` (archivos, clases, funciones, métodos).
2. **Populator**: Analiza las dependencias entre chunks y genera `ChunkRelations` (quién llama a quién, quién extiende a quién, quién importa a quién).
3. **Dossier Generator**: Genera un "Dossier" por archivo respondiendo 5 preguntas:
   - ¿Dónde está? (ruta)
   - ¿Con qué interactúa? (árbol de dependencias)
   - ¿Qué hace? (descripción a nivel humano + necesidad del proyecto)
   - ¿Cómo lo hace? (descripción a nivel código: por qué, para qué, dónde se usa)
   - ¿Cuál es la causa del fallo? (historial de debugging)

## Base de Datos

El esquema de base de datos está documentado en [`docs/database-schema.md`](docs/database-schema.md).

### Tablas Principales

| Tabla | Descripción |
|-------|-------------|
| `files` | Archivos PHP analizados |
| `chunks` | Fragmentos de código (clases, funciones, métodos) |
| `chunk_relations` | Grafo de dependencias entre chunks |
| `dossiers` | Análisis semántico por archivo (5 preguntas) |
| `analysis_sessions` | Registro de ejecuciones de análisis |

### Archivos SQL

- **`lumina_schema.sql`**: Script completo con todas las tablas, vistas, procedimientos almacenados y triggers para MySQL/MariaDB.

## Instalación

```bash
# Ejecutar el script SQL en tu base de datos MySQL
mysql -u usuario -p nombre_base_datos < lumina_schema.sql
```

## Estructura del Proyecto

```
/workspace
├── lumina_schema.sql          # Esquema de base de datos completo
├── docs/
│   └── database-schema.md     # Documentación detallada del esquema
├── adbbmis1_Cloud.sql         # Esquema existente (otros componentes del sistema)
├── README.md                  # Este archivo
└── LICENSE                    # Licencia del proyecto
```

## Consultas de Ejemplo

Ver [docs/database-schema.md](docs/database-schema.md) para ejemplos completos de consultas.

## Motor de Base de Datos

- **Requerido:** MySQL 8.0+ o MariaDB 10.3+
- **Charset:** utf8mb4
- **Engine:** InnoDB 
