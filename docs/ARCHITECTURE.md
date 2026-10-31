# MarkaRH: arquitectura oficial

Referencia funcional: `PRODUCT_SPEC.md`, documento proporcionado por el propietario. El estado real de esta entrega se registra aparte en `STATUS.md`; las fases pendientes no se consideran implementadas por aparecer en la especificación.

## Estado y alcance

El repositorio inicial tenía un README. El directorio `prototipo/` conserva el UX aprobado con demostración local. No se reutilizan su PIN fijo, almacenamiento sin cifrar ni selector de roles como mecanismos de seguridad.

Se inicia una aplicación Laravel 13 / PHP 8.3 en `apps/web_laravel/`, con vistas Blade, sesiones de servidor y base relacional. MySQL 8.4 es el objetivo de desarrollo; MySQL/MariaDB y extensiones del cPanel deberán validarse en staging. SQLite puede ejecutar pruebas aisladas, pero no sustituye la validación MySQL.

## Límites y responsabilidades

- Sitio público: información institucional y acceso de administradores. No registra asistencia.
- Portal empresarial: empleados, sucursales y lectura de eventos recibidos, siempre según empresa de la sesión.
- Maestro: administración global de empresas, sin acceso implícito a evidencias de personal. MFA obligatorio: la contraseña sola no autoriza las rutas del Maestro. El enrolamiento, la verificación y la recuperación están separados de los recursos administrativos.
- Dispositivo: identidad y token propios, ligados a empresa y sucursal. La credencial de dispositivo no autentica una sesión administrativa.
- Futura aplicación Flutter Windows/Android: escritura local transaccional, cifrado y cola persistente. Ninguna interfaz web sustituye esa aplicación.

## Aislamiento

La empresa se deriva del usuario autenticado o del dispositivo autenticado. No se acepta `tenant_id` como autoridad enviada por el cliente. Consultas y relaciones se filtran por empresa; asociaciones a sucursales se validan dentro de esa empresa. Los identificadores ajenos devuelven 404. El Maestro dispone de rutas específicas, no de un bypass general a rutas empresariales.

## Evidencia y sincronización

Los eventos de origen son inmutables. Solo una terminal autenticada puede ingresar lotes; la operación debe ser idempotente por UUID y secuencia de terminal. El hecho y la interpretación laboral son entidades separadas. No se calcula presencia ni remuneración certificada con datos incompletos.

La primera entrega del backend usará tokens opacos de terminal almacenados como hashes, aprovisionamiento de un solo uso con caducidad y respuestas por evento. Firmas de eventos, rotación y autorización offline firmada requieren una fase adicional antes de operar terminales reales.

## Operación

HTTPS obligatorio en producción. Sin WebSockets ni workers residentes como dependencia de cPanel. Sincronización iniciada por cliente y cron posterior para mantenimiento. Fotos fuera del directorio público. No se guardan secretos en Git. Las migraciones y pruebas se ejecutan antes de declarar capacidades terminadas.
