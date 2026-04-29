# Respuestas para el equipo de AWS
**Contexto**: El equipo de AWS nos pidió información para dimensionar Bedrock, QuickSight y Textract.

Algunas respuestas las tenemos, otras Chuy necesita confirmar en planta. Las marqué.

---

## Usuarios y Portales

**¿Cuántos clientes (distribuidores) tendrían acceso al portal? ¿Y cuántos proveedores?**

> ⚠️ **Chuy necesita confirmar en planta** (preguntar a Ventas y Compras).
>
> Lo que sabemos: en el seeder tenemos 2 clientes y 3 proveedores de prueba. Necesitamos los números reales. Preguntarle a Ventas cuántos clientes activos tienen y a Compras cuántos proveedores activos.
>
> **Sugerencia de respuesta si no tienen el dato exacto**: "Estimamos entre 20-50 clientes y 30-80 proveedores en la primera fase, con crecimiento gradual."

**¿Cuántos usuarios concurrentes esperan en hora pico?**

> ⚠️ **Chuy necesita confirmar**.
>
> Considerando que es una empresa manufacturera, el uso más fuerte sería por la mañana (8-10 AM) cuando compras revisa OC, ventas revisa pedidos y proveedores consultan estatus.
>
> **Sugerencia**: "Estimamos 15-30 usuarios concurrentes en hora pico. Los proveedores entran a revisar OC y estatus de pagos, los clientes a consultar pedidos, y el equipo interno (compras, ventas, calidad) usa el panel admin."

---

## Reportes

**¿Cuántos reportes estiman que se descargarían al día?**

> ⚠️ **Chuy necesita confirmar** (preguntar a Dirección y Finanzas).
>
> **Sugerencia**: "Entre 5-15 reportes al día. Principalmente: estado de cuenta de clientes, historial de pagos a proveedores, reporte de pedidos del mes, y el dashboard ejecutivo."

**¿Tamaño promedio de un reporte?**

> Esto lo podemos estimar nosotros:
> - Reportes de listado (pedidos, facturas, clientes): **50-200 KB** (tablas con datos)
> - Reportes con gráficas (dashboard ejecutivo, QuickSight): **500 KB - 2 MB**
> - Reportes PDF exportados (estado de cuenta, factura): **100-500 KB**
>
> **Sugerencia de respuesta**: "Promedio de 500 KB. Los reportes con gráficas pueden llegar a 2 MB."

**¿Cuántos meses de datos históricos necesitan disponibles?**

> ⚠️ **Chuy necesita confirmar** (preguntar a Dirección).
>
> Para que la IA (Bedrock) haga pronósticos útiles necesita mínimo 6 meses. Para QuickSight comparativos año vs año necesita 12-24 meses.
>
> **Sugerencia**: "12 meses disponibles en línea para consulta rápida. Datos más antiguos en almacenamiento frío (S3 Glacier) por si se necesitan."

**¿Necesitan reportes batch o solo bajo demanda?**

> **Respuesta**: "Principalmente bajo demanda. El usuario entra al panel admin y genera el reporte que necesita. Podríamos agregar un reporte automático semanal por correo al director con el resumen de la semana (ventas, pedidos, score proveedores)."
>
> ⚠️ Confirmar con Dirección si quieren el reporte semanal automático.

**¿Cuánto tiempo quieren retener reportes generados?**

> **Sugerencia**: "90 días para reportes generados. Después se eliminan automáticamente. Los datos fuente se mantienen en la BD."

---

## Validación Documental

**¿Cuántos documentos nuevos se cargarían al mes?**

> ⚠️ **Chuy necesita confirmar** (preguntar a Compras — cuántos proveedores nuevos entran al mes y cuántos renuevan documentos).
>
> Cada proveedor sube 6 documentos (CIF, Opinión SAT, Acta Constitutiva, INE representante, INE contribuyente, Carátula banco). La Opinión SAT se renueva cada 3 meses.
>
> **Sugerencia**: "Si entran 5 proveedores nuevos al mes = 30 documentos nuevos. Más renovaciones de Opinión SAT de proveedores existentes. Estimamos 40-60 documentos al mes."

**¿Cuántas páginas promedio tiene cada documento?**

