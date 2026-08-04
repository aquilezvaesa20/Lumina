/**
 * Lumina Graph Visualizer - Cytoscape.js
 */
(function () {
    const api = new LuminaApi();
    let cy = null;
    let currentProjectId = 1;

    // Colores por tipo de nodo
    const nodeColors = {
        class: '#4fc3f7',
        interface: '#81c784',
        trait: '#ffb74d',
        function: '#ba68c8',
        method: '#9575cd',
    };

    // Colores por tipo de relación
    const edgeColors = {
        calls: '#64b5f6',
        extends: '#81c784',
        implements: '#4db6ac',
        uses_trait: '#ffb74d',
        instantiates: '#ef5350',
        type_hints: '#ba68c8',
        returns: '#7e57c2',
        throws: '#ef5350',
        contains: '#ffa726',
        references: '#78909c',
        imports: '#26a69a',
        overrides: '#ec407a',
    };

    /**
     * Inicializa Cytoscape
     */
    function initCytoscape() {
        cy = cytoscape({
            container: document.getElementById('cy'),
            
            style: [
                // Nodos
                {
                    selector: 'node',
                    style: {
                        'label': 'data(label)',
                        'background-color': 'data(color)',
                        'color': '#e0e6ed',
                        'text-valign': 'center',
                        'text-halign': 'center',
                        'font-size': '11px',
                        'text-wrap': 'ellipsis',
                        'text-max-width': '80px',
                        'width': '40px',
                        'height': '40px',
                        'border-width': 2,
                        'border-color': '#2a3050',
                        'text-outline-width': 0,
                        'text-margin-y': '25px',
                    }
                },
                // Nodos seleccionados
                {
                    selector: 'node:selected',
                    style: {
                        'border-width': 4,
                        'border-color': '#ffeb3b',
                        'width': '50px',
                        'height': '50px',
                        'font-size': '13px',
                        'z-index': 999,
                    }
                },
                // Aristas
                {
                    selector: 'edge',
                    style: {
                        'width': 1.5,
                        'line-color': 'data(color)',
                        'target-arrow-color': 'data(color)',
                        'target-arrow-shape': 'triangle',
                        'curve-style': 'bezier',
                        'arrow-scale': 0.8,
                        'opacity': 0.6,
                    }
                },
                // Aristas seleccionadas
                {
                    selector: 'edge:selected',
                    style: {
                        'width': 3,
                        'opacity': 1,
                    }
                },
            ],

            layout: {
                name: 'dagre',
                rankDir: 'LR',
                nodeSep: 30,
                rankSep: 80,
                padding: 30,
            },

            wheelSensitivity: 0.3,
        });

        // Eventos
        cy.on('tap', 'node', handleNodeClick);
        cy.on('tap', (e) => {
            if (e.target === cy) {
                hideDetailPanel();
            }
        });
    }

    /**
     * Carga el grafo desde la API
     */
    async function loadGraph(projectId, filter = null) {
        currentProjectId = projectId;
        showLoading(true);
        hideDetailPanel();

        try {
            const data = await api.getGraph(projectId, filter);

            if (data.nodes.length === 0) {
                alert(data.message || 'No hay datos. Ejecuta primero: ./bin/lumina analyze && ./bin/lumina populate');
                showLoading(false);
                return;
            }

            // Agregar colores a los datos
            data.nodes.forEach(node => {
                node.data.color = nodeColors[node.data.type] || '#78909c';
            });
            data.edges.forEach(edge => {
                edge.data.color = edgeColors[edge.data.relation] || '#78909c';
            });

            // Cargar en Cytoscape
            cy.elements().remove();
            cy.add([...data.nodes, ...data.edges]);
            cy.layout({
                name: 'dagre',
                rankDir: 'LR',
                nodeSep: 30,
                rankSep: 80,
                padding: 30,
            }).run();

            cy.fit(null, 50);

            // Actualizar stats
            updateStats(data.stats);
            updateFooter(data.stats);

            // Cargar stats detalladas
            loadDetailedStats(projectId);

        } catch (error) {
            console.error('Error loading graph:', error);
            alert('Error al cargar el grafo: ' + error.message);
        } finally {
            showLoading(false);
        }
    }

    /**
     * Carga estadísticas detalladas
     */
    async function loadDetailedStats(projectId) {
        try {
            const stats = await api.getStats(projectId);
            renderSidebarStats(stats);
        } catch (error) {
            console.error('Error loading stats:', error);
        }
    }

    /**
     * Renderiza estadísticas en el sidebar
     */
    function renderSidebarStats(stats) {
        const statsEl = document.getElementById('stats');
        statsEl.innerHTML = `
            <div class="stat-row">
                <span class="stat-label">Archivos</span>
                <span class="stat-value">${stats.files}</span>
            </div>
            <div class="stat-row">
                <span class="stat-label">Chunks</span>
                <span class="stat-value">${stats.chunks}</span>
            </div>
            <div class="stat-row">
                <span class="stat-label">Relaciones</span>
                <span class="stat-value">${stats.relations}</span>
            </div>
            <div class="stat-row">
                <span class="stat-label">Dossiers</span>
                <span class="stat-value">${stats.dossiers}</span>
            </div>
            <div class="stat-row">
                <span class="stat-label">Enriquecidos IA</span>
                <span class="stat-value">${stats.ai_enriched}</span>
            </div>
        `;
    }

    /**
     * Maneja click en un nodo
     */
    async function handleNodeClick(event) {
        const node = event.target;
        const chunkId = node.data('id');

        try {
            const data = await api.getNode(chunkId);
            showDetailPanel(data);
        } catch (error) {
            console.error('Error loading node details:', error);
        }
    }

    /**
     * Muestra el panel de detalles
     */
    function showDetailPanel(data) {
        const panel = document.getElementById('detailPanel');
        const title = document.getElementById('detailTitle');
        const content = document.getElementById('detailContent');

        const chunk = data.chunk;
        const dossier = data.dossier;
        const rels = data.relations;

        title.textContent = `${chunk.name} (${chunk.chunk_type})`;

        let html = `
            <div class="detail-section">
                <h4>📍 Información</h4>
                <p><strong>Tipo:</strong> <code>${chunk.chunk_type}</code></p>
                <p><strong>Archivo:</strong> <code>${chunk.filename}</code></p>
                ${chunk.namespace ? `<p><strong>Namespace:</strong> <code>${chunk.namespace}</code></p>` : ''}
                ${chunk.visibility ? `<p><strong>Visibilidad:</strong> <code>${chunk.visibility}</code></p>` : ''}
                <p><strong>Líneas:</strong> ${chunk.start_line} - ${chunk.end_line}</p>
            </div>
        `;

        if (chunk.signature) {
            html += `
                <div class="detail-section">
                    <h4>📝 Firma</h4>
                    <pre style="background:#1a1f3a;padding:10px;border-radius:4px;font-size:12px;overflow-x:auto;white-space:pre-wrap;">${escapeHtml(chunk.signature)}</pre>
                </div>
            `;
        }

        if (chunk.docblock) {
            html += `
                <div class="detail-section">
                    <h4>📚 Documentación</h4>
                    <pre style="background:#1a1f3a;padding:10px;border-radius:4px;font-size:12px;overflow-x:auto;white-space:pre-wrap;">${escapeHtml(chunk.docblock)}</pre>
                </div>
            `;
        }

        if (dossier) {
            html += `
                <div class="detail-section">
                    <h4>🎯 ¿Qué hace?</h4>
                    <p>${escapeHtml(dossier.what_does)}</p>
                </div>
                ${dossier.why_exists ? `
                <div class="detail-section">
                    <h4>💡 ¿Por qué existe?</h4>
                    <p>${escapeHtml(dossier.why_exists)}</p>
                </div>
                ` : ''}
                <div class="detail-section">
                    <h4>🤖 Metadata</h4>
                    <p><strong>Generado por:</strong> ${dossier.ai_generated ? 'IA' : 'Análisis estático'}</p>
                    <p><strong>Confianza:</strong> ${(dossier.confidence_score * 100).toFixed(0)}%</p>
                </div>
            `;
        }

        if (rels.outgoing_count > 0 || rels.incoming_count > 0) {
            html += `<div class="detail-section"><h4>🔗 Relaciones</h4>`;

            if (rels.outgoing_count > 0) {
                html += `<p><strong>Salientes (${rels.outgoing_count}):</strong></p><ul class="relation-list">`;
                rels.outgoing.slice(0, 10).forEach(rel => {
                    html += `<li><span class="rel-type">${rel.relation_type}</span> <code>${rel.target_name}</code></li>`;
                });
                if (rels.outgoing_count > 10) {
                    html += `<li><em>... y ${rels.outgoing_count - 10} más</em></li>`;
                }
                html += `</ul>`;
            }

            if (rels.incoming_count > 0) {
                html += `<p style="margin-top:10px;"><strong>Entrantes (${rels.incoming_count}):</strong></p><ul class="relation-list">`;
                rels.incoming.slice(0, 10).forEach(rel => {
                    html += `<li><span class="rel-type">${rel.relation_type}</span> <code>${rel.source_name}</code></li>`;
                });
                if (rels.incoming_count > 10) {
                    html += `<li><em>... y ${rels.incoming_count - 10} más</em></li>`;
                }
                html += `</ul>`;
            }

            html += `</div>`;
        }

        content.innerHTML = html;
        panel.style.display = 'block';
    }

    function hideDetailPanel() {
        document.getElementById('detailPanel').style.display = 'none';
    }

    function updateStats(stats) {
        // Stats ya se actualizan en renderSidebarStats
    }

    function updateFooter(stats) {
        document.getElementById('nodeCount').textContent = `${stats.nodes} nodos`;
        document.getElementById('edgeCount').textContent = `${stats.edges} aristas`;
    }

    function showLoading(show) {
        document.getElementById('loading').style.display = show ? 'block' : 'none';
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    /**
     * Event listeners
     */
    document.getElementById('reloadBtn').addEventListener('click', () => {
        const filter = document.getElementById('filterSelect').value;
        loadGraph(currentProjectId, filter || null);
    });

    document.getElementById('layoutBtn').addEventListener('click', () => {
        if (cy) {
            cy.layout({
                name: 'dagre',
                rankDir: 'LR',
                nodeSep: 30,
                rankSep: 80,
                padding: 30,
            }).run();
            cy.fit(null, 50);
        }
    });

    document.getElementById('filterSelect').addEventListener('change', (e) => {
        loadGraph(currentProjectId, e.target.value || null);
    });

    document.getElementById('projectSelect').addEventListener('change', (e) => {
        loadGraph(parseInt(e.target.value), null);
    });

    document.getElementById('closeDetail').addEventListener('click', hideDetailPanel);

    // Inicialización
    window.addEventListener('DOMContentLoaded', () => {
        initCytoscape();
        loadGraph(1);
    });
})();
