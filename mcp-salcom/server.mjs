import { McpServer } from "@modelcontextprotocol/sdk/server/mcp.js";
import { StdioServerTransport } from "@modelcontextprotocol/sdk/server/stdio.js";
import { z } from "zod";

const API_BASE = process.env.SALCOM_API_URL || "http://localhost:8000/api/salcom";
const API_TOKEN = process.env.SALCOM_API_TOKEN || "salcom-kiro-2026-secret";

// ── Helper para llamar la API ──
async function callApi(endpoint, params = {}) {
  const url = new URL(`${API_BASE}${endpoint}`);
  for (const [key, value] of Object.entries(params)) {
    if (value !== undefined && value !== null && value !== "") {
      url.searchParams.set(key, value);
    }
  }

  const response = await fetch(url.toString(), {
    headers: {
      Authorization: `Bearer ${API_TOKEN}`,
      Accept: "application/json",
    },
  });

  if (!response.ok) {
    const text = await response.text();
    throw new Error(`API error ${response.status}: ${text}`);
  }

  return response.json();
}

// ── Crear servidor MCP ──
const server = new McpServer({
  name: "salcom",
  version: "1.0.0",
});

// ── Tool: Resumen general ──
server.tool(
  "resumen",
  "Obtiene un resumen general de Industrias Salcom: totales de clientes, proveedores, pedidos, encuestas y productos",
  {},
  async () => {
    const data = await callApi("/resumen");
    return { content: [{ type: "text", text: JSON.stringify(data, null, 2) }] };
  }
);

// ── Tool: Listar clientes ──
server.tool(
  "clientes",
  "Lista los clientes de Salcom. Puede filtrar por nombre/correo y por estado activo/inactivo",
  {
    busqueda: z.string().optional().describe("Buscar por nombre, correo o código de cliente"),
    activo: z.boolean().optional().describe("Filtrar por estado: true=activos, false=inactivos"),
    limit: z.number().optional().describe("Máximo de resultados (default 50)"),
  },
  async ({ busqueda, activo, limit }) => {
    const data = await callApi("/clientes", { busqueda, activo, limit });
    return { content: [{ type: "text", text: JSON.stringify(data, null, 2) }] };
  }
);

// ── Tool: Listar proveedores ──
server.tool(
  "proveedores",
  "Lista los proveedores de Salcom. Puede filtrar por nombre, correo o código de compras",
  {
    busqueda: z.string().optional().describe("Buscar por nombre, correo o código de compras"),
    limit: z.number().optional().describe("Máximo de resultados (default 50)"),
  },
  async ({ busqueda, limit }) => {
    const data = await callApi("/proveedores", { busqueda, limit });
    return { content: [{ type: "text", text: JSON.stringify(data, null, 2) }] };
  }
);

// ── Tool: Listar pedidos ──
server.tool(
  "pedidos",
  "Lista los pedidos de Salcom. Puede filtrar por estatus (validacion, procesando, enviado, entregado, cancelado) o por cliente",
  {
    estatus: z.string().optional().describe("Filtrar por estatus del pedido"),
    cliente: z.string().optional().describe("Filtrar por código de cliente"),
    limit: z.number().optional().describe("Máximo de resultados (default 50)"),
  },
  async ({ estatus, cliente, limit }) => {
    const data = await callApi("/pedidos", { estatus, cliente, limit });
    return { content: [{ type: "text", text: JSON.stringify(data, null, 2) }] };
  }
);

// ── Tool: Listar productos ──
server.tool(
  "productos",
  "Lista los productos del catálogo de Salcom. Puede buscar por nombre o código",
  {
    busqueda: z.string().optional().describe("Buscar por nombre o código de producto"),
    limit: z.number().optional().describe("Máximo de resultados (default 50)"),
  },
  async ({ busqueda, limit }) => {
    const data = await callApi("/productos", { busqueda, limit });
    return { content: [{ type: "text", text: JSON.stringify(data, null, 2) }] };
  }
);

// ── Tool: Ver encuestas ──
server.tool(
  "encuestas",
  "Obtiene las encuestas de satisfacción de clientes con promedios de calificación, tiempo de entrega y calidad",
  {
    cliente: z.string().optional().describe("Filtrar por código de cliente"),
    limit: z.number().optional().describe("Máximo de resultados (default 50)"),
  },
  async ({ cliente, limit }) => {
    const data = await callApi("/encuestas", { cliente, limit });
    return { content: [{ type: "text", text: JSON.stringify(data, null, 2) }] };
  }
);

// ── Iniciar servidor ──
const transport = new StdioServerTransport();
await server.connect(transport);
