# Lumina Changelog

All notable changes to this project will be documented in this file.

## [1.6.0] - 2026-08-04
### Added
- **Fase 6 completada**: Integración con IA (Claude API)
  - `AiClientInterface`: interfaz para clientes de IA
  - `ClaudeClient`: cliente para Anthropic Claude API con curl
  - `PromptBuilder`: construye prompts optimizados para las 5 preguntas
  - `AiEnricher`: orquestador que enriquece dossiers con IA
  - Comando CLI: `./bin/lumina enrich [project_id] [limit]`
  - Método `Lumina::enrichWithAi()` para enriquecimiento programático
  - Configuración de IA en `config/lumina.php` (anthropic, openai)
  - Variables de entorno en `.env.example` (ANTHROPIC_API_KEY, etc.)
  - Rate limiting automático (1 segundo entre requests)
  - Cálculo de costos estimados basado en pricing de Claude 3.5 Sonnet
  - Parseo robusto de JSON (soporta markdown, texto extra)
  - Fallback graceful si la API falla (mantiene dossier estático)
  - Campo `ai_generated = 1` en FileDossiers tras enriquecimiento
  - Tests unitarios para PromptBuilder y ClaudeClient

## [1.5.0] - 2026-08-04
### Added
- **Fase 5 completada**: Generador de Dossiers
  - `QuestionAnswerer`: responde las 5 preguntas fundamentales de Lumina
  - `DossierTemplate`: formato Markdown profesional con emojis
  - `DossierGenerator`: orquestador con confidence score automático
  - Comando CLI: `./bin/lumina dossier <file|all> [project_id]`
  - Generación automática de `SKILL.md` para IA
  - Dossiers individuales en carpeta `/dossiers/`
  - Heurística inteligente para inferir propósito de clases (27 patrones)
  - Tests unitarios para QuestionAnswerer

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
