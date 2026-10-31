# MarkaRH — Especificación funcional, técnica y laboral para Codex

**Versión:** 1.0
**Fecha de referencia:** 30 de octubre de 2026
**Idioma:** Español de Costa Rica
**Producto:** MarkaRH
**Objetivo:** Sistema de asistencia laboral multiempresa, con marcadores instalados en Windows y Android que funcionan **sin internet**, y una plataforma web alojada en hosting con cPanel **exclusivamente para administración**.

> **Instrucción principal para Codex:** Este documento define el alcance del producto. Examina primero el repositorio existente de MarkaRH, identifica las tecnologías y el estado real del código, y preserva cualquier avance útil. Prepara arquitectura, backlog, base de datos, contratos API y fases de implementación antes de sustituir componentes. Construye componentes funcionales con pruebas; no declares listo aquello que todavía sea un prototipo o dependa de hardware sin validar.

---

## 1. Visión y principios innegociables

MarkaRH permite a una empresa instalar tantos puntos de marcación autorizados como contemple su licencia, en computadoras Windows o dispositivos Android. El empleado **no tiene cuenta web**. La web oficial solo es accesible a los administradores de empresas y al usuario Maestro, además de sus páginas públicas institucionales.

**Reglas obligatorias:**

1. La entrada, salida y demás marcaciones se ejecutan y quedan guardadas **localmente** incluso con cero conexión de internet. Una caída del sitio web, del hosting, del DNS o del servicio API nunca debe impedir una marcación ya configurada y autorizada.
2. La instalación Windows y la APK Android usan almacenamiento persistente local cifrado y pueden sincronizar cuando se restablezca internet. No borrar jamás eventos locales no confirmados.
3. La web **no posee pantalla, formulario, API de usuario ni botón que permita a empleados registrar una marcación**. La API de sincronización sí recibe marcaciones creadas previamente por instalaciones registradas y autenticadas; no es una API de marcación pública.
4. Un marcador queda activado con una **credencial de dispositivo** creada o autorizada por el Maestro (por empresa/sucursal/dispositivo). No es una cuenta personal de empleado ni permite acceder a información administrativa.
5. Un administrador de negocio utiliza **otra cuenta** para entrar a la web y gestionar personal, horarios, reglas y reportes. Puede poseer permisos de soporte/configuración local delimitados, sin habilitar el panel web como reloj marcador.
6. El Maestro tiene acceso global a empresas afiliadas, sucursales, licencias, dispositivos, suscripciones, soporte, ajustes globales, contenido y estética del sitio público, siempre con auditoría.
7. Todos los datos y cálculos se aíslan estrictamente por empresa (`tenant_id`); la sede o sucursal (`branch_id`) también forma parte de la autorización.
8. Los registros originales son inmutables: toda rectificación es un evento adicional con motivo, autor, fecha, valores anteriores y nuevos, y jamás una edición silenciosa.
9. El sistema debe indicar visiblemente si una marcación está **guardada localmente**, **pendiente de sincronizar**, **recibida por el servidor**, **observada** o **conciliada**.
10. La interfaz debe ser accesible, táctil, clara, veloz, adaptable al tamaño de pantalla y diseñada para marcaciones consecutivas por personas diferentes.

### Límites honestos de la solución

- Un equipo instalado en la oficina no prueba de manera absoluta la presencia física: deben controlarse accesos remotos, portabilidad del equipo, falsificación de capturas y suplantación entre compañeros. La evidencia será más fuerte combinando equipo registrado y las verificaciones configuradas.
- La hora del servidor es autoridad **cuando hay conexión**. Sin internet se usa hora local con control de manipulación, última calibración, monotonicidad y auditoría. No se puede prometer que el servidor certificó un instante que ocurrió mientras estaba desconectado.
- Varias instalaciones completamente desconectadas **no conocen al instante** las marcaciones registradas en otras. El servidor consolida después y marca conflictos para revisión.
- La identificación por huella de *varios empleados en el mismo punto de marcación*, sin elegir nombre ni ingresar PIN, requiere **lector externo compatible y SDK de identificación 1:N**. La biometría normal del sistema Android (BiometricPrompt) solo confirma al usuario autorizado del dispositivo; no expone una identidad arbitraria entre distintos empleados.
- La primera activación y los cambios administrativos podrán realizarse conectados; opcionalmente se admitirá una **activación offline mediante archivo firmado** y exportación/importación segura si la empresa no dispone de conexión.

---

## 2. Roles, identidad y aislamiento de permisos

| Rol | Dónde accede | Facultades |
| --- | --- | --- |
| **Maestro MarkaRH** | Web administrativa global | Alta/suspensión de empresas, planes, licencias, dispositivos, configuración global, auditoría, soporte autorizado, diseño/contenido del sitio y branding. |
| **Administrador de empresa** | Solo web administrativa; servicio local técnico opcional | Alta/baja de empleados, sucursales según permisos, jornadas, turnos, reglas, fotografías/huellas si se habilitan, vacaciones, incidencias, reportes, ajustes y aprobación de correcciones. |
| **Supervisor / RR. HH.** (delegado) | Web empresarial | Permisos granulares para empleados, sucursales, reportes, turnos, incidentes, aprobaciones. No ve datos de otras empresas. |
| **Credencial del marcador** | Solo app instalada Windows/Android | Autoriza una instalación física como terminal de marcación. Mantiene sesión de quiosco, cachea la plantilla de empleados autorizados y sincroniza. Sin permisos administrativos. |
| **Empleado** | Solo panel de marcación instalado | Escoge su nombre y utiliza su PIN, o bien lector de huella autorizado en modo de identificación automática. **Nunca inicia sesión web.** |

**Privilegios:** RBAC y permisos por módulo y sucursal; comprobación **en el backend** en todas las solicitudes; MFA para Maestro y recomendado para administradores; límites de sesión para admins; autorización separada de dispositivos; registro de acciones sensibles. El Maestro puede crear credenciales pero la solución debe evitar almacenar contraseñas en texto claro o exponerlas después del alta.

---

## 3. Alta de empresas, instalaciones y modo quiosco

### 3.1 Ciclo de activación

