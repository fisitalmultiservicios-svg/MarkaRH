# Estado de implementación — primera entrega oficial

Esta es la base funcional inicial de la fase 1. No representa el producto completo ni una versión autorizada para operación laboral real.

## Implementado y verificado

- Laravel 13.39.0, PHP 8.3.36, dependencias fijadas en composer.lock y entorno Docker de desarrollo con MySQL 8.4.
- Sitio público basado en el diseño gráfico aprobado, pestañas de producto y formulario que persiste consultas en servidor. Consulta de mensajes reservada al Maestro. No se envían correos.
- Acceso administrativo real con contraseñas hasheadas, sesiones regeneradas, CSRF, límites de intentos y estados de empresa.
- Creación interactiva del primer Maestro, sin contraseña por defecto. El seeder normal no crea cuentas demo.
- Maestro: alta de empresa, sucursal inicial y administrador; suspensión y activación; emisión de credenciales de instalación de un solo uso con vigencia de 15 minutos.
- Empresa: sucursales, alta/edición/desactivación y búsqueda de empleados, listado/revocación de terminales, consulta de eventos recibidos y bitácora.
- Aislamiento de consultas y asociaciones por empresa. Identificadores empresariales ajenos no revelan su existencia.
- Enrolamiento de terminal mediante credencial temporal; token opaco de dispositivo almacenado como hash y autenticación independiente de la sesión web.
- Ingesta de lotes de hasta 100 hechos, resultados por evento, normalización de UUID, deduplicación y rechazo de conflictos de identidad/secuencia sin sobrescritura.
- Preservación del instante local original con offset y recepción UTC separada. No se certifica el reloj local ni se infieren horas.
- La web pública y administrativa no ofrecen rutas de marcación de empleados.
- Prototipo UX anterior conservado en un directorio independiente.

## Evidencia de validación

- SQLite en memoria: **29 pruebas, 194 comprobaciones, aprobadas**.
- MySQL de testing separado: **29 pruebas, 194 comprobaciones, aprobadas**.
- Pint: **52 archivos PHP comprobados**, sin diferencias de estilo pendientes.
- Navegador Chromium: sitio y pestañas, login/logout con sesión, alta persistente de empleado y empresa, rechazo CSRF con HTTP 419 y rechazo de sesión web ante API de terminal. Sin errores JavaScript ni violaciones CSP durante el recorrido final.
- Adaptación del sitio y acceso a 390, 768 y 1440 píxeles, sin desbordamiento horizontal.
- Script de preparación ejecutado nuevamente: conservó la configuración y APP_KEY existentes, sin migraciones pendientes.
- Auditoría de Composer sin avisos de vulnerabilidades conocidos en las dependencias instaladas. El manifiesto no declara todavía una licencia de distribución propia.
- Las capturas en `capturas-oficiales/` proceden de una base de testing con datos ficticios; no prueban actividad de una empresa real.

El workflow de GitHub Actions queda configurado para ambos motores. Su ejecución remota se debe consultar en GitHub; las comprobaciones anteriores fueron ejecutadas en este entorno.

## Pendiente antes de producción

MFA obligatorio para Maestro; recuperación y gestión de cuentas; permisos delegados por módulo/sucursal; cupos y licencias comerciales; identidad criptográfica, firma, rotación y autorización offline de terminales; catálogo/versiones y sincronización de verificadores PIN; fotos y evidencias privadas; recuperación de colas tras revocación; historial de asignaciones para conciliar eventos de empleados trasladados o dados de baja; backups y restauración probados; evaluación de privacidad, carga y seguridad; staging cPanel real.

La API inicial no sustituye un protocolo completo de dispositivos homologados. Un rechazo no autoriza al futuro cliente a borrar su copia local. Actualmente se valida el estado y sucursal vigentes del empleado; el historial de autorizaciones offline sigue pendiente.

## Fases siguientes

1. Completar seguridad y operación de la fase 1, especialmente MFA y permisos.
2. Implementar el marcador Flutter Windows/Android con almacenamiento cifrado y outbox persistente. No hay instaladores nativos disponibles en esta entrega.
3. Conciliación, turnos, correcciones append-only y reportes.
4. Reglas laborales CR versionadas y revisadas, permisos/vacaciones, extras y preplanilla.
5. Foto, token, hardware biométrico homologado, CMS y suscripciones.

No se desplegó a cPanel ni se publicaron instaladores. La marca vectorial sigue siendo una recreación del logotipo de referencia, pendiente del activo maestro.
