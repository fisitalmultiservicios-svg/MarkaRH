# Seguridad y privacidad — base inicial

Sesiones administrativas en servidor, contraseñas hasheadas, regeneración de sesión al autenticar, CSRF en formularios y limitación de login. La API de terminal utiliza autenticación independiente con tokens hasheados. La asociación de empresa se deriva de las identidades autenticadas.

No hay credenciales por defecto en el seeder. Un comando interactivo crea el primer Maestro sin exponer la contraseña en argumentos. El seeder de pruebas usa datos exclusivos del entorno de test.

No registrar tokens, contraseñas o PIN. No usar el almacenamiento del prototipo para datos reales. Fotografías y biometría no están implementadas. Definir consentimiento/base legal, retención, eliminación y respaldo con revisión de Ley 8968 antes de producción.

Pendientes que impiden operar en producción: MFA obligatorio Maestro, recuperación de cuentas, permisos por módulo/sucursal, sesiones/revocación, protección criptográfica completa de terminal, pruebas de carga y revisión de seguridad independiente. El entorno HTTP de desarrollo no se debe exponer públicamente.
