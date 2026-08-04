<?php

declare(strict_types=1);

namespace Lumina\Dossier;

/**
 * Formatea el dossier completo en Markdown legible
 */
class DossierTemplate
{
    /**
     * Genera el dossier completo en formato Markdown
     */
    public function render(
        array $source,
        string $whereIs,
        array $interactsWith,
        string $whatDoes,
        ?string $whyExists,
        string $howDoes,
        ?string $failureCauses,
        bool $aiGenerated = false,
        ?float $confidenceScore = null
    ): string {
        $filename = $source['filename'] ?? 'archivo';
        $date = date('Y-m-d H:i:s');
        $generator = $aiGenerated ? 'IA' : 'Análisis estático (Lumina)';

        $md = [];
        $md[] = "# 📋 Dossier: `{$filename}`";
        $md[] = "";
        $md[] = "> Generado por **Lumina** | {$generator} | {$date}";
        
        if ($confidenceScore !== null) {
            $md[] = "> Confiabilidad: " . number_format($confidenceScore * 100, 1) . "%";
        }
        $md[] = "";
        $md[] = "---";
        $md[] = "";

        // P1: ¿Dónde está?
        $md[] = "## 📍 1. ¿Dónde está?";
        $md[] = "";
        $md[] = $whereIs;
        $md[] = "";

        // P2: ¿Con qué interactúa?
        $md[] = "## 🔗 2. ¿Con qué interactúa?";
        $md[] = "";
        $md[] = $this->renderInteractions($interactsWith);
        $md[] = "";

        // P3: ¿Qué hace?
        $md[] = "## 🎯 3. ¿Qué hace?";
        $md[] = "";
        $md[] = $whatDoes;
        $md[] = "";

        // P3b: ¿Para qué existe?
        if ($whyExists) {
            $md[] = "### ¿Para qué existe?";
            $md[] = "";
            $md[] = $whyExists;
            $md[] = "";
        }

        // P4: ¿Cómo lo hace?
        $md[] = "## ⚙️ 4. ¿Cómo lo hace?";
        $md[] = "";
        $md[] = $howDoes;
        $md[] = "";

        // P5: ¿Causa de fallo?
        $md[] = "## 🐛 5. ¿Causa de fallo?";
        $md[] = "";
        $md[] = $failureCauses ?? "Sin registros de fallos conocidos.";
        $md[] = "";

        // Footer
        $md[] = "---";
        $md[] = "";
        $md[] = "*Este dossier fue generado automáticamente por Lumina.*";
        $md[] = "*Las respuestas marcadas como 'inferidas' se enriquecerán con IA en futuras fases.*";

        return implode("\n", $md);
    }

