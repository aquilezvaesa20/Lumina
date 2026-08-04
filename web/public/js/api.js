/**
 * Cliente API para Lumina
 */
class LuminaApi {
    constructor(baseUrl = '') {
        this.baseUrl = baseUrl;
    }

    async getGraph(projectId = 1, filter = null, maxNodes = 500) {
        const params = new URLSearchParams({
            project_id: projectId,
            max_nodes: maxNodes,
        });
        if (filter) params.append('filter', filter);

        const response = await fetch(`${this.baseUrl}/api/graph?${params}`);
        if (!response.ok) {
            throw new Error(`API error: ${response.status}`);
        }
        return response.json();
    }

    async getNode(chunkId) {
        const params = new URLSearchParams({ chunk_id: chunkId });
        const response = await fetch(`${this.baseUrl}/api/node?${params}`);
        if (!response.ok) {
            throw new Error(`API error: ${response.status}`);
        }
        return response.json();
    }

    async getStats(projectId = 1) {
        const params = new URLSearchParams({ project_id: projectId });
        const response = await fetch(`${this.baseUrl}/api/stats?${params}`);
        if (!response.ok) {
            throw new Error(`API error: ${response.status}`);
        }
        return response.json();
    }
}

window.LuminaApi = LuminaApi;
