# Conceptos de Contpaqi (CIDDOCUMENTODE) — referencia Salcom/Wiese

> Sacado de la tabla `admConceptos` de la base de Wiese (adSalcom18), 27-oct-2026.
> Sirve para entender el parámetro `CIDDOCUMENTODE` que agregó Alan al APIWIESE.

## Concepto clave: dos niveles

- **CIDDOCUMENTODE** = tipo GENERAL de documento (ej. 17 = todas las OC).
- **CCODIGOCONCEPTO** = subtipo ESPECÍFICO dentro de ese tipo (ej. 19 = OC Materia Prima).

⚠️ OJO: el número 19 significa DOS cosas según la columna:
- `CIDDOCUMENTODE = 19` → "Compra" (factura de compra del proveedor).
- `CCODIGOCONCEPTO = 19` → "Orden de Compra M.P." (vive bajo el tipo 17).
Son columnas distintas. No confundir.

## Tabla de CIDDOCUMENTODE (los que importan)

| CIDDOCUMENTODE | Qué es | Para qué sirve |
|---|---|---|
| 1  | Cotización | — |
| 2  | Pedido | — |
| 3  | Remisión | — |
| 4  | **Factura** (venta a cliente) | ventas |
| 17 | **Orden de Compra (OC)** | ⭐ flujo nuevo: comparar OC del proveedor |
| 19 | **Compra** (factura de compra del proveedor) | ⭐ facturas que paga el proveedor |
| 23 | **Pago al proveedor** | pagos |
| 26 | Abonos / Anticipos a proveedor (BBVA, HSBC...) | anticipos |
| 32 | **Entrada de almacén** (MP, PT...) | ⭐ "lo que llegó a Acatlán" (recepción física) |
| 33 | Salida de almacén | inventario |

## Detalle del 17 (ÓRDENES DE COMPRA) — por CCODIGOCONCEPTO
| Código | OC |
|---|---|
| 19  | OC Materia Prima |
| 61  | OC Producto Terminado |
| 119 | OC Importación |
| OEPP | OC EPP |
| OCMTTO | OC Mantenimiento |

## Detalle del 19 (COMPRAS / facturas de compra) — por CCODIGOCONCEPTO
| Código | Compra |
|---|---|
| 21  | Compra (la normal) |
| 101 | Compras Importación |
| 126 | Fletes |
| 127 | Mantenimiento |
| 130 | Gastos |
| (y varios más: 121,125,128,129,131,132,133) |

## Cómo conecta con el flujo nuevo de facturas + OC
- Proveedor sube factura (PDF+XML) + OC (PDF).
- Para validar la OC contra Wiese → pedir al API documentos con **CIDDOCUMENTODE = 17** (OC).
- Para validar la factura de compra → **CIDDOCUMENTODE = 19**.
- Para confirmar "lo que llegó a Acatlán" → **CIDDOCUMENTODE = 32** (entradas de almacén).
- El API de Alan (APIWIESE) ahora acepta ese parámetro; antes estaba fijo en 19.

## Las 3 APIs (quién es quién)
- **APIWIESE** (servidor 250, 172.16.1.250:7183): lee directo la BASE de Wiese/Contpaqi (SQL).
- **APIPortalWeb** (servidor 251, 172.16.1.251:5443 HTTPS): GATEWAY/puente; reenvía al APIWIESE.
  Es "mi servicio" (el que aseguramos con JWT+HTTPS).
- **Salcom (Laravel, SiteGround)**: el CLIENTE que consume el APIPortalWeb.
Flujo: Salcom → APIPortalWeb (251) → APIWIESE (250) → base SQL Contpaqi.
