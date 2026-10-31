# MarkaRH

Sistema de asistencia laboral para empresas de Costa Rica. Se inició el código oficial del backend y del portal administrativo sobre el UX aprobado.

## Código oficial

- [Aplicación Laravel / PHP](apps/web_laravel)
- [Instalación y desarrollo](docs/DEVELOPMENT.md)
- [Estado real de implementación](docs/STATUS.md)
- [Arquitectura](docs/ARCHITECTURE.md) · [Modelo de datos](docs/DATA_MODEL.md)
- [Plan por fases](docs/ROADMAP.md) · [API de terminales](docs/API_SYNC.md)
- [Seguridad y privacidad](docs/SECURITY_PRIVACY.md) · [Validación cPanel pendiente](docs/DEPLOY_CPANEL.md)
- [Capturas de la base oficial](docs/capturas-oficiales)
- [Descargar código oficial desde GitHub](https://github.com/fisitalmultiservicios-svg/MarkaRH/archive/refs/heads/desarrollo-oficial.zip)

La base oficial usa sesiones, contraseñas hasheadas y persistencia del servidor. No utiliza el PIN ni los roles simulados del prototipo. El Maestro crea las empresas y emite credenciales de instalación de un solo uso. La API de terminal es independiente del login administrativo; la web no ofrece marcación de empleados.

**En desarrollo, no habilitado para uso laboral real.** MFA, apps nativas Windows/Android, almacenamiento local cifrado, firmas de terminal, cálculo laboral y validación cPanel siguen pendientes. Consulta el estado de cada capacidad antes de desplegar.

## Prototipo UX aprobado

El directorio `prototipo/` conserva la demostración visual independiente. Sus datos ficticios y el PIN `123456` pertenecen solo a la demo.

- [Ver web oficial de referencia](prototipo/capturas/01-web-oficial.png)
- [Ver marcador de referencia](prototipo/capturas/02-marcador.png)
- [Instrucciones del prototipo](prototipo/LEEME.md)
- [Descargar ZIP de la demo](https://github.com/fisitalmultiservicios-svg/MarkaRH/raw/refs/heads/prototipo-ux/MarkaRH-prototipo-UX.zip)

El marcador HTML es la representación de la futura app instalada. No se incorpora como marcador en el portal oficial. El activo vectorial de marca sigue siendo una recreación simplificada; se sustituirá al disponer del archivo maestro.
