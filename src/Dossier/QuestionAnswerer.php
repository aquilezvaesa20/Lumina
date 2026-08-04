<?php

declare(strict_types=1);

namespace Lumina\Dossier;

use Lumina\Core\Database;

/**
 * Responde cada una de las 5 preguntas del dossier usando análisis estático
 */
class QuestionAnswerer
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * P1: ¿Dónde está?
     * Retorna la ubicación y contexto del archivo en el proyecto
     */
    public function answerWhereIs(array $source, array $chunks): string
    {
        $filename = $source['filename'] ?? 'desconocido';
        $s3Key = $source['s3_key'] ?? '';
        $language = $source['language'] ?? 'php';

        // Determinar namespace principal
        $namespace = '';
        foreach ($chunks as $chunk) {
            if (!empty($chunk['namespace'])) {
                $namespace = $chunk['namespace'];
                break;
            }
        }

        // Determinar tipo principal del archivo
        $mainType = $this->detectMainType($chunks);

        $parts = [];
        $parts[] = "**Archivo:** `{$filename}`";
        $parts[] = "**Ruta:** `{$s3Key}`";
        
        if ($namespace) {
            $parts[] = "**Namespace:** `{$namespace}`";
        }
        
        $parts[] = "**Tipo principal:** {$mainType}";
        $parts[] = "**Lenguaje:** {$language}";
        $parts[] = "**Tamaño:** " . ($source['size_bytes'] ?? 0) . " bytes";

        return implode("\n", $parts);
    }

    /**
     * P2: ¿Con qué interactúa?
     * Retorna el árbol de dependencias como JSON estructurado
     */
    public function answerInteractsWith(int $sourceId, int $projectId): array
    {
        // Obtener todos los chunks de este archivo
        $chunks = $this->db->fetchAll(
            "SELECT id_, name, chunk_type FROM SourceChunks WHERE source_id_ = ?",
            [$sourceId]
        );

        if (empty($chunks)) {
            return ['outgoing' => [], 'incoming' => [], 'summary' => 'Sin dependencias detectadas'];
        }

        $chunkIds = array_column($chunks, 'id_');
        $placeholders = implode(',', array_fill(0, count($chunkIds), '?'));

        // Dependencias salientes (este archivo → otros)
        $outgoing = $this->db->fetchAll(
            "SELECT cr.relation_type, tc.name AS target_name, tc.chunk_type AS target_type,
                    tc.namespace AS target_namespace, cr.context, cr.is_confirmed
             FROM ChunkRelations cr
             JOIN SourceChunks tc ON cr.target_chunk_id_ = tc.id_
             WHERE cr.source_chunk_id_ IN ({$placeholders})
             AND cr.project_id_ = ?",
            array_merge($chunkIds, [$projectId])
        );

        // Dependencias entrantes (otros → este archivo)
        $incoming = $this->db->fetchAll(
            "SELECT cr.relation_type, sc.name AS source_name, sc.chunk_type AS source_type,
                    sc.namespace AS source_namespace, cr.context, cr.is_confirmed
             FROM ChunkRelations cr
             JOIN SourceChunks sc ON cr.source_chunk_id_ = sc.id_
             WHERE cr.target_chunk_id_ IN ({$placeholders})
             AND cr.project_id_ = ?",
            array_merge($chunkIds, [$projectId])
        );

        // Agrupar por tipo de relación
        $outgoingGrouped = $this->groupByRelationType($outgoing);
        $incomingGrouped = $this->groupByRelationType($incoming);

        return [
            'outgoing' => $outgoingGrouped,
            'incoming' => $incomingGrouped,
            'outgoing_count' => count($outgoing),
            'incoming_count' => count($incoming),
            'summary' => $this->buildInteractionSummary(
                count($outgoing),
                count($incoming),
                $outgoingGrouped,
                $incomingGrouped
            ),
        ];
    }

    /**
     * P3: ¿Qué hace?
     * Descripción a nivel humano basada en análisis estático
     */
    public function answerWhatDoes(array $source, array $chunks): string
    {
        $filename = $source['filename'] ?? 'desconocido';
        $mainChunk = $this->findMainChunk($chunks);

        if ($mainChunk === null) {
            return "El archivo `{$filename}` no contiene declaraciones principales identificables.";
        }

        $parts = [];

        // Descripción basada en el tipo y nombre del chunk principal
        $type = $mainChunk['chunk_type'];
        $name = $mainChunk['name'] ?? 'sin nombre';
        $namespace = $mainChunk['namespace'] ?? '';

        $typeDescription = match($type) {
            'class' => "la clase `{$name}`",
            'interface' => "la interfaz `{$name}`",
            'trait' => "el trait `{$name}`",
            'function' => "la función `{$name}`",
            default => "el componente `{$name}`",
        };

        $parts[] = "Este archivo define {$typeDescription}";
        
        if ($namespace) {
            $parts[] = "dentro del namespace `{$namespace}`.";
        } else {
            $parts[] = "en el espacio global.";
        }

        // Si tiene docblock, extraer primera línea como descripción
        if (!empty($mainChunk['docblock'])) {
            $docblockFirstLine = $this->extractDocblockSummary($mainChunk['docblock']);
            if ($docblockFirstLine) {
                $parts[] = "\n**Descripción del código:** {$docblockFirstLine}";
            }
        }

        // Contar métodos/funciones internos
        $methods = array_filter($chunks, fn($c) => $c['chunk_type'] === 'method');
        $functions = array_filter($chunks, fn($c) => $c['chunk_type'] === 'function');
        $totalMethods = count($methods) + count($functions);

        if ($totalMethods > 0) {
            $parts[] = "\nContiene {$totalMethods} método(s)/función(es) que implementan su lógica.";
        }

        // Descripción basada en el nombre (heurística)
        $nameHint = $this->inferPurposeFromName($name);
        if ($nameHint) {
            $parts[] = "\n**Propósito inferido:** {$nameHint}";
        }

        return implode(' ', array_slice($parts, 0, 2)) . implode('', array_slice($parts, 2));
    }

    /**
     * P3b: ¿Para qué existe?
     * Necesidad del proyecto que cubre (inferida del nombre y contexto)
     */
    public function answerWhyExists(array $source, array $chunks): ?string
    {
        $mainChunk = $this->findMainChunk($chunks);
        
        if ($mainChunk === null) {
            return null;
        }

        $name = $mainChunk['name'] ?? '';
        $purpose = $this->inferPurposeFromName($name);

        if ($purpose) {
            return "Este archivo existe para: {$purpose}";
        }

        return "No se puede determinar el propósito específico sin análisis semántico (se enriquecerá con IA en Fase 6).";
    }

    /**
     * P4: ¿Cómo lo hace?
     * Descripción técnica del funcionamiento
     */
    public function answerHowDoes(array $source, array $chunks): string
    {
        $mainChunk = $this->findMainChunk($chunks);
        
        if ($mainChunk === null) {
            return "No se pudo determinar la implementación interna.";
        }

        $parts = [];
        $type = $mainChunk['chunk_type'];
        $name = $mainChunk['name'] ?? 'componente';

        // Estructura interna
        $methods = array_filter($chunks, fn($c) => 
            $c['chunk_type'] === 'method' && $c['parent_name'] === $name
        );
        $properties = array_filter($chunks, fn($c) => 
            $c['chunk_type'] === 'property' && $c['parent_name'] === $name
        );
        $constants = array_filter($chunks, fn($c) => 
            $c['chunk_type'] === 'constant' && $c['parent_name'] === $name
        );

        if ($type === 'class' || $type === 'interface' || $type === 'trait') {
            $parts[] = "## Estructura interna de `{$name}`\n";
            
            if (!empty($properties)) {
                $parts[] = "### Propiedades (" . count($properties) . ")\n";
                foreach (array_slice($properties, 0, 10) as $prop) {
                    $vis = $prop['visibility'] ?? 'public';
                    $static = $prop['is_static'] ? ' static' : '';
                    $typeHint = $prop['return_type'] ?? 'mixed';
                    $parts[] = "- `{$vis}{$static} {$typeHint} \${$prop['name']}`";
                }
                if (count($properties) > 10) {
                    $parts[] = "- ... y " . (count($properties) - 10) . " más";
                }
            }

            if (!empty($methods)) {
                $parts[] = "\n### Métodos (" . count($methods) . ")\n";
                foreach (array_slice($methods, 0, 15) as $method) {
                    $vis = $method['visibility'] ?? 'public';
                    $static = $method['is_static'] ? 'static ' : '';
                    $returnType = $method['return_type'] ? ": {$method['return_type']}" : '';
                    $parts[] = "- `{$vis} {$static}function {$method['name']}(){$returnType}`";
                }
                if (count($methods) > 15) {
                    $parts[] = "- ... y " . (count($methods) - 15) . " más";
                }
            }

            if (!empty($constants)) {
                $parts[] = "\n### Constantes (" . count($constants) . ")\n";
                foreach ($constants as $const) {
                    $parts[] = "- `{$const['name']}`";
                }
            }
        } elseif ($type === 'function') {
            $parts[] = "## Implementación de `{$name}()`\n";
            $returnType = $mainChunk['return_type'] ? ": {$mainChunk['return_type']}" : '';
            $parts[] = "- **Firma:** `function {$name}(){$returnType}`";
            
            if (!empty($mainChunk['parameters_json'])) {
                $params = json_decode($mainChunk['parameters_json'], true);
                if (!empty($params)) {
                    $parts[] = "- **Parámetros:**";
                    foreach ($params as $param) {
                        $paramType = $param['type'] ?? 'mixed';
                        $default = $param['default'] ? " = {$param['default']}" : '';
                        $parts[] = "  - `{$paramType} {$param['name']}{$default}`";
                    }
                }
            }
        }

        // Relaciones de dependencia (cómo interactúa con otros)
        $parts[] = "\n### Interacciones principales\n";
        $parts[] = "Ver pregunta 2 (¿Con qué interactúa?) para el detalle completo de dependencias.";

        return implode("\n", $parts);
    }

    /**
     * P5: ¿Causa de fallo?
     * Problemas conocidos y historial de debugging
     */
    public function answerFailureCauses(int $sourceId, int $projectId): ?string
    {
        // Verificar si hay intentos de lint fallidos relacionados
        try {
            $lintAttempts = $this->db->fetchAll(
                "SELECT la.error_message, la.attempt_number, la.model_used, la.created_at
                 FROM LintAttempts la
                 JOIN FileVersions fv ON la.file_version_id_ = fv.id_
                 WHERE fv.project_id_ = ?
                 ORDER BY la.created_at DESC
                 LIMIT 5",
                [$projectId]
            );

            if (!empty($lintAttempts)) {
                $parts = ["### Historial de errores de sintaxis\n"];
                foreach ($lintAttempts as $attempt) {
                    $parts[] = "- **Intento #{$attempt['attempt_number']}** ({$attempt['created_at']}):";
                    $parts[] = "  " . substr($attempt['error_message'] ?? 'Sin mensaje', 0, 200);
                }
                return implode("\n", $parts);
            }
        } catch (\Throwable $e) {
            // La tabla puede no existir aún o no tener datos relacionados
        }

        return "No se tienen registros de fallos para este archivo. " .
               "Los historiales de debugging se enriquecerán con IA en Fase 6.";
    }

    // ==========================================
    // MÉTODOS PRIVADOS AUXILIARES
    // ==========================================

    private function detectMainType(array $chunks): string
    {
        $types = [];
        foreach ($chunks as $chunk) {
            if (in_array($chunk['chunk_type'], ['class', 'interface', 'trait', 'function'])) {
                $types[] = $chunk['chunk_type'];
            }
        }

        if (empty($types)) return 'desconocido';
        if (count($types) === 1) return $types[0];
        
        // Si hay múltiples, priorizar class > interface > trait > function
        foreach (['class', 'interface', 'trait', 'function'] as $priority) {
            if (in_array($priority, $types)) return $priority;
        }

        return 'mixed';
    }

    private function findMainChunk(array $chunks): ?array
    {
        // Buscar el chunk principal: class > interface > trait > function
        foreach (['class', 'interface', 'trait', 'function'] as $type) {
            foreach ($chunks as $chunk) {
                if ($chunk['chunk_type'] === $type && !empty($chunk['name'])) {
                    return $chunk;
                }
            }
        }
        return null;
    }

    private function extractDocblockSummary(string $docblock): ?string
    {
        // Extraer primera línea significativa del docblock
        $lines = explode("\n", $docblock);
        foreach ($lines as $line) {
            $clean = trim($line, " \t\n\r\0\x0B/*");
            if (!empty($clean) && !str_starts_with($clean, '@') && !str_starts_with($clean, '/')) {
                return $clean;
            }
        }
        return null;
    }

    private function inferPurposeFromName(string $name): ?string
    {
        $patterns = [
            '/^Auth/i' => 'gestionar autenticación y autorización de usuarios',
            '/^User/i' => 'gestionar entidades y operaciones de usuarios',
            '/^Controller$/i' => 'manejar peticiones HTTP y coordinar la lógica de negocio',
            '/^Service$/i' => 'encapsular la lógica de negocio y coordinar entre componentes',
            '/^Repository$/i' => 'gestionar el acceso a datos y consultas a la base de datos',
            '/^Model$/i' => 'representar una entidad del dominio y sus reglas de negocio',
            '/^Migration/i' => 'definir cambios en el esquema de la base de datos',
            '/^Test$/i' => 'verificar el correcto funcionamiento de otros componentes',
            '/^Helper$/i' => 'proporcionar funciones utilitarias reutilizables',
            '/^Middleware$/i' => 'interceptar y procesar peticiones antes de llegar al controlador',
            '/^Exception$/i' => 'representar un tipo específico de error en el sistema',
            '/^Interface$/i' => 'definir un contrato que otras clases deben implementar',
            '/^Factory$/i' => 'crear instancias de objetos de forma centralizada',
            '/^Validator$/i' => 'validar datos de entrada y reglas de negocio',
            '/^Event$/i' => 'representar un evento del sistema para el patrón observer',
            '/^Listener$/i' => 'reaccionar a eventos específicos del sistema',
            '/^Job$/i' => 'ejecutar tareas asíncronas o en segundo plano',
            '/^Command$/i' => 'ejecutar una acción específica (patrón Command)',
            '/^Query$/i' => 'consultar datos sin modificar el estado (CQRS)',
            '/^Config/i' => 'gestionar la configuración del sistema',
            '/^Log/i' => 'registrar eventos y errores del sistema',
            '/^Cache/i' => 'gestionar el almacenamiento temporal de datos',
            '/^Payment/i' => 'procesar pagos y transacciones',
            '/^Mail|Email/i' => 'gestionar el envío de correos electrónicos',
            '/^Notification/i' => 'gestionar notificaciones a usuarios',
            '/^Api/i' => 'exponer endpoints para comunicación entre sistemas',
            '/^Export/i' => 'generar archivos de salida (PDF, Excel, CSV)',
            '/^Import/i' => 'procesar archivos de entrada y cargar datos',
        ];

        foreach ($patterns as $pattern => $description) {
            if (preg_match($pattern, $name)) {
                return $description;
            }
        }

        return null;
    }

    private function groupByRelationType(array $relations): array
    {
        $grouped = [];
        foreach ($relations as $rel) {
            $type = $rel['relation_type'];
            if (!isset($grouped[$type])) {
                $grouped[$type] = [];
            }
            $grouped[$type][] = [
                'name' => $rel['target_name'] ?? $rel['source_name'] ?? 'desconocido',
                'type' => $rel['target_type'] ?? $rel['source_type'] ?? 'unknown',
                'namespace' => $rel['target_namespace'] ?? $rel['source_namespace'] ?? '',
                'context' => $rel['context'] ?? null,
                'confirmed' => (bool)($rel['is_confirmed'] ?? true),
            ];
        }
        return $grouped;
    }

    private function buildInteractionSummary(
        int $outgoingCount,
        int $incomingCount,
        array $outgoingGrouped,
        array $incomingGrouped
    ): string {
        if ($outgoingCount === 0 && $incomingCount === 0) {
            return 'Este archivo no tiene dependencias detectadas con otros componentes del proyecto.';
        }

        $parts = [];

        if ($outgoingCount > 0) {
            $types = array_keys($outgoingGrouped);
            $parts[] = "Este archivo depende de {$outgoingCount} componente(s) externo(s)";
            $parts[] = "(" . implode(', ', array_slice($types, 0, 3)) . ").";
        }

        if ($incomingCount > 0) {
            $parts[] = "Es utilizado por {$incomingCount} componente(s) del proyecto.";
        }

        return implode(' ', $parts);
    }
}
