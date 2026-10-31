# Desarrollo oficial

## Organización

- `apps/web_laravel`: código del backend y del sitio oficial (Laravel 13 / PHP 8.3).
- `infra/docker/php` y `compose.yml`: PHP y MySQL de desarrollo.
- `packages/contracts`: contrato inicial de terminales.
- `docs`: decisiones, estado y fases.
- `prototipo`: experiencia aprobada, independiente de autenticación y datos oficiales.

Cada tarea ya dispone de un entorno aislado. Trabaja en el checkout existente; no crear worktrees salvo solicitud explícita.

## Linux / entorno cloud con Docker

Se requieren Docker y Compose, socket local disponible y el bundle de CA en `/etc/ssl/certs/ca-certificates.crt`. El Dockerfile conserva verificación TLS y usa el bundle durante pasos de red. Las variables del proxy administrado se mantienen. No copiar credenciales del proxy ni certificados de la sesión a imágenes.

```sh
./scripts/setup-dev
./scripts/php php artisan markarh:create-master
docker compose up -d app
```

`setup-dev` genera secretos aleatorios locales solo cuando `.env` no existe, instala desde `composer.lock`, conserva APP_KEY y aplica migraciones. La instalación usa `--prefer-source` porque el entorno restringe `api.github.com`; GitHub Git y Packagist siguen disponibles. En equipos con descargas de archivos habilitadas, `composer install --prefer-dist` también es válido.

La aplicación se sirve en el puerto local 8001. La base MySQL no se publica al host. Las credenciales de desarrollo se guardan en archivos ignorados; no hay una contraseña maestra predeterminada. Ejecuta el comando interactivo para crear el primer Maestro sin pasar contraseñas en argumentos.

## Pruebas

Las pruebas usan bases aisladas. SQLite en memoria y MySQL `markarh_test` son los únicos destinos permitidos por el arranque de las pruebas. No apuntar las pruebas a una base de operación real.

La base MySQL de pruebas se crea separadamente y se concede acceso al usuario de desarrollo. Consulta el script `scripts/test-backend` para ejecutarlas. La suite incluye autenticación, separación de roles y empresas, enrolamiento de un uso e ingesta idempotente. CI usa los mismos dos motores.

## Rutas principales

- `/`: web oficial.
- `/acceso`: login administrativo; no hay registro público de administradores.
- `/maestro`: empresas y autorización de instalaciones.
- `/admin`: dashboard empresarial; personal, sucursales, dispositivos, registros y bitácora.
- `/api/v1/device/*`: autenticación de dispositivo independiente de la sesión web.

## Antes de desplegar

Revisar `STATUS.md`, `SECURITY_PRIVACY.md` y `DEPLOY_CPANEL.md`. Esta entrega no está aprobada para operar asistencia real ni se ha desplegado a cPanel.

## Recorrido opcional en navegador

Con Playwright y Chromium ya disponibles en la máquina de pruebas, se puede ejecutar el recorrido `apps/web_laravel/tests/Browser/smoke.py`. Preparar primero la base dedicada con `APP_ENV=testing`, `DB_DATABASE=markarh_test` y `php tests/Browser/seed.php`: el script verifica ese destino y restablece exclusivamente esa base. No ejecutarlo mientras otra suite use la misma base.

La instancia HTTP del recorrido debe usar `DB_DATABASE=markarh_test`, puerto local 8002 y `APP_ENV=local`, para verificar el middleware CSRF real sin el bypass propio de unit tests. Iniciar con `php artisan serve --host=0.0.0.0 --port=8000 --no-reload` dentro del contenedor y publicar solo el loopback 8002. `--no-reload` conserva las variables DB inyectadas por Docker. El script registra capturas en `docs/capturas-oficiales/`.

Los usuarios conocidos del archivo de fixtures solo se crean en `markarh_test`; no constituyen credenciales predeterminadas del producto. Detener la instancia del recorrido cuando termine. No hace falta instalar paquetes de navegador para ejecutar las pruebas PHPUnit.

Boost se mantiene como herramienta de desarrollo. Su inyector de logs de navegador está deshabilitado para respetar la política CSP, sin permitir scripts inline.


## Primer acceso y seguridad del Maestro

Después de crear el Maestro con `./scripts/php php artisan markarh:create-master`, ingresa por `/acceso`. El primer acceso dirige a `/seguridad/configurar`: agrega la cuenta en un autenticador compatible, confirma su código y guarda los diez códigos de recuperación fuera del teléfono. Ningún recurso del Maestro queda disponible con la contraseña sola.

Los siguientes accesos requieren el código actual o un código de recuperación aún no usado. En `/seguridad` se puede renovar el respaldo o cambiar de autenticador con contraseña y verificación adicional. Si se ingresa mediante recuperación, hay cinco minutos para confirmar la contraseña y vincular otro teléfono. No existe un bypass de MFA ni recuperación pública cuando se han perdido ambos factores.

Para actualizar una instalación existente, instala desde el lockfile y aplica las migraciones sin regenerar APP_KEY. Conserva copias seguras de esa clave. El protocolo web requiere HTTPS en producción y reloj de servidor sincronizado. Consulta `SECURITY_PRIVACY.md` para límites de tiempo, cifrado e invalidación.

La fixture del navegador configura un autenticador conocido solo para el Maestro ficticio de `markarh_test`. El test genera sus códigos mediante una referencia RFC 6238 independiente, comprueba recuperación y enrolamiento nuevos y evita capturar claves o códigos de respaldo en las imágenes guardadas.