1. Maestro crea empresa, plan, sucursales y administrador inicial.
2. Maestro autoriza un cupo de dispositivos, y emite una **credencial de instalación de uso único o aprovisionamiento** vinculada con empresa/sucursal.
3. Se instala el cliente MarkaRH para Windows o la APK Android.
4. Un técnico autorizado activa la instalación una vez y descarga configuración, catálogo de personal de la sucursal y claves de verificación necesarias para trabajar offline.
5. La instalación genera y almacena de forma segura una **identidad única de dispositivo y par criptográfico**. Se autoriza en servidor y se vincula con la sucursal. Reinstalar/cambiar equipo exige nuevo aprovisionamiento o recuperación supervisada, no reutilización silenciosa.
6. El marcador entra en **Modo Quiosco** y permanece mostrando la pantalla de marcación durante el horario de operación. Después de reinicio puede configurarse inicio automático, pero siempre dentro de las capacidades de Windows/Android.
7. El administrador puede activar/desactivar el equipo desde la web; el cambio llega cuando sincronice. Definir política offline de revocación y caducidad de autorización, sin borrar eventos legítimos.

### 3.2 Persistencia e inicio automático

- **Windows:** inicio automático al entrar en la cuenta Windows destinada al quiosco; opción de arranque a pantalla completa; control de cierre con credencial técnica; recuperación tras cierre inesperado; prevenir suspensión del equipo solo con permiso del sistema. Un proceso de servicio o tarea de arranque, si se necesita, debe probarse en Windows real. No afirmar que una app común puede impedir Ctrl+Alt+Supr, apagado o cierre forzado.
- **Android:** app dedicada con orientación vertical/horizontal, pantalla siempre activa configurable y modo quiosco administrado **solo en equipos aprovisionados para ello**, usando las APIs correspondientes. Las restricciones de Android sobre segundo plano deben respetarse; la sincronización continua se garantiza mientras la aplicación permanece al frente y la red está disponible, no cuando el SO la suspenda.
- **Ambos:** indicador online/offline, última sincronización, reloj, cantidad de eventos pendientes; menú técnico oculto/protegido; actualización segura y firma de versiones.
- Varios equipos de la **misma empresa y sucursal** pueden funcionar simultáneamente. El registro individual de cada terminal permite saber dónde se capturó cada marcación.

### 3.3 Identidad del dispositivo y licencias offline

Autorización firmada, anclada al identificador de instalación y con un tiempo de vigencia/revalidación claramente definido. Definir para contratos comerciales un período de gracia offline razonable; **el vencimiento comercial no debe destruir ni ocultar marcaciones existentes**. Permitir recuperar/exportar de forma segura los eventos pendientes ante falla del equipo o vencimiento de licencia. Evitar que una simple modificación del reloj evada la vigencia.

---

## 4. Diseño del panel instalado — usar la imagen adjunta como referencia

La fotografía proporcionada por el propietario muestra la dirección visual: azul marino, blanco, acentos azules y verdes, jerarquía clara y pantalla en orientación horizontal. **Usar la imagen de referencia como guía estética, no para copiar fotografías reales ni datos personales ficticios como parte del producto.**

### 4.1 Pantalla principal

- Logo **MarkaRH** y nombre de empresa/sucursal; estado 'Dispositivo autorizado'.
- En centro, área de marcado muy grande; nombre/título 'Panel de Marcación'; selección/búsqueda rápida de empleados activos según la sucursal (nombre y opcionalmente número interno, sin mostrar cédula completa).
- Teclado táctil numérico grande; PIN configurable mínimo 6 dígitos y recomendable 6–8; entrada enmascarada; no mostrar ni almacenar PIN en logs.
- Botones de entrada y salida cuando se usa modo explícito; acciones de descanso si está configurado; en modo automático un botón central grande **MARCAR AHORA**.
- Cámara/ventana de validación fotográfica **solamente cuando se habilite**; no exigir 'tomar fotografía manual' si la validación está configurada como automática e inmediata.
- Reloj grande, fecha, indicador de internet, última sincronización y fila de 'Últimas marcaciones', protegida contra exposición excesiva. No afirmar 'Hora del servidor' cuando esté offline.
- En Android pequeño: reorganizar a una columna, sin perder el panel central y el teclado grande.
- Aviso corto: 'Marcación guardada', hora, tipo, estado de sincronización. Reiniciar formulario tras un breve intervalo; nunca dejar foto/PIN del empleado anterior visible.
- Panel de accesibilidad: contraste, texto escalable, teclado físico/mouse/táctil, modo alto contraste opcional.

### 4.2 Dos modos de marcación configurables **por empresa o sucursal**

**A. Tipo explícito:** empleado selecciona entrada, salida, inicio de descanso o fin de descanso (solo opciones habilitadas). Solicitar confirmación para eventos contradictorios. Controlar horarios nocturnos, múltiples intervalos y doble jornada.

**B. Tipo automático:** empleado selecciona nombre/PIN y pulsa **MARCAR AHORA**; el dispositivo solo registra el hecho. El **motor de conciliación** infiere intervalos de presencia/trabajo a partir del orden de eventos, la planificación de jornada, la configuración de descansos y las reglas de emparejamiento. No decidir arbitrariamente que todas las marcaciones impares son entradas y pares son salidas cuando hay irregularidades. Eventos incompletos, duplicados, turnos cruzando medianoche y secuencias ambiguas se señalan para revisión administrativa.

Los hechos deben almacenarse **independientemente de su interpretación**. Si cambia una regla, volver a calcular el resultado sin alterar las evidencias originales, dejando versión de reglas y trazabilidad.

### 4.3 Flujo de marcación con PIN

1. Empleado busca/selecciona su nombre; teclado táctil pide PIN.
2. PIN se valida **localmente** contra un verificador seguro provisionado, con limitación de intentos y demora progresiva. No impedir que otras personas marquen por un intento fallido de un empleado, pero sí contener ataques masivos.
3. El empleado elige tipo de evento si el modo es explícito; si es automático, solo pulsa 'Marcar ahora'.
4. Si hay verificación adicional configurada, se ejecuta **en ese mismo flujo** antes de confirmar: cámara toma foto o solicita token administrativo, según política.
5. El cliente guarda de forma transaccional evento, hora local, reloj monotónico, dispositivo, método, evidencia y estado de sincronización en base local; luego confirma al trabajador.
6. Se intenta enviar al servidor si existe conexión, sin bloquear confirmación local; se sincroniza después cuando vuelva la red.
7. Regresa automáticamente a la pantalla inicial limpia.

