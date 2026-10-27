# Sistema de Atención y Soporte Técnico

Plataforma web interna para la gestión, seguimiento y auditoría de solicitudes de soporte técnico institucional. Permite administrar incidencias informáticas organizadas por departamentos, oficinas y equipos, facilitando la asignación de tareas y el control estadístico en una red local.

## Características Principales

* **Portal de autogestión para empleados:** Creación rápida de solicitudes vinculadas al departamento y equipo del usuario, con barra de progreso en cuatro etapas y confirmación de solución.
* **Tablero de trabajo de cinco etapas:** Flujo operativo dividido en Por aceptar, Asignado, En atención, En espera y Resuelto.
* **Control de inventario y estructura:** Registro de departamentos, oficinas, equipos informáticos con hoja de vida técnica y catálogo de fallas con tiempos estimados de atención.
* **Panel de estadísticas y reportes:** Gráficos interactivos de incidencias por fecha, departamento, equipo y técnico, con exportación de reportes en formato imprimible y hoja de cálculo.
* **Seguridad, auditoría y respaldos:** Control de permisos por rol, historial detallado de accesos y modificaciones de datos, monitor de estado del servidor, cierre forzado de sesiones y generación de copias de seguridad de la base de datos desde la interfaz.

## Roles del Sistema

1. **Superadministrador:** Control total de usuarios, roles, permisos, auditoría de accesos y cambios, monitor del servidor y copias de seguridad de la base de datos.
2. **Jefe de Departamento de Soporte:** Vista global del tablero de trabajo, recepción, aceptación o rechazo de solicitudes, asignación de técnicos, gestión de inventario y estadísticas generales.
3. **Personal de Soporte Técnico:** Vista filtrada del tablero con sus casos asignados, registro de diagnósticos, soluciones, reemplazo de piezas, notas técnicas internas y estadísticas individuales.
4. **Usuario Común:** Creación de solicitudes de soporte para los equipos de su oficina, seguimiento del estado de avance y comunicación pública con el técnico asignado.

## Requisitos Previos

* Servidor local como WampServer 3.4.0, XAMPP o Laragon.
* PHP 8.0 o superior con las extensiones `pdo_mysql`, `mbstring`, `fileinfo` y `openssl` habilitadas.
* MySQL 5.7 o superior, o MariaDB 10.4 o superior.
* Servidor web Apache 2.4 con el módulo `mod_rewrite` activo.

## Instalación Paso a Paso

### 1. Descargar el proyecto
Clona este repositorio o descarga el código fuente dentro de la carpeta pública de tu servidor local:

```bash
cd C:/wamp64/www/
git clone https://github.com/tu-usuario/sistema_tickets.git
```

### 2. Importar la base de datos
1. Inicia los servicios de Apache y MySQL en tu servidor local.
2. Abre `http://localhost/phpmyadmin` en tu navegador.
3. Ve a la pestaña **Importar** o **SQL**.
4. Selecciona o ejecuta el archivo ubicado en `database/db_tickets_soporte.sql`.
5. Esto creará automáticamente la base de datos `db_tickets_soporte` con sus 15 tablas, la vista estadística, el disparador de auditoría y los datos de prueba iniciales.

### 3. Configurar la conexión
Abre el archivo `config/Database.php` y verifica que las credenciales coincidan con tu entorno local. En WampServer los valores por defecto son:

```php
private string $host = "localhost";
private string $db_name = "db_tickets_soporte";
private string $username = "root";
private string $password = "";
```

### 4. Permisos de carpetas
Verifica que el servidor tenga permisos de escritura sobre las siguientes rutas para el guardado de adjuntos y copias de seguridad:
* `public/assets/uploads/`
* `database/backups/`

### 5. Ejecutar el sistema
Abre tu navegador web e ingresa a la siguiente dirección:

```text
http://localhost/sistema_tickets/
```

## Cuentas de Prueba Incluidas

Todas las cuentas de demostración incluidas en la base de datos utilizan la contraseña **`123456`**:

| Rol | Usuario | Correo | Cédula |
| :--- | :--- | :--- | :--- |
| Superadministrador | `superadmin` | `admin@comunidad.gob.ve` | `V-15000111` |
| Jefe de Soporte | `jefesoporte` | `jefe.soporte@comunidad.gob.ve` | `V-18222333` |
| Personal de Soporte | `tecnico1` | `miguel.torres@comunidad.gob.ve` | `V-22444555` |
| Personal de Soporte | `tecnico2` | `laura.gomez@comunidad.gob.ve` | `V-24666777` |
| Usuario Común | `mrodriguez` | `maria.rodriguez@comunidad.gob.ve` | `V-19888999` |
| Usuario Común | `jperez` | `juan.perez@comunidad.gob.ve` | `V-21333444` |

## Estructura del Proyecto

```text
sistema_tickets/
├── config/             # Configuración general, seguridad y conexión PDO
├── controllers/        # Controladores de cada módulo del sistema
├── models/             # Modelos de datos con consultas preparadas PDO
├── views/              # Plantillas de interfaz organizadas por módulo
├── public/             # Hojas de estilo, scripts JS, imágenes y archivos subidos
├── database/           # Script SQL principal y carpeta de copias de seguridad
└── index.php           # Enrutador frontal de la aplicación
```

## Arquitectura y Seguridad

* **Patrón de diseño:** Modelo Vista Controlador con programación orientada a objetos.
* **Base de datos:** Tercera Forma Normal con integridad referencial y borrado lógico para preservar el historial estadístico.
* **Protección aplicada:** Consultas preparadas nativas en PDO contra inyección SQL, validación de tokens en formularios, escape de salidas de texto, bloqueo temporal tras intentos fallidos de acceso, validación binaria de archivos subidos y restricción de acceso a directorios internos mediante `.htaccess`.