    /**
     * Genera el archivo SKILL.md para que la IA lo lea antes de programar
     */
    public function renderSkillMd(
        string $projectName,
        array $dossiers,
        array $stats
    ): string {
        $md = [];
        $md[] = "# 🧠 SKILL.md - Conocimiento del Proyecto: {$projectName}";
        $md[] = "";
        $md[] = "> **Instrucción para la IA:** Antes de escribir, modificar o depurar código";
        $md[] = "> en este proyecto, DEBES leer este archivo para comprender la arquitectura.";
        $md[] = "> Después de programar, actualiza este archivo con lo que aprendiste.";
        $md[] = "";
        $md[] = "---";
        $md[] = "";

        // Resumen del proyecto
        $md[] = "## 📊 Resumen del Proyecto";
        $md[] = "";
        $md[] = "| Métrica | Valor |";
        $md[] = "|---------|-------|";
        $md[] = "| Archivos analizados | {$stats['files_analyzed']} |";
        $md[] = "| Chunks extraídos | {$stats['chunks_extracted']} |";
        $md[] = "| Relaciones detectadas | {$stats['relations_found']} |";
        $md[] = "| Dossiers generados | {$stats['dossiers_generated']} |";
        $md[] = "| Última actualización | " . date('Y-m-d H:i:s') . " |";
        $md[] = "";

        // Índice de archivos
        $md[] = "## 📁 Índice de Archivos";
        $md[] = "";
        $md[] = "| Archivo | Tipo | Dependencias | Dossier |";
        $md[] = "|---------|------|-------------|---------|";
        
        foreach ($dossiers as $dossier) {
            $filename = $dossier['filename'] ?? 'desconocido';
            $type = $dossier['file_type'] ?? 'desconocido';
            $deps = ($dossier['outgoing_count'] ?? 0) + ($dossier['incoming_count'] ?? 0);
            $md[] = "| `{$filename}` | {$type} | {$deps} | ✅ |";
        }
        $md[] = "";

        // Dossiers individuales (primeros 20)
        $md[] = "## 📋 Dossiers Detallados";
        $md[] = "";
        
        foreach (array_slice($dossiers, 0, 20) as $dossier) {
            $md[] = "### `{$dossier['filename']}`";
            $md[] = "";
            $md[] = "**¿Qué hace?** " . ($dossier['what_does_summary'] ?? 'N/A');
            $md[] = "";
            $md[] = "**Dependencias principales:** " . ($dossier['interacts_summary'] ?? 'N/A');
            $md[] = "";
        }

        if (count($dossiers) > 20) {
            $md[] = "> *... y " . (count($dossiers) - 20) . " archivos más. Consulta la BD para detalles completos.*";
            $md[] = "";
        }

        // Reglas para la IA
        $md[] = "---";
        $md[] = "";
        $md[] = "## 🤖 Reglas para la IA";
        $md[] = "";
        $md[] = "1. **SIEMPRE** lee este archivo antes de modificar código.";
        $md[] = "2. **SIEMPRE** consulta el dossier del archivo que vas a modificar.";
        $md[] = "3. **SIEMPRE** verifica las dependencias antes de cambiar firmas de métodos.";
        $md[] = "4. **NUNCA** asumas el propósito de un archivo sin leer su dossier.";
        $md[] = "5. **ACTUALIZA** este archivo después de hacer cambios significativos.";
        $md[] = "6. **RESPETA** la arquitectura existente detectada por Lumina.";
        $md[] = "";

        return implode("\n", $md);
    }

    // ==========================================
    // MÉTODOS PRIVADOS
    // ==========================================

    private function renderInteractions(array $interactsWith): string
    {
        $parts = [];

        if (empty($interactsWith['outgoing']) && empty($interactsWith['incoming'])) {
            return "Este archivo no tiene dependencias detectadas.";
        }

        // Dependencias salientes
        if (!empty($interactsWith['outgoing'])) {
            $parts[] = "### ➡️ Dependencias salientes (este archivo usa)";
            $parts[] = "";
            $parts[] = "| Tipo | Componente | Namespace | Confirmada |";
            $parts[] = "|------|-----------|-----------|------------|";

            foreach ($interactsWith['outgoing'] as $type => $relations) {
                foreach (array_slice($relations, 0, 5) as $rel) {
                    $name = $rel['name'] ?? 'desconocido';
                    $ns = $rel['namespace'] ?? '-';
                    $confirmed = $rel['confirmed'] ? '✅' : '🔮';
                    $parts[] = "| {$type} | `{$name}` | `{$ns}` | {$confirmed} |";
                }
                if (count($relations) > 5) {
                    $parts[] = "| {$type} | ... " . (count($relations) - 5) . " más | - | - |";
                }
            }
            $parts[] = "";
        }

        // Dependencias entrantes
        if (!empty($interactsWith['incoming'])) {
            $parts[] = "### ⬅️ Dependencias entrantes (otros usan este archivo)";
            $parts[] = "";
            $parts[] = "| Tipo | Componente | Namespace | Confirmada |";
            $parts[] = "|------|-----------|-----------|------------|";

            foreach ($interactsWith['incoming'] as $type => $relations) {
                foreach (array_slice($relations, 0, 5) as $rel) {
                    $name = $rel['name'] ?? 'desconocido';
                    $ns = $rel['namespace'] ?? '-';
                    $confirmed = $rel['confirmed'] ? '✅' : '🔮';
                    $parts[] = "| {$type} | `{$name}` | `{$ns}` | {$confirmed} |";
                }
                if (count($relations) > 5) {
                    $parts[] = "| {$type} | ... " . (count($relations) - 5) . " más | - | - |";
                }
            }
            $parts[] = "";
        }

        // Resumen
        if (isset($interactsWith['summary'])) {
            $parts[] = "**Resumen:** " . $interactsWith['summary'];
        }

        return implode("\n", $parts);
    }
}