### 4.4 Fotografía instantánea opcional

- Modo OFF / foto obligatoria en cada marcación / foto en eventos configurados.
- Cámara integrada de tableta o webcam en Windows. Solicitar permiso, previsualizar solo lo necesario y capturar sin pasos adicionales salvo fallas. Guardar evidencia cifrada localmente y transmitir posteriormente; compresión, miniaturas, límites de peso y política de retención.
- Definir política si falla la cámara: bloquear o generar **incidencia provisional** en vez de simular fotografía; administrador puede revisar.
- La foto es **evidencia**, no comparación facial automática por defecto. Si se implementa reconocimiento facial será una capacidad separada, legalmente evaluada, no incluida en MVP.

### 4.5 Token de administrador opcional

El empleado marca normalmente, y el flujo solicita token creado/autorizado por un admin cuando la empresa lo exige. El token ha de funcionar **también con el marcador offline**:

- Propuesta principal: códigos tipo **TOTP** con secreto provisionado al dispositivo autorizado y al mecanismo de generación del administrador, vigencia breve, tolerancia controlada y protección ante reuso/ataques. El servidor reconcilia reusos detectados entre equipos después de sincronizar. Si el reloj local es inestable, no declarar válida una verificación temporal sin revisión.
- Opción de mayor garantía: desafío-respuesta con tokens **firmados asimétricamente** por el backend o la herramienta de administrador; el dispositivo valida firma offline con clave pública previamente instalada. Si el código se genera después de cortar internet, se requiere que el administrador disponga del mecanismo autorizado offline.
- Configurar si se exige en todas las marcaciones, determinados empleados, determinados turnos o excepciones.
- No guardar secreto de TOTP sin protección, no utilizar tokens estáticos reutilizables y limitar intentos.
- Un código corto y compartido entre varios trabajadores no prueba identidad por sí solo; combina PIN/equipo/foto según política.

### 4.6 Lector de huella opcional

- Activado desde ajustes de empresa/sucursal/dispositivo **solo si existe lector externo homologado y probado** con SDK y soporte en la plataforma.
- Modo de identificación **1:N**: al poner el dedo, el lector reconoce el identificador del empleado y dispara el flujo de marcación sin seleccionar nombre ni introducir PIN. En modo explícito puede mostrarse una confirmación o selección de acción, según ajuste.
- Crear una **interfaz de adaptadores** de hardware (`FingerprintProvider`) para el SDK particular: enrolamiento autorizado, identificación, fallo, reintento, eliminación, dispositivo no conectado y errores.
- **Windows:** implementar puente nativo/FFI o componente del SDK del fabricante. **Android:** requerirá soporte específico del fabricante (USB/OTG, Bluetooth si aplica, drivers/SDK). Evitar afirmar compatibilidad universal.
- Plantillas biométricas **preferentemente quedan dentro del lector** si el hardware lo admite, o cifradas y minimizadas conforme evaluación jurídica; no sincronizar huellas crudas con la web por defecto. Ofrecer baja y borrado seguro.
- Si lector falla, opción de fallback PIN+verificación siempre que la política empresarial lo permita.

### 4.7 Configuración de seguridad combinable

Permitir seleccionar por empresa, sucursal o dispositivo: PIN solo; PIN+foto; PIN+token; PIN+foto+token; huella identificadora (con foto opcional); fallback controlado. Mostrar la política efectiva sin filtrar secretos. Toda modificación queda auditada y se sincroniza con versionado.

---

## 5. Funcionamiento offline, sincronización y conciliación

### 5.1 Almacenamiento local

- Motor local SQLite con cifrado real soportado en Windows y Android (por ejemplo SQLCipher tras verificar paquetes/licencias/soporte), claves protegidas según capacidades de cada plataforma, migraciones y copia de recuperación cifrada.
- Cachear solo empleados de las sucursales autorizadas, estado laboral, verificador PIN, configuración, turnos, políticas, claves públicas de validación y catálogos estrictamente necesarios.
- Tablas o colecciones locales: `device_profile`, `employee_cache`, `schedule_cache`, `policy_cache`, `attendance_events`, `evidence_queue`, `sync_outbox`, `sync_cursors`, `time_health`, `local_audit`.
- Cada marcación es un hecho **append-only** con UUID v7/ULID globalmente único, secuencia por dispositivo, hash de integridad/enlace cuando corresponda, fecha local, offset, evidencia y estados.

### 5.2 Sincronización

- Client inicia conexión saliente **HTTPS/TLS** al API; el hosting no necesita conexiones entrantes hacia los equipos. La web recibe datos pero no es un reloj marcador.
- Cuando la app está abierta y hay conexión: sincronización periódica configurable (por ejemplo 30–60 segundos; intervalo exacto según pruebas de carga/hosting) y después de cada nuevo evento en cola. Si falla, reintento exponencial con variación aleatoria.
- **Push:** eventos nuevos de marcación y evidencias cifradas/subidas por lotes con hash, firmas/identidad de instalación; reintentos idempotentes.
- **Pull:** empleados, sucursales autorizadas, turnos, políticas, altas/bajas, rotación de credenciales, autorizaciones, cambios de versión y avisos administrativos.
- Respuesta por evento con `accepted`, `duplicate`, `pending_review` o `rejected_with_reason`; hacer ack local **solo cuando la aceptación del servidor esté confirmada y persistida**.
- Sincronizar con cursores/versiones y lotes paginados; soportar subida de fotos en varios pasos si el hosting impone límites de subida.
- No utilizar WebSocket ni procesos residentes como dependencia obligatoria en cPanel compartido; preferir sincronización iniciada por cliente y tareas programadas vía cron.
- Datos empresariales solo se visualizan centralmente cuando ya fueron sincronizados; indicar cobertura y `última recepción por dispositivo`.
- Nunca reemplazar silenciosamente un evento previo por `last write wins`.

### 5.3 Colisiones y eventos en múltiples terminales

Ejemplo: Ana marca en Windows a las 07:59 y vuelve a marcar en la tableta a las 08:01, con ambos equipos sin conexión. Al sincronizar, **conservar los dos eventos**, marcar posible duplicado y aplicar política de conciliación con revisión cuando proceda. No descartar el primero por orden de llegada al servidor. La secuencia de recepción no necesariamente es el orden real de las marcaciones.

