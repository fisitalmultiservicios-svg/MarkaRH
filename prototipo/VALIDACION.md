# Validación de la entrega UX

## Rediseño visual 2

Se ejecutaron las pruebas de regresión generales, las del HTML autónomo por HTTP y un recorrido específico del nuevo diseño. Se verificaron búsqueda por tarjeta y estado sin resultados, progreso de tres pasos, selección de evento, limpieza de PIN al cambiar de persona, regreso a entrada como opción inicial, pestañas del sitio con teclado y vista del marcador identificado a 390, 600, 768, 1024 y 1440 píxeles. También se comprobó ausencia de desbordamiento horizontal con texto ampliado y alto contraste.

## Resultado

Las páginas separadas y el HTML autónomo servido por HTTP pasaron las pruebas funcionales en Chromium. Se inspeccionaron capturas de escritorio y móvil. No se detectaron errores JavaScript durante los recorridos automatizados.

## Verificado

- Sitio público: consulta comercial local y contenido de inicio.
- Marcador: empleado requerido, PIN erróneo y válido, teclado táctil, evento persistente tras recargar, guardado pendiente, sincronización demo sin duplicados y recarga con navegador sin conexión una vez cacheado.
- Tipo automático: oculta las acciones explícitas y conserva el hecho como observado, sin inferir horas.
- Personal: crear, editar, buscar y desactivar conservando historial.
- Conciliación: guardar revisión con motivo sin modificar los eventos originales.
- Turnos y sucursales: alta funcional.
- Solicitudes: registro y aprobación de permisos/vacaciones.
- Horas extra: solicitud y decisión separadas del trabajo efectivo y del pago.
- Reportes: filtros, descarga CSV y contenido de registros exportados.
- Preplanilla: estado incompleto y exportación de pendientes sin importes inventados.
- Terminal: revocación demo impide registrar nuevos eventos.
- Maestro: catálogo de empresas y alta, consultas recibidas y edición del sitio.
- CMS: guardar borrador sin publicar, publicar demo y persistencia entre páginas.
- Texto del CMS: caracteres HTML se muestran como texto, sin crear elementos ejecutables.
- Adaptación: sin desbordamiento horizontal de la página a 390, 768, 1024 y 1440 píxeles. Las tablas mantienen desplazamiento interno cuando corresponde.
- HTML autónomo por HTTP: navegación y acciones principales sin solicitar CSS, JS, imágenes ni fuentes externas.

## Límites de la comprobación

La apertura directa `file://` se omitió porque la política del navegador administrado la bloquea con `ERR_BLOCKED_BY_ADMINISTRATOR`. No se eludió esa política. La versión autónoma se comprobó por HTTP.

El botón PDF invoca el diálogo de impresión del navegador; no se automatizó la interacción con dicho diálogo. No se prueba un exportador XLSX ni existen instaladores nativos.

No se validan seguridad de producción, cifrado, backend, identidad de equipos, hardware, cálculos jurídicos ni comportamiento de una app Windows/Android. Esas capacidades pertenecen al desarrollo posterior.

## Reproducción

Ver `LEEME.md`. Las pruebas usan contextos de navegador aislados y datos ficticios; no alteran la sesión del usuario. Los archivos generados de las pruebas son las capturas en `capturas/`.
