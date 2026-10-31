# Plan por fases

## 0. Base y UX

Prototipo aprobado y preservado. Arquitectura, límites de permisos, esquema inicial y contrato de terminales versionado. No hay estimaciones cerradas ni afirmación de compatibilidad de hardware no probado.

## 1. Portal y backend inicial — en ejecución

- Laravel/PHP, migraciones, configuración reproducible y pruebas.
- Login administrativo con sesiones, protección CSRF y limitación de intentos.
- MFA obligatorio para Maestro: configurado y probado, con respaldo de un solo uso.
- Empresas, sucursales, empleados y separación Maestro/administrador.
- Eventos recibidos de terminal, sin creación desde el portal.
- Aprovisionamiento de un solo uso, tokens de dispositivo hasheados e ingesta idempotente.
- Auditoría de acciones sensibles.

Antes de producción: permisos delegados por sucursal, recuperación de cuentas, control de sesiones, pruebas de carga, rotación de credenciales, claves/firma de terminal y staging cPanel. El alcance efectivamente completado se registra en `STATUS.md`.

## 2. Marcador nativo

Flutter Windows/Android, SQLite cifrado, identidad de terminal, PIN seguro local, outbox, relojes y recuperación tras reinicio/corte. Ejecutar pruebas reales en ambas plataformas; el prototipo HTML no satisface esta fase.

## 3. Conciliación y operación laboral

Turnos, conflictos, rectificaciones append-only, vacaciones, extras y reportes. Reglas costarricenses versionadas y revisadas por especialista. No inferir intervalos ambiguos ni ausencias por falta de sincronización.

## 4. Verificaciones

Foto y token offline con política de fallas. Huella solo con lector externo y SDK homologados; sin biometría simulada.

## 5. Comercialización

CMS completo, planes, suscripciones, instaladores firmados, actualizaciones seguras y validación integral de privacidad, respaldo y recuperación. Preplanilla/aguinaldo después de incorporar remuneraciones reales y revisión legal.
