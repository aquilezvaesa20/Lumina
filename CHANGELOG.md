# Lumina Changelog

## [1.0.0] - 2024-08-04

### Added
- Estructura inicial del proyecto PHP con PSR-4
- Configuración de Composer con nikic/php-parser ^5.0
- CLI principal (`bin/lumina`)
- Clases base del Core (Lumina, Config, Database)
- Models iniciales (SourceChunk, ChunkRelation, FileDossier, Project, ProjectSource)
- Stubs para Parser, Populator y Dossier (Fases 3-5)
- Contracts/Interfaces para Repository, Parser, Populator, DossierGenerator
- Configuración de herramientas de desarrollo (PHPUnit, PHPStan, PHP-CS-Fixer)
- Documentación inicial de arquitectura

### Database
- Migración `001_lumina_core.sql` completada (Fase 1)
- Schema completo en `database/schema/adbbmis1_Cloud.sql`

### Notes
- Proyecto listo para comenzar la Fase 3: El Chunker (Parser)
- Todas las clases tienen stubs con TODO para implementación futura
