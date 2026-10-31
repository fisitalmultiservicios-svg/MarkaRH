# Seguridad y privacidad — base oficial

Sesiones administrativas en servidor, contraseñas hasheadas, regeneración de sesión, CSRF y límites de intentos. La empresa se deriva de la identidad autenticada. La API de terminal utiliza tokens independientes y no acepta una sesión administrativa como autorización de dispositivo.

## MFA del Maestro

Toda ruta del Maestro exige contraseña y MFA. Los Maestros nuevos y las sesiones anteriores a esta actualización deben configurar el autenticador antes de operar. No hay una opción para desactivar MFA ni una ruta de omisión.

- TOTP estándar de seis dígitos, período de 30 segundos y tolerancia de un intervalo a cada lado. Se conserva el último intervalo aceptado, bajo bloqueo de la fila de usuario, para impedir replay incluso desde otra sesión.
- Generación de secretos y verificación mediante `pragmarx/google2fa` 9.1.0. QR SVG generado localmente con `bacon/bacon-qr-code` 3.1.1, sin enviar la clave a un servicio de QR externo.
- Secreto TOTP cifrado con APP_KEY. Diez códigos de recuperación aleatorios de 128 bits, almacenados como hashes SHA-256 dentro de una colección cifrada; cada uso elimina un hash bajo el mismo bloqueo de usuario.
- Los códigos nuevos se presentan una sola vez y pueden descargarse localmente. Las pantallas privadas usan `Cache-Control: no-store`. No registrar códigos, contraseñas, secretos ni QR de aprovisionamiento en logs o capturas.
- La contraseña crea una sesión pendiente que vence en diez minutos. La prueba MFA tiene vigencia máxima de ocho horas, además de la caducidad normal de la sesión. La prueba se liga a usuario, versión de seguridad y huella del hash de contraseña.
- Se permiten cinco fallos de verificación por usuario en cinco minutos, compartidos entre autenticador y recuperación, con límites adicionales de solicitudes por IP.
- Renovar códigos o cambiar de autenticador requiere confirmar contraseña y un código nuevo. Un código de recuperación recién utilizado permite esa gestión durante cinco minutos; renovar el respaldo no extiende ese plazo.
- Cambiar de autenticador mantiene bloqueado el panel hasta confirmar el nuevo secreto. La renovación de respaldo o reconfiguración incrementa la versión de seguridad e invalida las verificaciones MFA de otras sesiones. Los cambios de contraseña también invalidan la prueba MFA.
- Las acciones y los intentos fallidos se auditan sin incluir los códigos. Campos de seguridad ocultos en la serialización del usuario y fuera de la asignación masiva de formularios.

Los códigos de recuperación no sustituyen la contraseña. Si se pierden tanto el autenticador como todos los códigos, no hay restablecimiento público automático en esta entrega: se necesita un procedimiento autorizado de recuperación de identidad, todavía pendiente. No improvisar un bypass ni desactivar el middleware.

APP_KEY debe conservarse y respaldarse de forma segura. Una rotación necesita un procedimiento de recifrado y recuperación. El reloj de servidor debe estar sincronizado por NTP; TOTP no reemplaza HTTPS ni protege por sí solo frente a phishing de códigos en tiempo real.

Este MFA protege el acceso web del Maestro. Es independiente del PIN del empleado y de los futuros tokens de verificación offline del marcador. MFA opcional para administradores empresariales queda para una fase posterior.

## Operación y privacidad pendientes

No hay credenciales por defecto en el seeder normal. El comando interactivo crea el primer Maestro y no expone su contraseña en argumentos. Las cuentas y secretos conocidos de los tests solo se crean en la base aislada `markarh_test`.

Fotografías, biometría y almacenamiento del marcador no están implementados. Definir base jurídica, avisos, retención y eliminación con revisión de Ley 8968 antes de producción. No usar datos reales en el entorno de pruebas.

Siguen pendientes: recuperación de contraseña y procedimientos de identidad, permisos por módulo/sucursal, gestión completa de sesiones, protección criptográfica y recuperación de terminales, pruebas de carga y revisión independiente. El entorno HTTP de desarrollo no se debe exponer públicamente.
