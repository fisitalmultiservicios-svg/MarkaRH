# API de terminales v1 — base inicial

Prefijo: `/api/v1/device`. HTTPS obligatorio fuera de desarrollo. Los administradores web no crean eventos de empleado.

## Enrolamiento

`POST /enroll` recibe `enrollment_code` y `name`. El código se emite desde la vista Maestro para una sucursal, tiene caducidad y se consume de forma atómica una sola vez. Devuelve el token de dispositivo una única vez. Se almacenan hashes del código y token. Nunca registrar códigos ni tokens en logs.

## Terminal autenticada

`Authorization: Bearer <device_token>` solo en las rutas de terminal. La empresa y sucursal se derivan de ese token. Terminal revocada: 401. No se aceptan identificadores de empresa suministrados por el cliente como autoridad.

- `GET /config`: identidad, sucursal y configuración mínima. No devuelve datos salariales.
- `POST /events/batch`: máximo 100 eventos. Cada evento contiene UUID (normalizado a minúsculas), secuencia positiva, empleado, acción y fecha local con zona. Esta versión inicial admite fechas entre 2000 y 2100; no certifica la veracidad del reloj local. Acciones: `IN`, `OUT`, `BREAK_START`, `BREAK_END`, `AUTO_PUNCH`.

Resultados por evento: `accepted`, `duplicate` o `rejected_with_reason`. Un reintento con el mismo UUID y evidencia devuelve duplicate; un UUID o secuencia con contenido diferente se rechaza, sin sobrescribir. El cliente no debe eliminar su copia hasta persistir la confirmación correspondiente. Una confirmación de este backend no certifica la hora local ni una interpretación laboral.

## Pendientes del protocolo de producción

Firma de eventos y lotes, autorización offline firmada, rotación/recuperación de claves, sincronización incremental y catálogo de verificadores PIN, fotografías privadas, verificación de reloj y cargas paginadas. Esta API inicial no habilita clientes no homologados para uso laboral real.
