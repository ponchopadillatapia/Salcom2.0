import { McpServer } from "@modelcontextprotocol/sdk/server/mcp.js";
import { StdioServerTransport } from "@modelcontextprotocol/sdk/server/stdio.js";
import { z } from "zod";

const API_BASE = process.env.SALCOM_API_URL || "http://localhost:8000/api/salcom";
const API_TOKEN = process.env.SALCOM_API_TOKEN || "salcom-kiro-2026-secret";

async function callApi(endpoint, params = {}) {
  const url = new URL(`${API_BASE}${endpoint}`);
  for (const [key, value] of Object.entries(params)) {
    if (value !== undefined && value !== null && value !== "") {
      url.searchParams.set(key, value);
    }
  }
  const response = await fetch(url.toString(), {
    headers: { Authorization: `Bearer ${API_TOKEN}`, Accept: "application/json" },
  });
  if (!response.ok) throw new Error(`API error ${response.status}: ${await response.text()}`);
  return response.json();
}

async function patchApi(endpoint, body = {}) {
  const url = new URL(`${API_BASE}${endpoint}`);
  const response = await fetch(url.toString(), {
    method: "PATCH",
    headers: { Authorization: `Bearer ${API_TOKEN}`, Accept: "application/json", "Content-Type": "application/json" },
    body: JSON.stringify(body),
  });
  if (!response.ok) throw new Error(`API error ${response.status}: ${await response.text()}`);
  return response.json();
}

const ok = (data) => ({ content: [{ type: "text", text: JSON.stringify(data, null, 2) }] });

const server = new McpServer({ name: "salcom", version: "2.0.0" });

// ── Resumen general ──
server.tool("resumen", "Obtiene un resumen general de Industrias Salcom: totales de clientes, proveedores, pedidos, encuestas y productos", {},
  async () => ok(await callApi("/resumen")));

// ── Análisis y tendencias ──
server.tool("analisis", "Análisis completo del negocio: pedidos por mes, top clientes, productos con stock bajo, facturas vencidas y muestras activas", {},
  async () => ok(await callApi("/analisis")));

// ── Clientes ──
server.tool("clientes", "Lista los clientes de Salcom. Puede filtrar por nombre/correo y por estado activo/inactivo", {
  busqueda: z.string().optional().describe("Buscar por nombre, correo o código de cliente"),
  activo: z.boolean().optional().describe("Filtrar por estado: true=activos, false=inactivos"),
  limit: z.number().optional().describe("Máximo de resultados (default 50)"),
}, async ({ busqueda, activo, limit }) => ok(await callApi("/clientes", { busqueda, activo, limit })));

server.tool("cliente_detalle", "Detalle completo de un cliente: datos, pedidos, encuestas y facturas", {
  cliente_id: z.number().describe("ID del cliente"),
}, async ({ cliente_id }) => ok(await callApi(`/clientes/${cliente_id}`)));

// ── Proveedores ──
server.tool("proveedores", "Lista los proveedores de Salcom. Puede filtrar por nombre, correo o código de compras", {
  busqueda: z.string().optional().describe("Buscar por nombre, correo o código de compras"),
  limit: z.number().optional().describe("Máximo de resultados (default 50)"),
}, async ({ busqueda, limit }) => ok(await callApi("/proveedores", { busqueda, limit })));

server.tool("proveedor_detalle", "Detalle completo de un proveedor: datos, documentos fiscales, muestras y facturas", {
  proveedor_id: z.number().describe("ID del proveedor"),
}, async ({ proveedor_id }) => ok(await callApi(`/proveedores/${proveedor_id}`)));

// ── Pedidos ──
server.tool("pedidos", "Lista los pedidos de Salcom. Puede filtrar por estatus (validacion, procesando, enviado, entregado, cancelado) o por cliente", {
  estatus: z.string().optional().describe("Filtrar por estatus del pedido"),
  cliente: z.string().optional().describe("Filtrar por código de cliente"),
  limit: z.number().optional().describe("Máximo de resultados (default 50)"),
}, async ({ estatus, cliente, limit }) => ok(await callApi("/pedidos", { estatus, cliente, limit })));