Mantener hora declarada por dispositivo, última verificación horaria, hora de recepción, indicador de confianza horaria, ID del equipo, versión de política y resolución del conflicto. No interpretar secuencias ambiguas como jornadas definitivas.

### 5.4 Reloj y manipulación de hora

- Mientras haya red: comparación con hora confiable del backend usando HTTPS y desviación estimada; sello de recepción del servidor.
- Sin conexión: fecha/hora del equipo + reloj monotónico para detectar saltos de hora y reinicios; preservación de la última referencia temporal; bitácora de desvíos y evento con `time_confidence`.
- Si se detecta cambio relevante de reloj, **guardar la marcación**, señalar la hora como cuestionable y pedir conciliación; no fabricar una supuesta hora de servidor.
- Costa Rica por defecto `America/Costa_Rica`, pero guardar timestamps UTC y zona horaria por empresa/sucursal para escalar a otros países.

### 5.5 Copias, pérdida de datos y operación sin red prolongada

- Backup local cifrado con control de acceso; consola técnica para exportar paquete firmado/cifrado ante avería, y procedimiento autorizado para importar sin duplicar eventos.
- Cuotas de almacenamiento y alerta por espacio insuficiente; fotografías optimizadas; rutina de limpieza **solo de eventos ya confirmados** y respetando retención/legal hold.
- Definir recuperación ante corrupción de SQLite, apagado durante escritura, rotación de claves y recuperación de dispositivo; pruebas con red intermitente y reinicios.

---

## 6. Web oficial y panel de empresas (no marcador)

### 6.1 Web pública MarkaRH

Nombre correcto de marca: **MarkaRH**. Sitio público con páginas: Inicio, Funcionalidades, Cómo funciona, Soluciones por sector, Planes (si se publican), Preguntas frecuentes, Contacto, Descargas de Windows y Android, Privacidad, Términos y Acceso Empresas. Estética corporativa profesional, moderna, limpia, adaptable y basada en la identidad del panel de referencia.

### 6.2 Acceso empresarial

Portal web autenticado **solo para administración**, sin función de marcar. Dashboard de empresa con presentes/ausentes estimados a partir de **datos recibidos** (mostrar última sincronización de cada terminal), retardos, faltas, horas pendientes, dispositivos offline, discrepancias, solicitudes y reportes.

Módulos web de empresa:

1. **Empresa / sucursales:** datos fiscales básicos, sedes, departamentos, zonas horarias, calendarios y festivos, administradores y permisos.
2. **Empleados:** alta/baja, número interno, nombre, identificador protegido, fecha ingreso/egreso, salario y modalidad si se habilitan cálculos, puesto, sucursal, contrato, horario, estado, verificador PIN, fotografía opcional, preferencias de validación. No exponer PIN en claro.
3. **Turnos y reglas:** diurno, nocturno, mixto, jornadas especiales legalmente autorizadas, rotativos, partidos, turnos cruzando medianoche, días libres, descansos remunerados/no remunerados y tolerancia.
4. **Marcaciones:** cronología por empleado y por terminal, origen, evidencia disponible, tipo, estado, confianza horaria, anotaciones y excepciones.
5. **Conciliación:** marcaciones faltantes/duplicadas, tardías, cambios de turno, conflictos entre terminales; propuesta de ajuste y aprobación con auditoría.
6. **Vacaciones, ausencias, permisos e incapacidades:** saldos, solicitudes, calendarios, respaldo documental restringido, aprobaciones y reportes.
7. **Horas extra:** detectadas, solicitadas, autorizadas, trabajadas, rechazadas, pagaderas según revisión; motivo y persona aprobadora.
8. **Preplanilla:** horas ordinarias y extras, feriados/descansos y estimación de valores según contrato; exportación a sistemas de planilla. No presentar automáticamente como nómina legal certificada.
9. **Reportes:** fecha libre, rango, mes, empleado, todos, sucursal, departamento, tipo de incidente, terminal, jornada, estado sync; CSV/XLSX y PDF; filtros persistentes y exportación auditable.
10. **Configuraciones de marcadores:** listado de dispositivos, estado, asignación, política de seguridad, modo explícito/automático, cámara, tokens, lector, última conexión, versión, revocación.
11. **Notificaciones:** dispositivo sin sincronizar, conflicto de marcación, incumplimiento de topes, ausencias no justificadas, pendientes de aprobación, vencimientos.
12. **Bitácora y privacidad:** trazabilidad de accesos, ediciones, descargas, evidencias, consentimiento/avisos, retenciones, exportación y eliminación legalmente procedente.

### 6.3 Usuario Maestro

- Gestión total de cuentas cliente, sucursales y usuarios administradores; roles; validación de empresas.
- Planes por empresa: empleados activos, dispositivos autorizados, sucursales, módulos, almacenamiento, reportes avanzados, licencia activa/pausada/en gracia, fecha de renovación.
- Módulo de facturación/suscripciones administrativa con integración de pago **solo si se desarrolla después**; en MVP estados y trazabilidad manual.
- Descargas de instaladores firmados, versiones, notas, actualización obligatoria/opcional y canales estable/pruebas.
- Observabilidad: salud del hosting, tamaño de base, cola de errores, dispositivos que no sincronizan, estadísticas de servicio **sin exhibir datos sensibles de empleados salvo acceso de soporte con justificación y registro**.
- **Editor de la web pública:** logo, colores, tipografía, imágenes, textos de secciones, menús, SEO, páginas, testimonios, banners, enlaces, footer; vista previa y publicar/despublicar. El editor no debe permitir scripts arbitrarios ni comprometer seguridad.
- Soporte delegado, acceso temporal con consentimiento y registro de acciones. MFA obligatorio al Maestro.

---

## 7. Motor laboral Costa Rica: requisitos legales y funcionales

**Alcance:** reglas base para el sector privado costarricense; deben ser **versionadas, parametrizables y revisadas por un especialista en derecho laboral** antes de ofrecer cálculos monetarios oficiales. No asumir que todas las industrias, convenios, categorías laborales o situaciones especiales comparten reglas idénticas. Registrar fundamento y fecha de vigencia de cada regla.

