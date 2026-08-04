# Lumina

**Motor de Comprensión de Código PHP**

[![License](https://img.shields.io/badge/License-Apache_2.0-blue.svg)](https://opensource.org/licenses/Apache-2.0)
[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-purple.svg)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0%2B-orange.svg)](https://www.mysql.com/)

Lumina es un sistema de análisis estático que procesa codebases PHP para generar
un **grafo de conocimiento** y **dossiers detallados** de cada archivo. Permite
a desarrolladores y sistemas de Inteligencia Artificial comprender la arquitectura
completa de un proyecto antes de escribir, modificar o depurar código.

## 🎯 ¿Qué hace Lumina?

Lumina responde 5 preguntas fundamentales sobre cada archivo de tu proyecto:

1. **¿Dónde está?** → Ruta, namespace y contexto dentro del proyecto
2. **¿Con qué interactúa?** → Árbol completo de dependencias (quién llama a quién)
3. **¿Qué hace?** → Descripción a nivel humano + necesidad del proyecto que cubre
4. **¿Cómo lo hace?** → Descripción técnica: por qué, para qué y dónde se usa
5. **¿Causa de fallo?** → Historial de debugging y problemas conocidos

## 🏗️ Arquitectura

El pipeline de Lumina consta de 3 componentes principales:

### 1. Chunker (Extractor de Fragmentos)
Analiza archivos PHP usando [`nikic/php-parser`](https://github.com/nikic/PHP-Parser)
y extrae **SourceChunks**: clases, interfaces, traits, funciones, métodos, propiedades
y constantes. Cada chunk se almacena con su firma, PHPDoc, visibilidad, tipo de
retorno y parámetros.

### 2. Populator (Analizador de Relaciones)
Recorre el AST (Abstract Syntax Tree) para detectar dependencias entre chunks y
genera **ChunkRelations**:
- `calls` → Llama a una función/método
- `extends` → Extiende una clase
- `implements` → Implementa una interfaz
- `uses_trait` → Usa un trait
- `instantiates` → Crea una instancia con `new`
- `imports` → `use` statement
- `type_hints` → Usa como type hint
- `returns` → Tipo de retorno
- `throws` → Lanza una excepción
- `overrides` → Sobrescribe método del padre

### 3. Dossier Generator (Generador de Expedientes)
Produce un **FileDossier** por cada archivo, respondiendo las 5 preguntas clave.
Los dossiers pueden ser generados automáticamente por análisis estático o
enriquecidos con IA (Claude, GPT) para obtener descripciones semánticas profundas.

## 🗄️ Base de Datos

Lumina se integra con una base de datos MySQL 8.0+ existente que gestiona
proyectos, sesiones de chat, contexto y archivos en S3. Las tablas específicas
de Lumina son:

| Tabla | Descripción | Convención |
|-------|-------------|------------|
| `Projects` | Contenedor de proyectos analizados | Existente |
| `ProjectSources` | Archivos fuente del proyecto | Existente + campos Lumina |
| `SourceChunks` | Fragmentos de código extraídos | Existente + campos PHP completos |
| `ChunkRelations` | Grafo de dependencias entre chunks | **Nueva** |
| `FileDossiers` | Dossiers con las 5 preguntas por archivo | **Nueva** |
| `AnalysisSessions` | Registro de cada ejecución del análisis | **Nueva** |

> **Nota:** Lumina usa la convención de nombres del sistema existente:
> - CamelCase para nombres de tablas
> - Sufijo `_` para IDs primarios y foráneos (ej. `id_`, `project_id_`)
> - Charset `utf8mb4` y engine `InnoDB`

### Documentación del Esquema
Ver [`docs/database-schema.md`](docs/database-schema.md) para el diagrama ER
completo, consultas de ejemplo y procedimientos almacenados.

## 📦 Instalación

### Requisitos
- PHP 8.2 o superior
- MySQL 8.0+ o MariaDB 10.3+
- Composer 2.x
- Extensiones: `pdo_mysql`, `json`, `mbstring`

### Paso 1: Clonar el repositorio
```bash
git clone https://github.com/tu-usuario/lumina.git
cd lumina
```

### Paso 2: Instalar dependencias PHP
```bash
composer install
```

### Paso 3: Configurar variables de entorno
```bash
cp .env.example .env
# Editar .env con tus credenciales de base de datos
```

### Paso 4: Aplicar migraciones de Lumina
```bash
# Próximamente: comando de migración
mysql -u usuario -p nombre_base_datos < database/migrations/001_lumina_core.sql
```

### Paso 5: Verificar instalación
```bash
# Próximamente: comando de verificación
php bin/lumina --version
```

## 🚀 Uso

### Analizar un proyecto completo
```bash
# Próximamente
php bin/lumina analyze /ruta/al/proyecto
```

### Generar dossier de un archivo específico
```bash
# Próximamente
php bin/lumina dossier /ruta/al/archivo.php
```

### Ver grafo de dependencias
```bash
# Próximamente
php bin/lumina graph /ruta/al/proyecto
```

## 📁 Estructura del Proyecto

```
lumina/
├── bin/
│   ├── lumina                    # CLI principal
│   └── lumina-analyze            # Comando de análisis
├── config/
│   ├── lumina.php                # Configuración general
│   ├── database.php              # Configuración de BD
│   └── parser.php                # Configuración del parser
├── database/
│   ├── schema/
│   │   └── adbbmis1_Cloud.sql    # Dump completo de la BD
│   ├── migrations/
│   │   └── 001_lumina_core.sql   # Migraciones de Lumina
│   └── README.md                 # Documentación de BD
├── docs/
│   ├── database-schema.md        # Esquema completo de BD
│   ├── architecture.md           # Arquitectura del motor
│   ├── usage.md                  # Guía de uso detallada
│   └── api.md                    # API de las clases
├── src/
│   ├── Core/                     # Clases base (Config, Database, Lumina)
│   ├── Parser/                   # Chunker con nikic/php-parser
│   ├── Populator/                # Analizador de relaciones
│   ├── Dossier/                  # Generador de dossiers
│   ├── Analyzer/                 # Sesiones de análisis
│   ├── Model/                    # Entidades (SourceChunk, ChunkRelation, etc.)
│   ├── Repository/               # Acceso a datos
│   └── Contracts/                # Interfaces
├── tests/
│   ├── Unit/
│   ├── Integration/
│   └── Fixtures/
├── .env.example
├── .gitignore
├── composer.json
├── phpunit.xml
├── phpstan.neon
├── .php-cs-fixer.php
├── LICENSE                       # Apache 2.0
├── CHANGELOG.md
└── README.md                     # Este archivo
```

## 🔧 Desarrollo

### Ejecutar tests
```bash
vendor/bin/phpunit
```

### Análisis estático con PHPStan
```bash
vendor/bin/phpstan analyse
```

### Formateo de código
```bash
vendor/bin/php-cs-fixer fix
```

## 🤝 Integración con Sistema Existente

Lumina no opera de forma aislada. Se integra con un sistema más amplio que
gestiona:

- **ChatSessions** → Conversaciones con IA sobre el código
- **ChatMessages** → Mensajes del pipeline (compilación, respuesta, lint, embedding)
- **ProjectContext** → Reglas, decisiones y hechos del proyecto
- **SessionContextBlocks** → Bloques de contexto comprimidos para IA
- **FileVersions** → Versiones de archivos generados por la IA
- **LintAttempts** → Intentos de corrección de sintaxis
- **TokenUsage** → Tracking de costos de IA
- **ChunkEmbeddings** → Vectores para búsqueda semántica

Esta integración permite que Lumina alimente de contexto rico a los modelos
de IA, mejorando drásticamente la calidad de las respuestas y reduciendo
alucinaciones.

## 🗺️ Roadmap

- [x] Fase 1: Diseño de base de datos y migraciones
- [ ] Fase 2: Estructura del proyecto PHP con Composer
- [ ] Fase 3: Chunker con nikic/php-parser
- [ ] Fase 4: Populator de relaciones (grafo de dependencias)
- [ ] Fase 5: Generador de Dossiers (5 preguntas)
- [ ] Fase 6: Integración con IA (Claude/GPT para enriquecer dossiers)
- [ ] Fase 7: Visualización 3D del grafo de conocimiento

## 📄 Licencia

Este proyecto está licenciado bajo la Apache License 2.0 - ver el archivo
[LICENSE](LICENSE) para más detalles.

La licencia Apache 2.0 incluye una cláusula explícita de patentes que protege
tanto a los contribuidores como a los usuarios del proyecto.

## 🙏 Agradecimientos

- **[nikic/php-parser](https://github.com/nikic/PHP-Parser)** - El parser PHP
  más robusto del ecosistema, usado por PHPStan, Psalm y Rector.
- **Claude** - Asistente de IA que ayudó en el diseño
  arquitectónico de Lumina.

## 📧 Contacto

Si tienes preguntas, sugerencias o quieres contribuir, abre un issue en GitHub
o contacta al mantenedor del proyecto.

---

*Lumina - Arrojando luz sobre la oscuridad del código legacy* ✨
