# Modelo inicial

- `tenants`: empresas, nombre y estado. Borrado lógico/retención se decidirá antes de bajas reales.
- `branches`: sucursales pertenecientes a una empresa, zona horaria.
- `users`: identidad administrativa; rol `master` con empresa nula o `admin` con empresa asignada. Nunca autentican empleados.
- `employees`: identificador interno único por empresa, nombre, puesto, sucursal y estado; sin PIN fijo ni salario en respuestas a terminal.
- `device_enrollments`: hash de credencial temporal de un solo uso, empresa/sucursal, vencimiento y consumo.
- `devices`: empresa/sucursal, hash de token opaco y estado autorizado/revocado.
- `attendance_events`: UUID, secuencia de dispositivo, empleado, acción, instante local con offset original y recepción de servidor. Únicos por UUID y por dispositivo/secuencia. Sin métodos web de edición/borrado.
- `audit_logs`: empresa opcional, actor, acción, identificador de entidad y fecha; no almacena contraseñas ni tokens.

La primera entrega exige validaciones relacionales y pruebas de acceso cruzado. Los hechos no se transforman en horas de trabajo. Tablas de conciliación, reglas, solicitudes y remuneraciones corresponden a fases siguientes.