### 7.1 Jornada ordinaria y períodos

Según el Código de Trabajo, arts. 135, 136 y 138, y la orientación del MTSS:

| Clasificación | Franja | Regla ordinaria general |
| --- | --- | --- |
| Diurna | 05:00 a 19:00 | Hasta 8 horas/día y 48 horas/semana. |
| Nocturna | 19:00 a 05:00 | Hasta 6 horas/día y 36 horas/semana. |
| Mixta | Combina períodos diurnos y nocturnos | Hasta 7 horas/día y 42 horas/semana; si se laboran 3,5 horas o más en franja nocturna, se considera nocturna. |
| Acumulativa, cuando proceda | Diurna o mixta | En labores no insalubres ni peligrosas, la diurna puede pactarse hasta 10 horas y la mixta hasta 8 horas, sin superar 48 semanales. **No aplicar sin configurar la excepción aplicable.** |

Para cada empleado almacenar clasificación, horario pactado, modalidad, límites y versión de regla. Comparar jornada pactada con límites legales. Incluir turnos que cruzan medianoche y acumulación por semana laboral configurada.

### 7.2 Horas extraordinarias

- Art. 139: trabajo efectivo fuera de jornada ordinaria **o de la jornada inferior pactada**, en general remunerado con **50% adicional** (1,5 veces tarifa ordinaria), sujeto a excepciones legales que requieren revisión.
- Art. 140: jornada ordinaria + extraordinaria no puede exceder **12 horas por día**, salvo excepciones muy específicas de emergencia. Generar alertas, **no eliminar ni negar registros** si ocurre una excedencia.
- Calcular separadamente `horas_extra_detectadas`, `solicitadas`, `autorizadas`, `efectivamente_trabajadas` y `propuesta_de_pago`. Una aprobación empresarial no borra obligaciones legales de remuneración por trabajo efectivamente realizado.
- Registrar las horas extra pagadas separadas de las ordinarias para exportar a planilla (art. 144).
- Las categorías excepcionales del art. 143 requieren configuración explícita, respaldo y revisión jurídica, jamás inferir automáticamente por el nombre del puesto.

### 7.3 Descansos

- Art. 137: jornada continua debe contemplar **media hora mínima** de descanso computada como tiempo efectivo; si durante un descanso el empleado no puede abandonar el lugar de trabajo, puede computar como tiempo efectivo. Diferenciar claramente descanso pagado/computado y período discontinuo no remunerado según condiciones efectivas.
- Art. 152/MTSS: descanso semanal de 24 horas después de la semana o seis días continuos de trabajo; trabajo durante descanso semanal generalmente con pago doble según circunstancias aplicables. Generar alertas y cálculos revisables.
- No descontar automáticamente 30 o 60 minutos a toda jornada: el admin configura el tipo de descanso y el sistema aplica la regla correspondiente, con advertencias por posibles configuraciones incompatibles con la ley.

### 7.4 Feriados

- Calendario oficial **versionado por año**, con feriados de pago obligatorio/no obligatorio, cambios normativos y fecha real de disfrute (art. 148); permitir calendarios de empresa solo para beneficios superiores o reglas válidas.
- Modalidad de pago **semanal no comercial** vs **mensual, quincenal o semanal comercial** impacta la liquidación. El MTSS distingue los pagos de feriados en esas modalidades y, para casos de trabajo en feriado remunerado con doble, horas extra en feriado pueden implicar tasa triple. No aplicar una regla genérica a todos los feriados.
- Guardar separadamente `feriado_trabajado`, `feriado_no_trabajado`, `tipo_pago`, `horas_extra_feriado` y `resultado_revisable`.

### 7.5 Vacaciones

- Art. 153: mínimo **dos semanas remuneradas por cada cincuenta semanas de labores continuas** con el mismo patrono. Al terminar antes del período, como mínimo **un día por mes trabajado**, según norma y supuestos.
- Módulo con fecha de ingreso, periodos de devengo, saldo inicial, acumulado, solicitado, aprobado, disfrutado, adelantos y saldo; gestionar esquemas más favorables y casos especiales sin convertir por defecto dos semanas en un número universal de días para todos los patrones de trabajo.

### 7.6 Ausencias, licencias e incapacidades

Clasificar ausencia injustificada, tardía, permiso con/sin goce, incapacidad, riesgos del trabajo, licencias por maternidad/paternidad y otras protecciones aplicables, descanso, vacaciones y suspensión; adjuntos opcionales restringidos. **No tomar decisiones disciplinarias ni aplicar descuentos automáticos definitivos**: mostrar incidencias y propuestas que la empresa revisa conforme a la ley y reglamentos correspondientes.

### 7.7 Aguinaldo y preplanilla (fase posterior)

- El MTSS indica que el aguinaldo sector privado se calcula sumando remuneraciones salariales ordinarias y extraordinarias devengadas **del 1 de diciembre al 30 de noviembre** y dividiendo entre 12. Esto requiere historial salarial real, no solo horas del marcador. Si faltan componentes de remuneración, mostrar 'cálculo incompleto'.
- Agregar proyección informativa, calendario de corte, detalle de salarios fuente y exportaciones sin mezclarlo con la marcación.
- Cálculos de liquidación/preaviso/cesantía son módulos **separados y posteriores**, sujetos a revisión jurídica; no estimarlos solo a partir de horas marcadas.

### 7.8 Auditoría, confidencialidad y privacidad

- Ley 8968 y su reglamento: informar finalidades, habilitar mecanismos legales de consentimiento/avisos según base jurídica y contexto laboral, controlar accesos, proteger datos de empleados, delimitar transferencias y conservar evidencia de cumplimiento. Consultar a asesor legal antes de incorporar biometría o reconocimiento facial.
- Fotografías del trabajador e identificadores biométricos requieren privacidad reforzada, retención definida, acceso limitado y procedimiento de eliminación cuando sea aplicable. Evitar almacenar huellas crudas en la nube.
- No publicar listas amplias de empleados en un dispositivo de libre acceso más allá de lo indispensable; protección de pantalla, anti-captura cuando sea viable y limitación de historiales visibles.
- Mantener mecanismos de auditoría para inspección laboral, con exportación de documentos que identifiquen cambios de fechas y reglas.

### 7.9 Pruebas laborales obligatorias

