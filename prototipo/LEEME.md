# MarkaRH · Prototipo UX interactivo

Prototipo previo al desarrollo del sistema, basado en la especificación funcional proporcionada. HTML, CSS y JavaScript sin dependencias de red, frameworks ni compilación.

## Abrir la demostración

**Opción sencilla:** abre `MarkaRH-demo.html` en Chrome o Edge. Contiene las tres experiencias en un único archivo, incluido el logo vectorial. Permite navegar entre web, marcador y administración desde la barra de demostración. Si el navegador restringe el almacenamiento de archivos locales, usa el servidor de la opción siguiente. No uses navegación privada si deseas conservar los datos.

**Opción recomendada para compartir datos entre pantallas y probar desconexión:** desde esta carpeta ejecuta:

```sh
python3 -m http.server 8000 --bind 127.0.0.1
```

Abre el servidor local en tu navegador. Las páginas son `index.html` (web), `marcador.html` (representación del cliente instalado) y `admin.html` (empresa y Maestro). Usa siempre el mismo navegador, perfil y origen. El archivo autónomo usa la misma clave de almacenamiento; servido en el mismo origen comparte su demostración con las páginas separadas.

No hay usuarios ni contraseñas reales: el acceso administrativo tiene un selector de rol y una confirmación de alcance. El PIN de demostración para todos los empleados es **123456**. La aplicación real no deberá usar estas credenciales ni los controles de acceso simulados.

## Qué funciona

- Web pública responsive: navegación, planes ilustrativos, preguntas, modal de descargas y páginas informativas, contacto guardado localmente sin envío de correos.
- Marcador: búsqueda y selección de empleados activos de San José, PIN enmascarado, teclado táctil/físico, validación y demora por intentos fallidos, entrada/salida y descansos, modo automático pendiente de conciliación, confirmación y limpieza de identidad.
- Eventos con UUID y secuencia local, persistencia tras recargar, cola demo, copia JSON, simulación offline y sincronización explícita idempotente dentro del navegador. No existe envío a un servidor. El modo offline se aplica desde Opciones → Simular marcador sin internet → Aplicar.
- En las páginas separadas servidas localmente, un service worker cachea los recursos para recargar sin red después de la primera carga completa. No garantiza persistencia frente a borrado del navegador ni equivale al almacenamiento nativo cifrado.
- Empresa: dashboard de datos recibidos; alta, edición, desactivación y búsqueda de personal; cronología y detalle de eventos; revisiones de incidencias con motivo conservando originales; turnos diurnos/nocturnos; sucursales; solicitudes de vacaciones y permisos; solicitudes y aprobación de horas extra; revocación demo de terminal; cambios de modo del marcador; bitácora.
- Preplanilla: lista verificable de pendientes y exportación sin cálculos inventados. No genera valores monetarios ni horas legales definitivas.
- Reportes por fechas, empleado y sucursal, exportación CSV compatible con Excel e impresión/guardar PDF mediante el navegador. XLSX nativo pendiente.
- Maestro: catálogo ficticio de empresas, cambios de plan/estado comercial, consultas locales del sitio, editor de título/descripción/color con borrador, previsualización y publicación únicamente en la demo local. El selector “Cambiar vista demo” no representa una elevación de permisos real.
- Accesibilidad: etiquetas, foco de teclado, diálogos con retención de foco y Escape, estados anunciados, contraste y texto ampliado, adaptación a 390, 768, 1024 y 1440 píxeles.

Los gráficos semanales son ilustraciones y están rotulados. No se infiere ausencia con datos incompletos. Las fechas y horas usan America/Costa_Rica.

## Recorrido sugerido

1. Explora la web oficial. Guarda una solicitud ficticia desde Contacto.
2. Abre el marcador demo. Activa modo sin conexión desde Opciones y aplica.
3. Selecciona Ana Jiménez, introduce 123456, elige Salida y registra. Recarga: el evento seguirá pendiente.
4. Desactiva el modo sin conexión y aplica. Vuelve a Opciones y pulsa Sincronizar demo. Repite para comprobar que no duplica eventos.
5. En Administración elige Administrador de empresa y acepta el aviso. Consulta Marcaciones, registra un empleado, revisa una incidencia con motivo y genera un reporte.
6. Usa Cambiar vista demo para Maestro. Consulta la solicitud comercial y edita el título en Editor web pública: guarda borrador, previsualiza y publica demo. La web pública reflejará el cambio.
7. Para volver al estado inicial usa Acerca de la demo → Restablecer datos demo. Solo elimina los datos locales de esta demostración.

## Alcance y pendientes

Es un **prototipo funcional de interfaz**, no un sistema de asistencia listo para operar. No implementa backend, seguridad multiempresa, sesiones/MFA reales, cifrado, firmas, credenciales de dispositivos, aprovisionamiento, fotografías, tokens TOTP, biometría, instaladores Windows/APK, pago, envío de emails ni cumplimiento laboral certificado. Nunca ingresar información real de empleados, PIN personales ni datos salariales. El almacenamiento local y la bitácora son modificables desde el navegador.

El marcador HTML es una representación para revisión de la futura app Windows/Android; la web del producto final no ofrecerá marcación de empleados. Los accesos al marcador desde la barra superior existen solo para recorrer este prototipo.

La marca vectorial incluida es una recreación simplificada inspirada en la imagen adjunta; no es el archivo maestro del logotipo original. Sustituir `assets/logo.svg` por el activo oficial al preparar el producto.

La arquitectura, el backend y las apps nativas se desarrollarán en una tarea posterior. Las instrucciones de implementación incluidas en la especificación adjunta se trataron como referencia de alcance; esta entrega se limita al UX HTML solicitado.

## Validación y regeneración

Las pruebas funcionales requieren Python, Playwright y Chromium; la aplicación HTML no los requiere para abrirse. Con el servidor iniciado:

```sh
python3 tests/ux_smoke.py
python3 tests/standalone_smoke.py
```

Las capturas se generan en `capturas/`. Para actualizar el HTML autónomo y el ZIP después de cambiar los archivos fuente:

```sh
python3 scripts/empaquetar.py
```

La variante autónoma se verificó por HTTP sin solicitudes de recursos externos. La apertura `file://` quedó sin ejecutar porque la política del Chromium administrado la bloquea; no se eludió esa política. Se recomienda el servidor local para reproducir exactamente la validación.

Se debe repetir la validación después de regenerar. No hay dependencias npm ni archivos de instalación.
