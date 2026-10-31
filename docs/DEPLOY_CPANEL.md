# Despliegue cPanel — lista de validación pendiente

Esta base no ha sido desplegada ni certificada en un hosting cPanel. Validar PHP web y CLI >=8.3, Laravel 13, extensiones PDO MySQL, mbstring, intl, openssl, fileinfo, XML/DOM, zip y límites de ejecución.

La raíz del dominio debe apuntar exclusivamente a `apps/web_laravel/public`. Nunca exponer `.env`, `vendor`, `storage`, bases SQLite ni respaldos. Mantener `APP_DEBUG=false`, `APP_ENV=production`, `APP_URL=https://...` y cookies seguras con HTTPS.

Preparar MySQL/MariaDB, credenciales de mínimo privilegio, copia de seguridad y restauración probadas. Instalar desde composer.lock con `composer install --no-dev --prefer-dist --optimize-autoloader`, generar APP_KEY una sola vez, configurar permisos de storage/bootstrap/cache y aplicar migraciones después del respaldo. No cambiar APP_KEY sin plan de rotación.

No habilitar operación laboral real hasta completar MFA Maestro, pruebas de aislamiento y revisión de seguridad. Las tareas cron y la sincronización de terminales se incorporarán según sus respectivas fases; no se requiere WebSocket ni worker residente para esta base.
