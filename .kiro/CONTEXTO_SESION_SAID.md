# Contexto de trabajo — Salcom 2.0 (sesión Said, 22-24 sep 2026)

> Para pegar en un nuevo chat de Kiro y continuar sin perder el hilo.
> Proyecto: Laravel (PHP) + MySQL en XAMPP. Ruta: c:\Users\IT\Desktop\Salcom2.0
> La usuaria (Said) está APRENDIENDO a programar. Reglas: explicar en español, sencillo,
> "yo primero, IA después" en tareas simples, glosario "Para aprender" al final, y
> mantener la bitácora .kiro/DECISIONES.md.

## QUÉ ES EL PROYECTO
Sistema Salcom para gestionar PROVEEDORES, PRODUCTOS, PAGOS y más. Se conecta con
"Wiese" (sistema contable externo en C#/.NET, base SQL Server adSalcom18) vía una API.
Salcom = Wiese son la misma empresa; los proveedores REALES viven en Wiese.

## PERSONAS
- Said: la usuaria (aprendiendo, hace el frontend/PHP y también toca el C# para aprender).
- Alan: dueño de la API C# de Wiese. Solo aprueba PRs; deja que Said toque el C#.
- Karen: define reglas de negocio del flujo de pagos (pendiente consultarle varias cosas).
- Chino/Genaro: montan el servidor local en la empresa (El Salto).
- El dueño: quiere ver algo funcional el LUNES o no ve retornable la inversión.

## LO MÁS IMPORTANTE (bloqueo de red)
La API de Wiese vive en IP INTERNA 172.16.1.250:7186 → solo se alcanza con VPN de la
empresa. Producción (SiteGround) NO la alcanza. Alan NO va a exponerla a internet
(nunca, por seguridad). SOLUCIÓN acordada: montar un SERVIDOR LOCAL en la empresa
(Windows, IP 172.16.1.251, usuario Said / pwd Sa123) que SÍ está en la misma red que
Wiese. Chino lo está montando. Es lo que desbloquea TODO para el lunes.

## API DE WIESE (C#) — lo que Said ya logró
Endpoint: GET /api/ClienteProveedor/ListarProveedorWeb (login servicio: web / salcomweb1234).
La API es de 3 capas en C#:
- ENTIDAD: SDK.Entidades > Clases > PortalWeb > ProveedorWeb.cs
- CONSULTA: SDK.Backend > Consultas > PortalWeb > ConProveedorWeb.cs (el SELECT a admClientes)
- CONTROLLER: API > Controllers > ProveedorWebController.cs (el endpoint)

Campos que Said AGREGÓ al SELECT + entidad (aprendió a mapear columnas de Wiese):
- CCODIGOCLIENTE → Codigo (el CÓDIGO del proveedor; CLAVE para enlazar facturas). PR #101 aprobado.
- CIDMONEDA → Moneda (1=MXN, 2=USD). OJO: es int en SQL, en C# se lee con reader["CIDMONEDA"].ToString().
  (Este cambio Said lo subió; falta que Alan lo apruebe/publique — al probar aún salía vacío.)

Traía nombre (CRAZONSOCIAL) y RFC (CRFC) desde antes.

## HALLAZGOS CLAVE (confirmados con SQL en SSMS)
- Un mismo RFC puede tener VARIOS registros en admClientes. Causas: (1) MONEDA — un proveedor
  con cuenta MXN y otra USD (mismo RFC, distinto CCODIGOCLIENTE); (2) RFC genérico de
  EXTRANJEROS "XEXX010101000" (240+ registros lo comparten, no son el mismo); (3) duplicados
  reales (ej. ORPACK: 2 altas, M213015002 con 6,363 facturas vs 103015031 con 0).
- POR ESO el RFC NO sirve como identificador único; el CÓDIGO (CCODIGOCLIENTE) SÍ.
- Monedas: CIDMONEDA 1=MXN (4,795 provs), 2=USD (879 provs). Tabla admMonedas lo confirma.
- Datos bancarios: viven en tabla SEPARADA admCuentasBancarias (no en admClientes). PERO están
  casi vacíos: solo ~204 cuentas para 5,685 proveedores (5,514 SIN cuenta). Se decidió NO hacer
  el JOIN bancario (no aporta, el dato casi no existe; los bancarios se capturan en el onboarding).
  La liga sería admCuentasBancarias.CIDCATALOGO = admClientes.CIDCLIENTEPROVEEDOR PERO solo cuando
  CTIPOCATALOGO=1 (tipo 4 = cuentas de la propia empresa Salcom). Cuenta activa = CESTATUS=1.

## LO QUE YA SE HIZO EN EL PHP (Salcom) — todo en LOCAL, subido a git
1. `ProveedorApiService::listarProveedoresWiese()` — consume la API, trae ~5,685 proveedores.
2. Directorio de Proveedores (admin/proveedores) ahora muestra los REALES de Wiese, paginado 50,
   buscador por nombre/RFC, columnas Código + Nombre + RFC + Moneda, ordenado por código.
   Botón "Ver facturas" usa el código → trae OC/facturas reales (ORPACK = 6,363).
3. Filtros rápidos: Todos / Nacionales (MXN) / Dólar (USD) / Extranjeros. El de Extranjeros
   funciona ya (RFC XEXX010101000); MXN/USD funcionan cuando Alan publique cidmoneda.
4. FLUJO DE PAGOS (formato/pago/abono) ahora lista proveedores de Wiese:
   - Helper `AdminPagoProveedoresController::proveedoresParaFlujoPago()` trae los de Wiese en el
     formato que esperan las vistas (objeto con codigo, nombre, moneda, datos_identificacion.rfc).
     Si Wiese no responde, cae a proveedores locales (fallback, no rompe).
   - Se usó en abonoInterno() y create() (formato/pago).
   - store() del PAGO: ya NO exige proveedor_id local; ahora acepta codigo_proveedor (string).
     El proveedor local es opcional (puede venir de Wiese). Alertas al proveedor solo si existe local.
   - La vista pago-proveedores/form.blade.php: hidden codigo_proveedor, engancha por código,
     carga facturas por ?codigo=, y lee moneda del campo (no del método etiquetaMoneda que
     tronaba con objetos de Wiese).
5. LIMPIEZA/VISUAL: se borró el "Catálogo de Proveedores" (redundante). Se ocultaron del sidebar
   OC, Reportes, Clientes, Negocio, Fiscal (mostraban datos fake); se dejó OTIF. Se quitó el input
   de Migración del alta de producto. Input "Cuenta" en Abono autocarga la cuenta activa + readonly.
6. Alan (en su rama) agregó control de accesos por usuario: panel "Dirección"; Karen ve solo
   Pagos+Proveedores, Brenda solo Productos+Anticipos, Nayeli solo Reembolsos+Alta Empleados.

## INCIDENTE RESUELTO
MySQL de XAMPP se corrompió (instalación dañada a nivel motor: information_schema crasheaba).
Se reinicializó con mysql_install_db + se recreó salcom20 con `php artisan migrate` + `db:seed`.
Se subió innodb_buffer_pool_size a 256M en my.ini. Datos reales están también en producción.

## QUÉ FUNCIONA SIN WIESE (para el lunes, aunque no esté el servidor)
- Alta de PRODUCTO → 100% local, no usa Wiese.
- Alta/registro de PROVEEDOR → local; solo el paso 5 (confirmar cuenta Wiese por RFC) usa Wiese,
  y si falla solo muestra aviso, no rompe.
- Directorio de proveedores Wiese, ver facturas → SÍ necesitan Wiese (VPN o servidor local).

## PENDIENTES (hoja de ruta)
1. Que Alan APRUEBE/publique el cambio de cidmoneda (Said ya lo subió). Con eso los filtros
   MXN/USD y la columna Moneda funcionan solos.
2. SERVIDOR LOCAL (Chino) — prioridad #1 para el lunes. Es Windows 172.16.1.251. Falta montar
   XAMPP (PHP+MySQL+Apache) + clonar el proyecto + .env apuntando a Wiese interno + migrate/seed.
   Verificar que el servidor alcance Wiese abriendo http://172.16.1.250:7186/swagger/index.html
3. Flujo de pagos: ya lista y guarda con proveedores de Wiese (por código). FALTA probar de
   punta a punta en el navegador CON VPN (elegir proveedor de Wiese con facturas y guardar pago).
   NO se ha probado el guardado real todavía.
4. Preguntas de negocio para KAREN (flujo de pagos): cómo se genera el folio/póliza, validación
   de secuencia, a dónde regresa una factura al cancelar, si hay pagos parciales. Ver
   .kiro/steering/pendientes-flujo-pagos.md
5. Cómo dar de alta los 5,685 proveedores de Wiese para que vean su panel proveedor (registro
   uno por uno vs pre-carga masiva) — decisión de negocio con Alan.
6. Onboarding: BuscarPorRFC solo devuelve UNA cuenta; si se quiere manejar MXN+USD por RFC,
   Alan tendría que hacer que devuelva varias.

## CÓMO SE TRABAJA (git)
- Said sube su PHP a SU GitHub (ponchopadillatapia/Salcom2.0, rama main) cuando quiera.
- Al hacer pull suele haber CONFLICTO solo en .kiro/DECISIONES.md (ella y Alan editan las notas).
  Se resuelve conservando AMBAS versiones (quitar marcadores <<<< ==== >>>>).
- El C# es de Alan (repo aparte SalcomWiese/wiese); Said hace PR y Alan aprueba.

## REGLAS DE LA USUARIA (importantes)
- Responder en español, claro y sin adornos.
- Tareas SIMPLES (texto, color, quitar un botón): que las intente ELLA primero (regla "yo
  primero, IA después"), dando pistas, no la solución completa.
- Tareas COMPLEJAS (backend, varios archivos): hacerlas, explicando el PLAN antes y GLOSARIO después.
- Terminar cada cambio con sección "Para aprender" (2-4 conceptos en una línea, en español).
- Comentarios en código explican el POR QUÉ, no el qué.
- Actualizar .kiro/DECISIONES.md con cada cambio importante (fecha, qué, por qué, dónde).