server.tool("pedido_detalle", "Detalle de un pedido con tracking de envío y facturas asociadas", {
  pedido_id: z.number().describe("ID del pedido"),
}, async ({ pedido_id }) => ok(await callApi(`/pedidos/${pedido_id}`)));

// ── Productos ──
server.tool("productos", "Lista los productos del catálogo de Salcom. Puede buscar por nombre o código", {
  busqueda: z.string().optional().describe("Buscar por nombre o código de producto"),
  activo: z.boolean().optional().describe("Solo activos"),
  sin_stock: z.boolean().optional().describe("Solo productos sin stock"),
  limit: z.number().optional().describe("Máximo de resultados (default 50)"),
}, async ({ busqueda, activo, sin_stock, limit }) => ok(await callApi("/productos", { busqueda, activo, sin_stock, limit })));

server.tool("producto_detalle", "Detalle de un producto con pedidos recientes que lo incluyen", {
  producto_id: z.number().describe("ID del producto"),
}, async ({ producto_id }) => ok(await callApi(`/productos/${producto_id}`)));

// ── Facturas ──
server.tool("facturas", "Lista facturas. Puede filtrar por estatus, cliente, proveedor o solo vencidas", {
  estatus: z.string().optional().describe("Filtrar por estatus: pendiente, pagada, cancelada"),
  cliente: z.string().optional().describe("Filtrar por código de cliente"),
  proveedor: z.string().optional().describe("Filtrar por código de proveedor"),
  vencidas: z.boolean().optional().describe("Solo facturas vencidas"),
  limit: z.number().optional().describe("Máximo de resultados (default 50)"),
}, async ({ estatus, cliente, proveedor, vencidas, limit }) => ok(await callApi("/facturas", { estatus, cliente, proveedor, vencidas, limit })));

// ── Muestras ──
server.tool("muestras", "Lista muestras de materiales en proceso de validación. Puede filtrar por etapa o proveedor", {
  etapa: z.string().optional().describe("Filtrar por etapa: registro, recepcion, laboratorio, piso, estabilidad, aprobado, rechazado"),
  proveedor: z.string().optional().describe("Filtrar por nombre de proveedor"),
  limit: z.number().optional().describe("Máximo de resultados (default 50)"),
}, async ({ etapa, proveedor, limit }) => ok(await callApi("/muestras", { etapa, proveedor, limit })));

// ── Encuestas ──
server.tool("encuestas", "Obtiene las encuestas de satisfacción de clientes con promedios de calificación, tiempo de entrega y calidad", {
  cliente: z.string().optional().describe("Filtrar por código de cliente"),
  limit: z.number().optional().describe("Máximo de resultados (default 50)"),
}, async ({ cliente, limit }) => ok(await callApi("/encuestas", { cliente, limit })));

// ── Documentos de proveedores ──
server.tool("documentos", "Lista los documentos fiscales de proveedores (CIF, opinión, acta, INE, carátula banco). Puede filtrar por estatus, proveedor o tipo", {
  estatus: z.string().optional().describe("Filtrar por estatus: pendiente, aprobado, rechazado"),
  proveedor_id: z.number().optional().describe("Filtrar por ID del proveedor"),
  tipo: z.string().optional().describe("Filtrar por tipo: cif, opinion, acta, rep_legal, contribuyente, caratula_banco"),
  limit: z.number().optional().describe("Máximo de resultados (default 50)"),
}, async ({ estatus, proveedor_id, tipo, limit }) => ok(await callApi("/documentos", { estatus, proveedor_id, tipo, limit })));

server.tool("validar_documento", "Ejecuta la validación automática de un documento fiscal (extrae texto del PDF y verifica campos requeridos)", {
  documento_id: z.number().describe("ID del documento a validar"),
}, async ({ documento_id }) => ok(await callApi(`/documentos/${documento_id}/validar`)));

server.tool("revisar_documento", "Marca un documento como aprobado o rechazado con notas de revisión", {
  documento_id: z.number().describe("ID del documento a revisar"),
  estatus: z.enum(["aprobado", "rechazado"]).describe("Resultado: aprobado o rechazado"),
  notas: z.string().optional().describe("Notas de la revisión"),
}, async ({ documento_id, estatus, notas }) => ok(await patchApi(`/documentos/${documento_id}/revisar`, { estatus, notas })));

const transport = new StdioServerTransport();
await server.connect(transport);