Casos de regresión: jornada 08:00–17:00 con descanso continuo; turno 18:00–02:00; mezcla con 3,5 horas nocturnas; acumulativa legalmente configurada; jornada menor contractualmente pactada; horas extraordinarias por límites diarios/semanales; doce horas; sábado/domingo/descanso; feriado obligatorio y no obligatorio bajo las distintas modalidades de pago; turno que cruza fin de mes/semana; cambio de horario; ausencia justificada; vacaciones iniciadas a mitad de ciclo; recálculo con ajuste aprobado; salario variable y aguinaldo incompleto.

---

## 8. Modelo de datos sugerido

### Backend central (MySQL/MariaDB según cPanel)

`tenants`, `branches`, `departments`, `roles`, `permissions`, `user_accounts`, `user_roles`, `employee_profiles`, `employee_employment_terms`, `employee_assignment_history`, `devices`, `device_enrollments`, `device_licenses`, `plans`, `subscriptions`, `shifts`, `shift_assignments`, `break_rules`, `labor_rule_versions`, `holiday_calendar_entries`, `punch_policies`, `attendance_raw_events`, `attendance_evidence`, `attendance_interpretations`, `work_intervals`, `daily_attendance_summaries`, `weekly_attendance_summaries`, `attendance_conflicts`, `attendance_adjustments`, `overtime_requests`, `overtime_approvals`, `leave_types`, `leave_requests`, `vacation_ledgers`, `payroll_periods`, `payroll_estimates`, `salary_history`, `notifications`, `sync_batches`, `sync_cursors`, `security_audit_logs`, `public_site_pages`, `public_site_assets`, `public_site_settings`.

**Campos mínimos de un evento crudo**:

```text
id (UUID/ULID inmutable)
tenant_id
branch_id
employee_id
device_id
device_sequence
action_requested (IN / OUT / BREAK_START / BREAK_END / AUTO_PUNCH)
auth_method (PIN / FINGERPRINT)
verification_methods (PHOTO, ADMIN_TOKEN, NONE)
policy_version
local_occurred_at
local_timezone / offset
last_time_sync_at
estimated_clock_skew_ms
time_confidence
local_monotonic_reference
received_at_server (nullable hasta sincronización)
security_verification_result
evidence_id(s), checksum(s)
event_integrity_hash
raw_sync_status
created_at / archived_at (nunca destruir la evidencia de origen por re-cálculo)
```

**Índices/constraints:** `UNIQUE(tenant_id, device_id, device_sequence)`, `UNIQUE(id)`, índices por `(tenant_id, employee_id, local_occurred_at)`, `(tenant_id, branch_id, received_at_server)`, claves foráneas y aislamiento robusto. Jamás aceptar `tenant_id` arbitrario del cliente sin contrastarlo con identidad de dispositivo.

**Conciliación separada de evento crudo:** interpretación versionada con origen (regla/usuario), lista de UUID originales, advertencias, aprobación y sello de tiempo; al corregir se crea revisión y se retiene el historial anterior.

---

## 9. Contrato API inicial de sincronización

Crear API bajo `/api/v1/device/` con autenticación de dispositivo distinta del login web. Solo **terminales previamente autorizadas** pueden sincronizar eventos creados localmente.

Ejemplos de rutas ilustrativas:

```text
POST /api/v1/device/enroll         # Activación / intercambio inicial
POST /api/v1/device/auth/rotate    # Rotación segura de credenciales
GET  /api/v1/device/config        # Config y versiones autorizadas
GET  /api/v1/device/snapshot      # Empleados, horarios y políticas por sede
GET  /api/v1/device/changes       # Cambios incrementales por cursor
POST /api/v1/device/events/batch  # Ingesta idempotente de eventos offline
POST /api/v1/device/evidence      # Evidencias, con límites y verificaciones
POST /api/v1/device/heartbeat     # Versión, salud y sincronización
POST /api/v1/device/time-check    # Calibración del reloj con servidor
```

`POST events/batch` **no es un endpoint para empleados ni una marcación online**. Requiere identidad de terminal y firma/autorización, acepta solo eventos de esa instalación/sucursal y valida UUID, integridad, permisos y políticas. No debe aceptar datos falsificados por usuarios web o anónimos. Limitar tamaños, rate limits, paginar cambios, implementar respuestas por evento y `idempotency_key`.

Endpoints web admin (`/api/v1/admin/`) separados, con sesiones/MFA/roles; ninguno crea marcaciones originales 'como empleado'. Se admiten **solicitudes de rectificación** auditadas, no inserción suplantada en historial crudo.

Ejemplo conceptual:

```json
{
  "batch_id": "UUID",
  "device_id": "ID_AUTORIZADO",
  "events": [
    {
      "event_id": "UUID",
      "sequence": 132,
      "employee_id": "EMPLEADO_ABC",
      "action_requested": "AUTO_PUNCH",
      "local_occurred_at": "2026-10-30T07:58:12-06:00",
      "time_confidence": "LOCAL_OFFLINE",
      "policy_version": 7,
      "verification_methods": ["PHOTO"],
      "evidence_checksum": "SHA256"
    }
  ]
}
```

No poner claves API ni PIN dentro del JSON de evento; manejar firmas y tokens de autenticación según protocolo. Las evidencias de fotografía se transfieren separadamente o de modo controlado, por límites de tamaño.

---

## 10. Tecnología y despliegue en cPanel

### 10.1 Stack recomendado

- **Apps nativas:** Flutter/Dart para **Windows y Android** con una sola base de código; adaptadores por plataforma para cámara, impresión opcional, inicio automático, almacenamiento seguro y lector de huella.
- **Web / API:** PHP **8.3 compatible con hosting**, framework Laravel en versión estable soportada y verificada en el servidor real (Laravel 13 requiere PHP 8.3 o mayor), frontend administrativo responsive basado en Blade/Livewire u otra opción mantenible.
- **Base de datos central:** MySQL/MariaDB soportada por el cPanel contratado, con migraciones y backups probados. Probar compatibilidad específica del framework, drivers, extensiones PHP, límites de disco y conexiones.
- **Base de datos local:** SQLite con cifrado, migraciones y outbox transaccional.
- **Comunicación:** REST/JSON HTTPS saliente, jobs cron donde el cPanel lo permita, sin dependencia de WebSockets ni workers residentes.
- **Almacenamiento:** fotos/evidencias en área privada fuera del `public_html` cuando sea posible; descargas con autorizaciones firmadas/temporales, validación MIME y de malware según infraestructura; límite de almacenamiento y retención.
- **Instaladores:** Windows `setup.exe` o MSI firmado; Android APK firmado y distribuido de forma controlada desde la web oficial; actualizaciones autenticadas con manifiesto firmado, rollback seguro y compatibilidad con versiones API.

