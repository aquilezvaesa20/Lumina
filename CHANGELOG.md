# Lumina Changelog

All notable changes to this project will be documented in this file.

## [1.0.0] - 2026-08-04

### Added
- Estructura inicial del proyecto PHP con PSR-4 autoloading
- Configuración de Composer con nikic/php-parser ^5.0
- CLI principal (`bin/lumina`) con comandos: analyze, dossier, graph, help
- Clases base del Core:
  - `Lumina\Core\Config` - Gestor de configuración con notación dot
  - `Lumina\Core\Database` - Wrapper PDO con conexión lazy
  - `Lumina\Core\Lumina` - Clase principal que coordina el pipeline
- Models (Value Objects):
  - `SourceChunk` - Representa un chunk de código extraído
  - `ChunkRelation` - Representa una relación entre chunks
  - `FileDossier` - Dossier de comprensión de archivo
- Stubs para Parser, Populator y Dossier (Fases 3-5)
- Contracts/Interfaces para Repository, Parser, Populator, DossierGenerator
- Configuración de herramientas de desarrollo:
  - PHPUnit 10 para tests unitarios e integration
  - PHPStan level 8 para análisis estático
  - PHP-CS-Fixer con reglas PSR-12
- Archivos de configuración:
  - `config/lumina.php` - Configuración general
  - `.env.example` - Plantilla de variables de entorno
  - `phpunit.xml` - Configuración de PHPUnit
  - `phpstan.neon` - Configuración de PHPStan
  - `.php-cs-fixer.php` - Configuración de formateo

### Database
- Schema completo listo en `database/schema/`
- Migraciones disponibles en `database/migrations/`

### Notes
- Proyecto configurado con PHP >= 8.2
- Todo el código usa `declare(strict_types=1);`
- Namespaces siguen PSR-4: `Lumina\` → `src/`
- Listo para comenzar la Fase 3: El Chunker (Parser)