> Esto lo sabemos por el tipo de documento:
> - CIF (Constancia de Situación Fiscal): **1-2 páginas**
> - Opinión de Cumplimiento SAT: **1 página**
> - Acta Constitutiva: **10-30 páginas** (este es el más largo)
> - INE (representante y contribuyente): **1 página** cada uno (frente y vuelta)
> - Carátula bancaria: **1-2 páginas**
>
> **Respuesta**: "Promedio de 3-4 páginas por documento. La mayoría son 1-2 páginas, excepto el Acta Constitutiva que puede llegar a 30 páginas."

**¿Qué tipos de documentos van a validar?**

> **Respuesta** (esto ya lo tenemos implementado):
> 1. **Constancia de Situación Fiscal (CIF)** — se extrae RFC, nombre, régimen fiscal, domicilio
> 2. **Opinión de Cumplimiento del SAT** — se verifica que sea positiva y vigente
> 3. **Acta Constitutiva** — se verifica que sea persona moral, nombre de la empresa
> 4. **INE del representante legal** — se verifica que sea INE válida
> 5. **INE del contribuyente** — igual
> 6. **Carátula bancaria** — se verifica CLABE, banco, titular
>
> ⚠️ Confirmar con Compras/Calidad si hay documentos adicionales (certificaciones ISO, permisos COFEPRIS, pólizas de seguro).

**¿Cuánto tiempo necesitan retener los documentos cargados?**

> ⚠️ **Chuy necesita confirmar** (preguntar a Finanzas/Legal).
>
> Por regulación fiscal en México, los documentos fiscales se deben conservar 5 años. Los documentos de identidad (INE) mientras el proveedor esté activo.
>
> **Sugerencia**: "5 años para documentos fiscales (CIF, Opinión, Acta). Mientras el proveedor esté activo para INE y carátula bancaria."

---

## Lo que Chuy necesita preguntar en planta para completar esto

| Pregunta AWS | A quién preguntar | Qué necesitamos |
|---|---|---|
| Cuántos clientes tendrían acceso | Ventas | Número de clientes activos |
| Cuántos proveedores tendrían acceso | Compras | Número de proveedores activos |
| Usuarios concurrentes en hora pico | Dirección | Cuánta gente entra al sistema al mismo tiempo |
| Reportes al día | Dirección / Finanzas | Qué reportes generan hoy y con qué frecuencia |
| Meses de datos históricos | Dirección | Cuánto historial quieren consultar |
| Reporte semanal automático | Dirección | Si quieren un resumen semanal por correo |
| Documentos nuevos al mes | Compras | Cuántos proveedores nuevos entran al mes |
| Documentos adicionales | Compras / Calidad | Si piden algo más que los 6 que tenemos |
| Retención de documentos | Finanzas / Legal | Política de retención de documentos |

---

## Sobre los workshops que mandó AWS

Los links que mandaron son workshops para aprender a usar los servicios. Los relevantes para nosotros:

1. **Bedrock API** — Ya lo tenemos integrado con el AWS SDK. Cuando lleguen las credenciales solo ponemos los valores en `.env` y conecta.

2. **Bedrock Knowledge Bases & Agents** — Esto es para crear un chatbot que responda preguntas sobre documentos de Salcom. Podría ser útil para que el equipo interno pregunte cosas como "¿cuánto le compramos a Químicos del Norte el año pasado?" y la IA responda con datos reales. Es un siguiente paso después de conectar Bedrock básico.

3. **Document extraction con multimodal AI** — Alternativa a lo que ya hacemos con pdfparser + Tesseract. Bedrock puede extraer datos de PDFs directamente con IA multimodal (le mandas la imagen del documento y te regresa los campos). Podría mejorar la validación de documentos escaneados.

4. **QuickSight** — Para los dashboards ejecutivos con gráficas. Necesitamos datos reales primero (historial de ventas, pedidos, inventario). Una vez que conectemos la API de Alan, podemos alimentar QuickSight.

5. **Textract** — Extracción de texto de documentos. Similar a lo que hacemos con Tesseract OCR pero en la nube de AWS. Más preciso para documentos escaneados. Cobra por página procesada.