### 10.2 Comprobaciones previas del hosting

Codex **no debe suponer** que todo cPanel admite una app Laravel completa sin preparación. Verificar:

- Versión efectiva de PHP CLI y web; extensiones (`pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `intl`, `zip` y otras requeridas).
- MySQL/MariaDB versión, límites de conexiones, espacio, backups, cron jobs, cron mínimo admitido, acceso SSH/Composer, posibilidades de instalar dependencias localmente y cargar `vendor/`.
- Configurar documento raíz en directorio `public/`, nunca exponer `.env`, archivos SQLite, respaldos ni carpetas con evidencias.
- SSL/TLS obligatorio, headers, CORS restrictivo, CSRF en web, rate limits, rotación de claves, logs sanitizados.
- Tamaño máximo de POST/subidas y tiempos de ejecución; sincronización en lotes pequeños para no sobrepasarlos.
- Si el hosting no cumple latencia, almacenamiento, tráfico o confiabilidad de los equipos, documentar ruta de migración a VPS sin cambiar las apps instaladas.

### 10.3 Estructura de monorepo propuesta

```text
markarh/
  apps/
    marker_flutter/
      lib/
        core/                 # config, seguridad, offline db, sincronización
        features/kiosk/       # selección, PIN, reloj, eventos
        features/verification/# foto, token, huella/adaptadores
        features/diagnostics/ # estado técnico
      android/
      windows/
      test/
      integration_test/
    web_laravel/
      app/Domain/             # tenants, attendance, labor, subscriptions
      app/Http/               # APIs device / admin y middleware
      database/migrations/
      resources/views/
      tests/
  packages/
    contracts/               # OpenAPI/JSON Schema/ejemplos versionados
    labor_rules/             # especificación y casos de prueba
  docs/
    ARCHITECTURE.md
    DATA_MODEL.md
    API_SYNC.md
    SECURITY_PRIVACY.md
    LABOR_CR.md
    DEPLOY_CPANEL.md
    DEVICE_PROVISIONING.md
    QA_ACCEPTANCE.md
  scripts/
  README.md
```

No migrar ni reestructurar un repositorio existente sin primero comprobar su contenido y preparar la transición en commits/PRs verificables.

---

## 11. Criterios de aceptación imprescindibles

1. **Offline real:** desactivar toda conexión en Windows/Android previamente aprovisionado y marcar entrada y salida. El usuario ve confirmación local inmediata. Las dos quedan almacenadas aun después de cerrar y reiniciar la app.
2. **Sincronización posterior:** reconectar. Los dos eventos llegan una sola vez al backend y son visibles para el administrador; tres reintentos no crean duplicados.
3. **Web sin marcar:** usuario no autenticado, empleado y administrador web no pueden registrar un evento de origen nuevo por formulario o endpoint administrativo; solo terminales autorizadas envían hechos de marcador.
4. **Múltiples equipos:** dos quioscos de una misma sucursal crean marcaciones offline para un mismo empleado; ambas se conservan, se concilian o generan incidencia, nunca se pisan.
5. **Cambio de PIN/empleado:** alta o cambio desde web llega al dispositivo al sincronizar; si el equipo está desconectado, refleja la última versión provisionada y muestra su estado.
6. **Fotografía:** cámara activada captura evidencia durante la marcación offline y la evidencia llega a la web después. Si la cámara falla, no inventar imagen.
7. **Token:** un token válido conforme al esquema escogido puede verificarse con el equipo offline; código inválido, reutilizado cuando se pueda detectar o expirado no se aprueba silenciosamente.
8. **Huella:** implementada solo con dispositivo y SDK confirmado; prueba con dos empleados mostrando identificación correcta 1:N y registro sin nombre/PIN; si no hay hardware de pruebas, mantener adaptador abstracto **marcado como no implementado**.
9. **Automático:** tres eventos con secuencia anómala no producen horas laboradas definitivas inventadas; quedan como incidente revisable. Pares correctos calculan intervalos.
10. **Jornada nocturna:** turno que comienza a las 20:00 y termina al día siguiente no se divide erróneamente en dos jornadas ajenas.
11. **Regla laboral:** jornadas diurna/mixta/nocturna y excepciones se calculan de acuerdo con perfiles versionados; resultados de feriados dependen de modalidad de pago; detectar, no ocultar, sobrepasos de 12 h.
12. **Auditoría:** un admin ajusta una salida olvidada; queda el historial original y la rectificación con motivo, usuario, fecha y versión de cálculo.
13. **Tenancy:** un admin de empresa A no puede leer, exportar ni deducir empleados o fotos de B por URL, API o cambios de parámetros.
14. **Seguridad:** PIN no visible en logs; MFA en Maestro; el token de dispositivo no es acceso web; prueba de acceso indebido, replay y revocación.
15. **Operación quiosco:** Windows arranca en modo marcado bajo usuario kiosk; Android en dispositivo dedicado conserva pantalla operativa conforme las políticas de batería/MDM probadas.
16. **CPanel real:** desplegar y ejecutar migraciones/backups API en una instalación de staging compatible con el hosting; comprobar que los endpoints funcionan sin procesos residentes especiales.
17. **Privacidad:** imágenes y datos personales no son públicos por URL; autorización de descarga y política de conservación; solo empleados autorizados aparecen en cada terminal.
18. **Recuperación:** caída del servidor durante subida, corte de energía y falta de espacio no causan desaparición silenciosa de eventos.
19. **Reportes:** filtros por rango, empleado, todos, sucursal y estado; CSV/XLSX/PDF totalizan de forma consistente e indican incidencias no resueltas.
20. **Maestro:** puede editar web pública con previsualización y publicar cambios sin modificar código ni intervenir datos laborales de negocios por defecto.

---

## 12. Roadmap de desarrollo sin atajos

### Fase 0 — Auditoría técnica y diseño

Auditar repositorio y hosting; confirmar disponibilidad de herramientas Windows/Android y los SDK necesarios; definir arquitectura, alcance MVP, amenazas, esquema de base de datos, API OpenAPI, prototipo de interfaces basado en imagen. Entregables: docs, decisiones técnicas (ADR), riesgos, cronograma técnico por hitos **sin inventar estimaciones cerradas**.

### Fase 1 — Backend multiempresa y portal base

Maestro, login web admin, RBAC, empresas, sucursales, empleados, turnos, planes, registro de terminales, base central, auditoría, despliegue staging cPanel.

### Fase 2 — Marcador Flutter offline

Windows y APK, enrolamiento de terminal, modo quiosco, PIN, modo explícito, escritura transaccional local, reloj, indicador sync, recuperación ante reinicio, sincronización idempotente, pantalla responsive referida.

### Fase 3 — Motor de conciliación y reportes

Modo automático, interpretación versionada, calendario laboral CR, incidentes, aprobación de cambios, jornadas, tardías/ausencias, reglas de extras, vacaciones básicas, reportes.

### Fase 4 — Seguridad avanzada opcional

Fotografía automática, token admin offline, políticas combinadas, almacenamiento cifrado de evidencias. Lector de huella **solo tras probar hardware y SDK**.

### Fase 5 — SaaS comercial y sitio completo

Dashboard Maestro ampliado, planes/cupos, CMS del sitio, instaladores firmados, actualización, notificaciones, suscripciones, preplanilla/aguinaldo con pruebas jurídicas.

**Definición de terminado por fase:** código compilable, migraciones, pruebas automatizadas clave, pruebas manuales en ambos sistemas, documentación de instalación/operación, seguridad básica, registro de decisiones pendientes y demostración de escenarios de aceptación.

---

## 13. Instrucción directa lista para Codex

> **Codex: desarrolla MarkaRH conforme a esta especificación, tomando la imagen adjunta del panel como guía de estilo.** Antes de modificar archivos inspecciona el repositorio, resume su estado, crea o actualiza `docs/ARCHITECTURE.md`, `docs/ROADMAP.md`, `docs/API_SYNC.md` y `docs/LABOR_CR.md`; define un plan por fases y empieza por la Fase 1 con una base de datos multiempresa, roles, API de enrolamiento y sincronización y panel administrativo. Continúa con un marcador Flutter Windows/Android **offline-first**. Prioriza un flujo realmente probado de marcación local -> cola persistente -> sincronización -> reporte y pruebas de caídas/red. **No** crees una página web para marcaciones de empleados. El login web es solo del Maestro y administradores de negocio; las terminales usan identidad y licencia de dispositivo, y los empleados solo nombre+PIN o huella externa 1:N opcional. Implementa foto y token como verificaciones configurables, con token verificable offline. No asumas soporte biométrico universal; deja un adaptador y una integración explícita con proveedor probado. Todas las reglas de Costa Rica deben ser versionadas, revisables y auditables; no efectuar cálculos legales definitivos sin validación. Mantén trazabilidad completa, pruebas de aislamiento entre empresas y documentación para subir Laravel a cPanel. No sobrescribas trabajo existente sin diagnóstico. Entrega cada fase con comandos de compilación, migración y prueba, y un registro real de funcionalidades terminadas o pendientes.

---

## 14. Fuentes normativas y técnicas para el equipo de desarrollo

Estas fuentes sustentan las reglas iniciales y deben revisarse para cambios posteriores a esta especificación:

1. [Código de Trabajo de Costa Rica — PGR/SINALEVI, Ley 2](https://pgrweb.go.cr/scij/Busqueda/Normativa/Normas/nrm_texto_completo.aspx?nValor1=1&nValor2=8045&nValor3=129508&param1=NRTC&strTipM=TC), en particular arts. 135–144, 148, 152, 153.
2. [MTSS — Temas laborales: jornada, descansos, horas extraordinarias y descanso semanal](https://www.mtss.go.cr/temas-laborales/).
3. [MTSS — 15 de septiembre 2026, feriados y modalidades de remuneración](https://www.mtss.go.cr/prensa/comunicados/2026/septiembre/cp_019_2026.html).
4. [PGR — Artículo 148 actualizado (reforma Ley 10050)](https://www.pgrweb.go.cr/DOCS/NORMAS/1/NOVIGEN/L/1940-1949/1940-1944/1943/1F6D/149416.HTML) — verificar texto vigente en SINALEVI antes de codificar calendarios históricos/futuros.
5. [PGR — Artículo 153, vacaciones](https://pgrweb.go.cr/scij/Busqueda/Normativa/Normas/nrm_articulo.aspx?nValor1=1&nValor2=8045&nValor3=109068&nValor5=47180&param1=NRA&strTipM=FA).
6. [MTSS — Aguinaldo sector privado](https://www.mtss.go.cr/temas-laborales/aguinaldo/aguinaldo.html) y [MTSS preguntas frecuentes](https://www.mtss.go.cr/temas-laborales/aguinaldo/preguntas_frecuentes.pdf).
7. [PRODHAB — Legislación sobre protección de datos personales](https://www.prodhab.go.cr/acercade/normativa/), Ley 8968 y Reglamento.
8. [PGR — Ley 8968, texto](https://www.pgrweb.go.cr/DOCS/NORMAS/1/VIGENTE/L/2010-2019/2010-2014/2011/1153F/DCEF7.HTML).
9. [Android Open Source — Arquitectura de biometría](https://source.android.com/docs/security/features/biometric).
10. [Android Developers — BiometricPrompt](https://developer.android.com/reference/android/hardware/biometrics/BiometricPrompt).
11. [Flutter — Compatibilidad escritorio](https://docs.flutter.dev/platform-integration/desktop).
12. [Laravel — Política de soporte/versiones](https://laravel.com/framework/docs/12.x/releases) y [Scheduler/cron](https://laravel.com/framework/docs/12.x/scheduling).

**Advertencia de alcance:** El software proporciona registro y cálculos de apoyo, no reemplaza asesoría profesional laboral ni una revisión individual de contratos, convenios o excepciones. El motor debe quedar preparado para que el catálogo normativo se mantenga actualizado.
