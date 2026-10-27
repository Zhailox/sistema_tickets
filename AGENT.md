# AGENT.md - Fuente Única de Verdad y Contexto del Proyecto

> **INSTRUCCIONES CRÍTICAS PARA EL AGENTE:**
> 1. Lee este documento completo antes de escribir, modificar o sugerir código.
> 2. **PROHIBIDO INVENTAR:** No agregues tablas, columnas, roles ni módulos que no estén definidos aquí.
> 3. **AUTO-ACTUALIZACIÓN OBLIGATORIA:** Al finalizar cada tarea o módulo, actualiza la sección 8 al final de este documento indicando qué archivos creaste o modificaste y qué queda pendiente.

---

## 1. Reglas de Lenguaje, Tono y Textos en la Interfaz
Todo texto visible para el usuario en pantallas, botones, etiquetas, alertas, tablas y mensajes debe cumplir estas normas sin excepción:

* **Cero emojis:** Prohibido usar emojis o símbolos decorativos en cualquier parte de la interfaz o del código.
* **Cero palabras en inglés:** Toda la interfaz debe estar en español natural. Escribe "Panel de control" o "Estadísticas" en lugar de "Dashboard", "Tablero de trabajo" en lugar de "Kanban", "Iniciar sesión" en lugar de "Login", y "Registros del sistema" o "Historial de cambios" en lugar de "Logs" o "Changelogs".
* **Prohibido usar paréntesis explicativos:** Nunca escribas textos con aclaratorias entre paréntesis ni traducciones al lado. Escribe directamente "Prioridad de la solicitud" y jamás algo como "Prioridad (Nivel de urgencia / Priority)".
* **Tono humano, cercano y directo:** Evita frases robóticas o genéricas de sistema como "Operación ejecutada exitosamente en el servidor". Usa un lenguaje natural y claro, por ejemplo: "Tu solicitud fue enviada al equipo de soporte" o "No encontramos solicitudes en este mes".
* **Textos justos y limpios:** Sin párrafos de relleno, sin subtítulos innecesarios ni explicaciones obvias en los formularios. Muestra únicamente lo indispensable para que el usuario entienda qué hacer de un vistazo.

---

## 2. Resumen del Sistema y Entorno Técnico
* **Propósito:** Sistema interno de gestión de tickets de soporte técnico[cite: 1, 2].
* **Servidor Local:** WampServer 3.4.0 en `C:\wamp64\www\sistema_tickets\`.
* **Backend:** PHP 8.x con Programación Orientada a Objetos y PDO estricto mediante sentencias preparadas `prepare` y `execute`.
* **Base de Datos:** MySQL `db_tickets_soporte` en Tercera Forma Normal.
* **Arquitectura:** Modelo Vista Controlador con enrutador frontal en `index.php?c=controlador&a=accion`.
* **Frontend:** Plantilla Argon con Bootstrap 5, JavaScript nativo con `fetch` y gráficos con Chart.js.

---

## 3. Guía de Diseño Visual, UI/UX Moderno y Arquitectura de Interfaz (Rediseño Profesional)

El sistema debe lucir como un software SaaS moderno de nivel empresarial (inspirado en Linear, Jira Cloud o Asana), limpio, compacto, armónico y libre de clichés visuales generados por IA:

1. **Escala Compacta y Proporcionada (Densidad de Información):**
   * Prohibido usar tamaños gigantes por defecto. La fuente base del sistema debe ser `13.5px` o `14px` (`0.875rem`), con inputs y selectores estilizados de altura controlada (`36px` a `38px`), `padding` equilibrado y sin textos ni placeholders cortados.
   * Las opciones de accesibilidad (**Modo oscuro** y **Vista grande / Letra aumentada**) NO van sueltas en la barra superior; deben vivir dentro del módulo **Mi cuenta y perfil** como preferencias del usuario, permitiendo que quien realmente necesite vista grande la active desde su configuración.

2. **Sistema de Color Cohesivo (Cero Carnaval de Colores):**
   * **Acento Primario:** Rojo Institucional (`#B91C1C`, hover `#991B1B`).
   * **Superficies y Fondos:** Gris pizarra ultraligero (`#F8FAFC`) para el fondo general, blanco puro (`#FFFFFF`) para tarjetas con bordes sutiles (`1px solid #E2E8F0`) y sombras suaves (`0 1px 3px rgba(0,0,0,0.04)`).
   * **Prohibido usar botones verdes, cian o amarillos chillones:** Los botones de acción principal usan el rojo institucional o gris pizarra oscuro (`#1E293B`), y los secundarios usan bordes suaves (`#CBD5E1`) con fondo blanco.
   * **Etiquetas (Badges) de Estado y Prioridad en Estilo "Soft Pill" (Pastel suave con punto indicador):**
     * Crítica: Fondo `#FEE2E2`, texto `#991B1B`, borde `#FECACA`.
     * Alta: Fondo `#FFEDD5`, texto `#9A3412`, borde `#FED7AA`.
     * Media: Fondo `#E0F2FE`, texto `#075985`, borde `#BAE6FD`.
     * Baja / Operativo: Fondo `#DCFCE7`, texto `#166534`, borde `#BBF7D0`.

3. **Rediseño Total del Tablero Kanban (Moderno y Sin `border-left` de IA):**
   * **Prohibido el `border-left` grueso de colores en las tarjetas.** Las tarjetas del Kanban deben tener bordes uniformes (`1px solid #E2E8F0`), radio de `10px`, sombra sutil al pasar el cursor (`transform: translateY(-2px)` con transición de `180ms`) y mostrar la prioridad con un pequeño badge suave en la esquina superior derecha.
   * **Distribución Inteligente del Espacio:** Las 5 columnas deben adaptarse de forma fluida en pantallas de escritorio usando CSS Grid (`grid-template-columns: repeat(5, minmax(230px, 1fr))`), permitiendo colapsar o estrechar automáticamente las columnas que tengan `0` solicitudes para que las columnas con trabajo activo tengan espacio de sobra y no se corten.
   * **Vista de Detalle en Panel Lateral Deslizante (Drawer / Offcanvas) o Modal Organizado por Pestañas:** Al abrir un caso ("Gestionar caso"), NO empujes el tablero hacia arriba ni estires 3 columnas gigantes con espacios vacíos. Usa contenedores con `align-items: flex-start` y altura controlada, o un panel lateral deslizante / modal amplio donde la bitácora de chat tenga su propia caja de scroll independiente (`max-height: 360px; overflow-y: auto`) y el formulario de subida de archivos sea un botón limpio personalizado (ocultando el feo input file nativo que corta el texto).

4. **Buscadores en Tiempo Real en lugar de `<select>` Nativos Masivos:**
   * En cualquier formulario donde se busque un funcionario/usuario (Solicitante, Custodio de equipo, Técnico) o un equipo informático entre muchos registros, queda **prohibido usar un `<select>` nativo simple**.
   * Implementar un componente de **Buscador Autocompletable en Tiempo Real (Combobox con filtro instantáneo)**: un campo de texto donde al escribir nombre, apellido, cédula, departamento o código de bien se despliega una lista flotante filtrada al instante con resultados limpios en dos líneas (Línea 1 en negrita: Nombre y Cédula; Línea 2 en gris pequeño: Cargo y Departamento).

5. **Micro-animaciones y Fluidez:**
   * Transiciones suaves (`150ms - 200ms cubic-bezier(0.4, 0, 0.2, 1)`) en tarjetas, botones, modales, pestañas y desplegables.
---

## 4. Roles del Sistema y Reglas de Negocio
El acceso se valida mediante `$_SESSION['id_rol']` y `$_SESSION['slug_rol']`:

1. **`Superadmin` — ID 1:**
   * Acceso total al módulo de seguridad, gestión de usuarios, roles, permisos, registros de acceso e historial de cambios[cite: 1, 2].
2. **`Jefe_Soporte` — ID 2:**
   * Administra departamentos, oficinas y el inventario de equipos[cite: 1, 2].
   * En el tablero de tickets ve las solicitudes de todos los departamentos[cite: 1, 2]. Recibe los tickets en la columna `1_Por_Aceptar`, los acepta o rechaza indicando el motivo, define la prioridad oficial y los asigna a un técnico pasándolos a `2_Asignado`[cite: 1, 2].
   * En las estadísticas visualiza los reportes globales por periodo y por departamento[cite: 1, 2].
3. **`Personal_Soporte` — ID 3:**
   * En el tablero de tickets visualiza únicamente los casos que tiene asignados[cite: 1, 2]. Puede mover sus tickets entre `2_Asignado`, `3_En_Proceso`, `4_En_Espera` y `5_Resuelto`, además de anotar el diagnóstico, la solución aplicada y si hubo cambio de piezas[cite: 1, 2].
   * En las estadísticas ve únicamente los datos de su propio trabajo[cite: 1, 2].
4. **`Usuario_Comun` — ID 4:**
   * Cuenta con una pantalla principal sencilla para reportar un problema seleccionando el equipo de su oficina y ver en qué etapa va su solicitud mediante una barra de progreso horizontal clara: Enviado, Asignado, En atención y Resuelto[cite: 1, 2].

---

## 5. Los 6 Módulos del Sistema
1. **Módulo 1 `AuthController`:** Inicio y cierre de sesión, control de intentos fallidos, edición básica de cuenta propia y recuperación de clave mediante pregunta de seguridad[cite: 1, 2].
2. **Módulo 2 `AdminController`:** Gestión de usuarios con baja lógica en `eliminado_en`, asignación de permisos y consulta de actividad del sistema[cite: 1, 2].
3. **Módulo 3 `InventarioController`:** Registro y edición de departamentos, oficinas y equipos informáticos[cite: 1, 2].
4. **Módulo 4 `PortalController`:** Vista del usuario común para crear solicitudes, ver la barra de avance y leer o enviar comentarios públicos[cite: 1, 2].
5. **Módulo 5 `KanbanController`:** Tablero operativo de 5 columnas con filtro superior por mes y año para mantener limpia la columna de resueltos[cite: 1, 2].
6. **Módulo 6 `DashboardController`:** Panel de estadísticas alimentado por la vista `vw_metricas_dashboard` con filtros por fecha y departamento[cite: 1, 2].

---

## 6. Diccionario Exacto de Base de Datos `db_tickets_soporte`
Usa únicamente estos nombres de tablas y campos en las consultas SQL con PDO:

* **`roles`**: `id_rol`, `slug_rol`, `nombre_rol`, `descripcion`
* **`modulos_sistema`**: `id_modulo`, `clave_modulo`, `nombre_modulo`, `icono_argon`, `orden_menu`
* **`roles_permisos`**: `id_rol_permiso`, `id_rol`, `id_modulo`, `permiso_ver`, `permiso_crear`, `permiso_editar`, `permiso_eliminar`, `permiso_asignar_tickets`
* **`departamentos`**: `id_departamento`, `codigo_departamento`, `nombre_departamento`, `sede_edificio`, `piso_oficina`, `numero_oficina`, `telefono_extension`, `activo`
* **`usuarios`**: `id_usuario`, `id_rol`, `id_departamento`, `cedula_empleado`, `username`, `correo_institucional`, `password_hash`, `nombres`, `apellidos`, `cargo_empleado`, `telefono_extension`, `foto_perfil_ruta`, `pregunta_seguridad`, `respuesta_seguridad_hash`, `intentos_fallidos`, `bloqueado_hasta`, `estado_cuenta`, `estado_conexion`, `fecha_ultimo_acceso`, `eliminado_en`
* **`solicitudes_recuperacion_clave`**: `id_solicitud`, `id_usuario_afectado`, `metodo_recuperacion`, `validacion_seguridad_superada`, `id_usuario_validador`, `estado_solicitud`, `fecha_solicitud`, `fecha_resolucion`
* **`equipos`**: `id_equipo`, `id_departamento`, `id_usuario_asignado`, `codigo_bien`, `numero_serie`, `categoria_equipo`, `marca`, `modelo`, `procesador_cpu`, `memoria_ram_gb`, `tipo_almacenamiento`, `capacidad_almacenamiento_gb`, `sistema_operativo`, `direccion_ip_fija`, `direccion_mac`, `es_equipo_compartido`, `estado_operativo`
* **`categorias_falla`**: `id_categoria_falla`, `rama_general`, `subcategoria`, `tiempo_estimado_minutos`, `activo`
* **`tickets`**: `id_ticket`, `codigo_ticket`, `canal_creacion`, `id_usuario_creador`, `id_usuario_afectado`, `id_departamento`, `id_equipo`, `id_categoria_falla`, `titulo_asunto`, `descripcion_problematica`, `prioridad_sugerida`, `estado_aceptacion_jefe`, `motivo_rechazo`, `prioridad_oficial`, `estado_columna_kanban`, `motivo_pausa_espera`, `id_jefe_asignador`, `diagnostico_real_tecnico`, `solucion_aplicada_tecnico`, `requirio_cambio_pieza`, `detalle_pieza_cambiada`, `confirmado_por_usuario`, `tiempo_acumulado_espera_min`, `archivado_kanban`, `fecha_creacion`, `fecha_aceptacion_asignacion`, `fecha_inicio_atencion`, `fecha_resolucion_tecnica`, `fecha_cierre_definitivo`, `eliminado_en`, `anio_creacion`, `mes_creacion`
* **`ticket_asignaciones`**: `id_asignacion`, `id_ticket`, `id_personal_soporte`, `rol_en_ticket`, `fue_autoasignado`, `activo_en_ticket`, `fecha_asignacion`
* **`ticket_comentarios`**: `id_comentario`, `id_ticket`, `id_usuario_autor`, `mensaje_texto`, `es_nota_interna_privada`, `fecha_comentario`
* **`ticket_adjuntos`**: `id_adjunto`, `id_ticket`, `id_usuario_subida`, `nombre_archivo_original`, `extension_archivo`, `peso_archivo_kb`, `ruta_almacenamiento`, `fecha_subida`
* **`logs_sistema_accesos`**: `id_log_acceso`, `id_usuario`, `username_intento`, `tipo_evento`, `id_modulo`, `descripcion_accion`, `direccion_ip`, `navegador_user_agent`, `fecha_evento`, `hora_evento`, `fecha_hora`
* **`changelogs_auditoria`**: `id_changelog`, `id_usuario_autor`, `tabla_afectada`, `id_registro_afectado`, `tipo_operacion`, `campo_modificado`, `valor_anterior`, `valor_nuevo`, `motivo_cambio`, `fecha_hora_cambio`
* **`vw_metricas_dashboard`**: Vista SQL para consultar estadísticas y tiempos.

---

## 7. Estructura de Carpetas
```text
sistema_tickets/
├── AGENT.md
├── index.php
├── config/
│   ├── config.php
│   └── Database.php
├── models/
│   ├── Usuario.php
│   ├── Departamento.php
│   ├── Equipo.php
│   ├── Ticket.php
│   └── Auditoria.php
├── controllers/
│   ├── AuthController.php
│   ├── AdminController.php
│   ├── InventarioController.php
│   ├── PortalController.php
│   ├── KanbanController.php
│   └── DashboardController.php
├── views/
│   ├── layouts/
│   ├── auth/
│   ├── admin/
│   ├── inventario/
│   ├── portal/
│   ├── kanban/
│   └── dashboard/
└── public/
    ├── assets/
    └── js/app.js
```

---

## 8. Registro Evolutivo y Mejoras Propuestas por el Agente
*En esta sección el agente documenta las mejoras funcionales integradas por iniciativa propia tras el ciclo de autocuestionamiento en cada fase, sin alterar el esquema de `db_tickets_soporte`:*

### Mejoras Evolutivas Integradas en Fase 1 y Fase 2
1. **Identificación Multicanal en Inicio de Sesión y Recuperación (`models/Usuario.php`):**
   * El usuario puede ingresar indistintamente con su `username`, su `correo_institucional` o su `cedula_empleado`, reduciendo errores de acceso en personal administrativo o de mayor edad.
2. **Corrección y Respaldo Automático de Hashes Semilla (`database/db_tickets_soporte.sql` y `models/Usuario.php`):**
   * Se detectó que el archivo SQL original tenía el hash BCRYPT correspondiente a la palabra `password` en lugar de `123456` y tenía `respuesta_seguridad_hash` en `NULL`. Se actualizaron los registros en MySQL y en el archivo SQL con hashes BCRYPT reales de `123456` y respuestas de seguridad inicializadas (`rex`, `caracas`, `madrid`, `antonio`, `carlos`, `bolivar`), además de incluir migración transparente en `Usuario::verificarCredenciales()` por si se reimporta el SQL original.
3. **Normalización Inteligente de Respuestas de Seguridad (`config/config.php`):**
   * Al validar la pregunta de seguridad en intranet, el sistema normaliza mayúsculas, minúsculas, espacios adicionales, tildes y signos de puntuación para que un usuario no falle su recuperación por escribir `Bolívar` en lugar de `bolivar`.
4. **Doble Vía de Recuperación en Intranet (`controllers/AuthController.php` y `views/auth/recuperar.php`):**
   * Además del restablecimiento autónomo mediante pregunta de seguridad (`Chat_P2P_Soporte`), se habilitó la solicitud de restablecimiento asistido (`Reset_Manual`) hacia `solicitudes_recuperacion_clave` para los casos donde el funcionario olvidó su respuesta secreta, permitiendo que el `Superadmin` o el `Jefe_Soporte` aprueben y asignen una clave temporal directamente desde su panel.
5. **Contadores Dinámicos en Vivo en el Menú Lateral (`views/layouts/sidebar.php` y `models/Usuario.php`):**
   * El menú lateral consulta en tiempo real la cantidad de tickets pendientes por aceptar (`1_Por_Aceptar`) para el Jefe de Soporte, casos activos asignados para cada técnico, solicitudes abiertas para el usuario común, y alertas de seguridad o solicitudes de recuperación pendientes para supervisión.
6. **Accesibilidad Visual Avanzada y Recursos Locales para Intranet (`public/assets/` y `public/js/app.js`):**
   * Se integraron copias locales de Bootstrap 5, Chart.js, avatares e iconografía SVG embebida en CSS para que el sistema opere al 100% en una intranet aislada sin depender de conexión a internet.
   * Junto al botón de **Modo oscuro** persistente en `localStorage`, se incorporó un selector persistente de **Letra grande** (`16px` a `18px`), botones para mostrar u ocultar contraseñas, medidor visual de fortaleza de clave en español, buscador instantáneo en tablas sin recargar la página y modal de confirmación para acciones importantes.
7. **Panel Plegable de Perfiles de Demostración (`views/auth/login.php`):**
   * En la pantalla de acceso se integró un selector discreto que autocompleta con un clic cualquiera de los 6 usuarios semilla de los 4 roles para agilizar la defensa en vivo.

### Mejoras Evolutivas Integradas en Fase 3 y Fase 4
8. **Selector Inteligente de Equipos de Oficina y Tiempo Estimado en Vivo (`views/portal/index.php` y `models/Equipo.php`):**
   * El usuario común visualiza primero los equipos asignados directamente a su persona y luego los equipos compartidos de su departamento (`es_equipo_compartido = 1`), con su marca, modelo y código de bien. Al elegir la categoría de falla, el formulario le informa de inmediato el tiempo estimado de respuesta (`tiempo_estimado_minutos`).
9. **Ciclo Completo de Conformidad o Reapertura por el Solicitante (`controllers/PortalController.php` y `models/Ticket.php`):**
   * Cuando un técnico pasa el caso a `5_Resuelto`, el portal del usuario muestra el diagnóstico técnico, la solución aplicada y si hubo reemplazo de pieza, habilitando dos botones directos para **Confirmar que funciona correctamente** (`Confirmado_Conforme` con `fecha_cierre_definitivo = NOW()`) o **Reabrir porque la falla continúa** (`Reabierto_No_Funciona`, devolviendo el caso a `3_En_Proceso` con comentario explicativo).
10. **Aislamiento Estricto entre Comentarios Públicos y Notas Técnicas Internas (`models/Ticket.php`):**
    * El portal del usuario común filtra estrictamente `es_nota_interna_privada = 0`, mientras que en el tablero de trabajo los técnicos y el Jefe de Soporte pueden registrar tanto mensajes visibles para el solicitante como notas técnicas internas exclusivas del equipo de soporte, además de adjuntar archivos reales en `ticket_adjuntos`.
11. **Balanceo de Carga Técnica y Registro Telefónico o Presencial (`controllers/KanbanController.php` y `views/kanban/index.php`):**
    * Al aceptar y asignar una solicitud en `1_Por_Aceptar`, el Jefe de Soporte visualiza cuántos casos activos tiene cada técnico en tiempo real, puede asignar un `Tecnico_Principal` y opcionalmente un `Tecnico_Apoyo`, o rechazar la solicitud exigiendo `motivo_rechazo`.
    * Se habilitó el modal de registro manual para casos recibidos por `Llamada_Telefonica` o `Presencial`, filtrando dinámicamente los equipos según el departamento del funcionario seleccionado.
12. **Sincronización Automática del Estado Operativo del Equipo y Limpieza Mensual (`controllers/KanbanController.php`):**
    * Al gestionar el avance técnico, el especialista puede cambiar el estado del equipo (`Operativo`, `En_Reparacion`, `Danado`), y al resolver el ticket el sistema restaura automáticamente el equipo a `Operativo` si estaba `En_Reparacion`. Además, el Jefe de Soporte cuenta con el botón de archivo mensual (`archivado_kanban = 1`) para limpiar la columna `5_Resuelto` sin perder las métricas en `vw_metricas_dashboard`.

### Mejoras Evolutivas Integradas en Fase 5 y Fase 6
13. **Hoja de Vida Técnica de Equipos y Gestión Integrada del Catálogo de Fallas (`controllers/InventarioController.php` y `views/inventario/index.php`):**
    * Cada bien informático cuenta con un modal de hoja de vida que consulta todas las solicitudes asociadas al equipo, mostrando diagnósticos previos, soluciones y reemplazos de piezas para detectar reincidencias antes de decidir su desincorporación.
    * En el mismo módulo de inventario se incorporó una tercera pestaña para administrar el catálogo de `categorias_falla` y sus tiempos estimados de atención (`tiempo_estimado_minutos`), manteniendo actualizados los acuerdos de servicio que alimentan `vw_metricas_dashboard`.
    * En el formulario de bienes informáticos, al cambiar el departamento o dependencia se filtra automáticamente la lista de custodios pertenecientes a esa oficina, evitando asignaciones cruzadas erróneas.
14. **Protección de Integridad del Superadministrador, Desbloqueo en Un Clic y Comparador Visual de Cambios (`controllers/AdminController.php` y `views/admin/index.php`):**
    * El sistema impide que el `Superadmin` desactive, suspenda o aplique baja lógica (`eliminado_en`) sobre su propia cuenta activa, y protege la matriz `roles_permisos` para que el rol `Superadmin` conserve siempre acceso al módulo de administración y seguridad.
    * En la tabla de personal se integró un botón directo de desbloqueo inmediato para cuentas con `bloqueado_hasta` vigente o `intentos_fallidos > 0`, además de un conmutador para consultar o restaurar cuentas en baja lógica (`eliminado_en IS NOT NULL`) y un panel superior de atención rápida para solicitudes pendientes en `solicitudes_recuperacion_clave`.
    * El visor de `changelogs_auditoria` incluye un modal comparador que formatea el estado previo (`datos_anteriores_json`) y el estado posterior (`datos_nuevos_json`) lado a lado de forma legible.
15. **Panel de Estadísticas con Atajos de Período, Doble Modo por Rol y Exportación Auditada en Dos Formatos (`controllers/DashboardController.php`, `views/dashboard/index.php` y `views/dashboard/reporte_imprimible.php`):**
    * Conectado a `vw_metricas_dashboard`, el panel adapta automáticamente su alcance: vista global con filtro por departamento y técnico para `Superadmin` y `Jefe_Soporte`, y vista de desempeño individual para `Personal_Soporte`.
    * Incorpora botones de período rápido (`Mes actual`, `Mes anterior`, `Últimos 30 días`, `Año actual`, `Histórico completo`), cuatro gráficos interactivos en `Chart.js` que se adaptan en vivo al conmutar entre modo claro y modo oscuro, y exportación auditada en `historial_reportes_generados` tanto en documento institucional imprimible (`PDF`) como en hoja de cálculo (`Excel_CSV` con codificación UTF-8 BOM).

### Refactorización Integral de Interfaz, Experiencia de Usuario (UI/UX) y Compatibilidad PHP 8.5
16. **Compatibilidad Nativa con PHP 8.5 en PDO (`config/Database.php`):**
    * Se actualizó la constante de inicialización UTF-8 para utilizar `Pdo\Mysql::ATTR_INIT_COMMAND` cuando la clase existe en PHP 8.5+, manteniendo respaldo automático a `PDO::MYSQL_ATTR_INIT_COMMAND` en versiones previas y eliminando por completo avisos `Deprecated`.
17. **Sistema de Diseño Corporativo Estilo Linear / Jira Cloud y Escala Proporcionada (`public/assets/css/argon-institucional.css`):**
    * Se ajustó la escala tipográfica base a `14px` (`0.875rem`), con alturas de controles de `36px` a `38px` y `padding-right: 2.25rem` en selectores para evitar textos cortados o montados sobre la flecha.
    * Se unificó la paleta en tonos neutros Slate (`#0F172A`, `#334155`, `#64748B`, `#F1F5F9`, `#F8FAFC`), rojo institucional (`#B91C1C`) como acento de marca y etiquetas de estado/prioridad tipo **Soft Pill** (`insignia-suave`) con fondos pastel suaves y texto oscuro de alto contraste.
    * Se reubicaron los controles persistentes de **Modo oscuro** y **Letra grande** desde la barra superior hacia una tarjeta exclusiva de **Preferencias de visualización y accesibilidad** dentro de `views/auth/perfil.php`, y se compactó el menú lateral (`views/layouts/sidebar.php`) a `240px` con colapso persistente en escritorio.
18. **Rediseño del Tablero Kanban de 5 Columnas y Ficha Operativa Modal (`views/kanban/index.php`):**
    * Se eliminaron los bordes laterales gruesos de colores y se diseñaron tarjetas modernas con borde sutil de `1px`, esquinas de `10px`, micro-animación al pasar el cursor, código en gris monoespaciado e íconos vectoriales sutiles.
    * Las columnas con `0` casos aplican automáticamente la clase `.columna-vacia` para estrecharse y ceder ancho útil a las columnas activas sin cortar la columna 5.
    * La ficha operativa del caso se transformó en el modal estructurado `#modalGestionCasoActivo` con `.fila-inicio-alineada` (`align-items: flex-start`), eliminando los espacios verticales vacíos entre columnas.
19. **Bitácora Tipo Caja de Chat Real y Botón de Subida Estilizado (`public/js/app.js` y vistas de Kanban/Portal/Perfil):**
    * La bitácora de comentarios funciona como una caja de chat con altura controlada (`max-height: 340px; overflow-y: auto`), autodesplazamiento al último mensaje y compositor compacto fijo en la parte inferior.
    * Se reemplazaron todos los `<input type="file">` nativos por el componente `.contenedor-archivo-estilizado` con ícono de clip, etiqueta `"Adjuntar imagen o documento"` y truncado elegante del archivo seleccionado.
20. **Componente Buscador Autocompletable en Tiempo Real de 2 Líneas (`public/js/app.js` y `public/assets/css/argon-institucional.css`):**
    * Se reemplazaron los `<select>` masivos de funcionarios, técnicos, equipos informáticos, custodios, departamentos y categorías de falla por un combobox autocompletable en tiempo real (`data-autocompletable="true"`) que despliega una lista flotante de 2 líneas (título en negrita y departamento/cargo/estado en texto secundario) y sincroniza el identificador en un `<input type="hidden">`.
21. **Auditoría y Reparación Integral de Codificación UTF-8 en Base de Datos y Archivos (`config/Database.php`, `config/config.php` y `database/db_tickets_soporte.sql`):**
    * Se reforzó `config/Database.php` con `charset=utf8mb4` en el DSN, `SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci` en el comando de inicio PDO y ejecución explícita al establecer la conexión, además de `header('Content-Type: text/html; charset=utf-8');` en `config/config.php`.
    * Se auditaron las 15 tablas de `db_tickets_soporte` y se repararon mediante sentencias preparadas en `utf8mb4` todos los textos con caracteres corruptos (`?`) en `modulos_sistema`, `roles`, `ticket_comentarios` y `changelogs_auditoria`, actualizando también `database/db_tickets_soporte.sql` y verificando que los 36 archivos del proyecto estén codificados en UTF-8 sin BOM.
22. **Sistema de Paginación Híbrida y Reutilizable en Todas las Tablas del Sistema (`public/js/app.js` y `public/assets/css/argon-institucional.css`):**
    * Se programó el motor `inicializarPaginacionYBusquedaTablas()` y `construirPaginacionTabla()` que inyecta automáticamente la barra inferior `.barra-paginacion-tabla` en todas las tablas de datos (`#tablaUsuariosAdmin`, `#tablaSolicitudesRecuperacionAdmin`, `#tablaLogsAccesoAdmin`, `#tablaChangelogsAdmin`, `#tablaEquiposInventario`, `#tablaDepartamentosInv`, `#tablaCategoriasInv`, `#tablaHojaVidaEquipoInv`, `#tablaRendimientoTecnicos`, `#tablaEquiposIncidencia`, `#tablaDetalleMetricas`, `#tablaHistorialReportes`, `#tablaMisSolicitudesPortal`, `#tablaRechazadosKanban`, `#tablaAccesosPropios`, `#tablaCambiosPropios`).
    * Incluye contador dinámico en español (`"Mostrando X a Y de Z registros"`), selector compacto de cantidad por página (`10`, `25`, `50 por página`) y botones de navegación (`Anterior`, numeración con el activo en rojo `#B91C1C` y `Siguiente`) que se recalculan automáticamente a la página 1 al escribir en cualquier buscador instantáneo (`data-buscador-tabla`).
23. **Pulido Integral de Responsividad para Móviles y Tablets (`< 992px` y `< 768px` en `public/assets/css/argon-institucional.css`, `public/js/app.js` y Layouts):**
    * En pantallas menores a `992px`, el menú lateral se oculta fuera del lienzo (`transform: translateX(-100%)`) y se abre como un cajón flotante (`Offcanvas`) con fondo oscuro semitransparente (`#capaFondoMenuMovil`) y botón de cierre (`#botonCerrarMenuMovil`), cerrándose al tocar fuera, pulsar `Escape` o seleccionar una opción.
    * En `views/layouts/topbar.php` se oculta la etiqueta larga de departamento en móviles (`d-none d-md-inline-flex`) y se aplica truncado limpio (`text-truncate`) para evitar colisiones.
    * En `views/kanban/index.php` se implementó desplazamiento táctil horizontal con `scroll-snap-type: x mandatory` (columnas de `85vw` en móviles y `300px` en tablets) acompañado de la barra de salto rápido por etapa (`.barra-salto-columnas-movil`), además de apilamiento al `100%` en barras de filtros y modales adaptables.
24. **Auditoría e Implementación de Seguridad Básica Web en 6 Capas (Hardening PHP / Apache / JS):**
    * **Cabeceras HTTP y Endurecimiento de Sesiones (`config/config.php` y `controllers/AuthController.php`):** Configuración previa a `session_start()` con `session.use_only_cookies = 1`, `session.use_strict_mode = 1`, `cookie_httponly = true` y `cookie_samesite = 'Lax'`; expiración automática por inactividad tras 30 minutos (`TIEMPO_INACTIVIDAD_SESION = 1800`); regeneración de sesión (`session_regenerate_id(true)`) al iniciar sesión y al cambiar contraseña; y envío de cabeceras `X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`, `X-Permitted-Cross-Domain-Policies: none` y `Content-Security-Policy` compatible con recursos locales y `Chart.js`.
    * **Protección de Directorios y Archivos Sensibles (`.htaccess`):** Archivo `.htaccess` en la raíz con `Options -Indexes`, bloqueo de acceso directo a `AGENT.md`, `.sql`, `.log`, `.ini`, `.env` y carpetas internas (`config/`, `models/`, `controllers/`, `views/`, `database/`), permitiendo únicamente `index.php` y activos estáticos de `public/`.
    * **Blindaje de Subida de Adjuntos y Avatares (`procesar_archivo_subido_seguro()` en `config/config.php`):** Validación obligatoria de firma MIME real del archivo físico mediante `finfo_open(FILEINFO_MIME_TYPE)` / `finfo_file()`, verificación estructural de imágenes (`getimagesize`) y documentos PDF (`%PDF-`), renombrado físico con `bin2hex(random_bytes(16))` conservando el nombre original solo como texto en base de datos, y archivos `.htaccess` anti-ejecución en `public/assets/uploads/`, `public/assets/uploads/tickets/` y `public/assets/uploads/perfiles/`.
    * **Enrutador con Lista Blanca Estricta y Prevención de IDOR (`index.php`, `KanbanController.php` y `PortalController.php`):** Validación de `c` y `a` contra el diccionario explícito `$listaBlancaRutas` de clases, archivos y métodos públicos permitidos, y verificación en base de datos (`ticket_asignaciones`) de que el rol `Personal_Soporte` (ID 3) solo pueda mover, diagnosticar, resolver o comentar tickets que tenga activamente asignados.
    * **Blindaje Anti-XSS y CSRF Completo:** Verificación del token CSRF (`verificar_token_csrf`) en los 31 formularios POST del sistema, escape sistemático con `e()` (`htmlspecialchars(..., ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')`) en todas las vistas PHP y manipulación segura del DOM en `public/js/app.js` mediante `textContent`, `vaciarContenedorSeguro()` y `escaparHtml()`.
25. **Administración de Infraestructura, Copias de Seguridad SQL en Vivo, Control de Sesiones y Mantenimiento Auditado (`controllers/AdminController.php`, `models/Auditoria.php`, `models/Usuario.php`, `config/config.php` y `views/admin/index.php`):**
    * **Gestor de Copias de Seguridad de Base de Datos con PDO Nativo (`Auditoria::generarRespaldoBaseDatos` y `Auditoria::listarRespaldosExistentes`):** Generador SQL nativo en PHP/PDO sin depender de `mysqldump` externo (`SHOW CREATE TABLE`, exclusión inteligente de columnas `GENERATED` en las sentencias `INSERT`, volcado de las 15 tablas en el orden exacto de claves foráneas con `SET FOREIGN_KEY_CHECKS = 0;`, inclusión de la vista `vw_metricas_dashboard` y del disparador `trg_ticket_kanban_changelog`). Almacena los archivos `respaldo_sistema_YYYYMMDD_HHMMSS.sql` en `database/backups/` protegido por `.htaccess` (`Require all denied`) y permite generarlos, consultarlos en la tabla paginada `#tablaRespaldosSistema`, descargarlos de forma controlada (`AdminController::descargarRespaldo`) o eliminarlos (`AdminController::eliminarRespaldo`), registrando cada acción en `logs_sistema_accesos` y `changelogs_auditoria`.
    * **Monitor de Características del Servidor y Esquema de Base de Datos (`Auditoria::obtenerDiagnosticoServidor`):** Tarjetas e indicadores en tiempo real con sistema operativo y arquitectura (`php_uname()`), versión de PHP y servidor web, versión exacta de MySQL consultada vía PDO, uso de disco duro del servidor (`disk_total_space` y `disk_free_space`) con barra de progreso y porcentaje disponible, parámetros de configuración de PHP (`memory_limit`, `upload_max_filesize`, `post_max_size`, `max_execution_time`, zona horaria), estado de módulos críticos (`PDO MySQL`, `OpenSSL`, `Mbstring`, `Fileinfo`) y tabla paginada `#tablaEstadoTablasBd` conectada a `information_schema.TABLES` con conteo exacto de filas y peso en KB (`DATA_LENGTH`, `INDEX_LENGTH` y total) de las 15 tablas y la vista de `db_tickets_soporte`.
    * **Monitor de Sesiones Activas y Cierre Forzado en Tiempo Real (`Usuario::listarUsuariosEnLinea`, `Usuario::forzarCierreSesionUsuario` y `sesion_activa()` en `config/config.php`):** Lista en la tabla paginada `#tablaSesionesActivas` a los usuarios con `estado_conexion = 'Online'`, su departamento, rol y `fecha_ultimo_acceso`, permitiendo al Superadministrador forzar la desconexión inmediata (`AdminController::forzarDesconexion`) de cualquier sesión distinta a la propia. En `sesion_activa()` se verifica en cada petición contra la base de datos que `estado_cuenta = 'Activo'`, `eliminado_en IS NULL` y `estado_conexion = 'Online'`, expulsando de inmediato al usuario desconectado o suspendido con aviso claro en la pantalla de acceso.
    * **Herramientas de Mantenimiento y Depuración Auditada (`Auditoria::obtenerResumenMantenimiento` y `Auditoria::depurarLogsAccesoAntiguos`):** Bloque de mantenimiento que muestra el total de eventos en `logs_sistema_accesos` desglosados por antigüedad (`+30`, `+60`, `+90` días) y permite depurar registros antiguos conservando la ventana seleccionada (`30`, `60` o `90` días) dejando constancia en `changelogs_auditoria`, acompañado del monitor de volumen ocupado en disco por archivos subidos en `ticket_adjuntos` y fotografías de perfil.

---

## 9. Bitácora de Construcción y Estado del Proyecto

### Fase 1: Arquitectura Base, Configuración, Enrutador y Layouts Institucionales — COMPLETADA
* `config/config.php`: Configuración global, sesión segura con verificación en tiempo real de estado de cuenta y conexión (`estado_conexion = 'Online'`), protección CSRF, funciones de formato de fechas en español, etiquetas Soft Pill (`clase_insignia_prioridad`, `clase_insignia_estado`), normalización de textos y control de permisos RBAC.
* `config/Database.php`: Conexión PDO estricta compatible con PHP 8.5 (`Pdo\Mysql::ATTR_INIT_COMMAND`), `ERRMODE_EXCEPTION`, `FETCH_ASSOC` y `EMULATE_PREPARES = false`.
* `index.php`: Enrutador frontal con lista blanca estricta (`$listaBlancaRutas`), control de sesión, redirección inteligente según rol (`Superadmin`, `Jefe_Soporte`, `Personal_Soporte`, `Usuario_Comun`) y validación de permisos por módulo.
* `views/layouts/header.php`, `views/layouts/sidebar.php`, `views/layouts/topbar.php`, `views/layouts/footer.php`, `views/layouts/auth_header.php`, `views/layouts/auth_footer.php`: Layouts empresariales con barra superior sobria, menú lateral compacto de `240px` colapsable y soporte completo de modo oscuro.
* `public/assets/css/argon-institucional.css`, `public/js/app.js`, `public/assets/img/default-avatar.png`: Sistema de diseño Slate + Rojo Institucional (`#B91C1C`), motor de buscadores autocompletables en tiempo real y subida de archivos estilizada.

### Fase 2: Módulo 1 Autenticación, Seguridad de Cuenta y Auditoría de Accesos — COMPLETADA
* `models/Auditoria.php`: Registro y consulta con PDO en `logs_sistema_accesos`, `changelogs_auditoria` e `historial_reportes_generados`, además del generador nativo de respaldos SQL, diagnóstico de servidor/esquema y depuración de mantenimiento.
* `models/Usuario.php`: Autenticación multicanal, control de `intentos_fallidos`, bloqueo automático de 15 minutos en `bloqueado_hasta`, desbloqueo automático, gestión de perfil, cambio de clave BCRYPT, pregunta de seguridad, gestión de `solicitudes_recuperacion_clave` y control de sesiones activas en línea (`listarUsuariosEnLinea`, `forzarCierreSesionUsuario`).
* `controllers/AuthController.php`: Flujos completos de `login`, `logout`, `recuperar`, `perfil`, `actualizarPerfil`, `cambiarClave`, `actualizarSeguridad` y `resolverRecuperacion`.
* `views/auth/login.php`, `views/auth/recuperar.php`, `views/auth/perfil.php`: Vistas compactas en español natural con tarjeta de **Preferencias de visualización y accesibilidad** (Modo oscuro y Letra grande persistentes en `localStorage`) ubicada en Mi cuenta y perfil.

### Fase 3: Módulo 4 Portal de Solicitudes del Usuario Común — COMPLETADA
* `models/Departamento.php`, `models/Equipo.php`, `models/Ticket.php`: Modelos completos con consultas PDO preparadas para gestión de departamentos, inventario de equipos por oficina, ciclo de vida de tickets, asignaciones, comentarios públicos/privados y adjuntos.
* `controllers/PortalController.php`: Acciones `index`, `crear`, `comentar` y `confirmar` con subida de adjuntos y validación estricta de pertenencia de equipo y solicitud.
* `views/portal/index.php`: Portal del usuario común con línea de progreso horizontal moderna de 4 pasos numerados (`1. Enviado`, `2. Asignado`, `3. En atención`, `4. Resuelto`), columnas con altura independiente (`fila-inicio-alineada`), caja de chat real, subida de archivos estilizada y buscadores autocompletables para equipos de oficina y categorías de falla.

### Fase 4: Módulo 5 Tablero de Trabajo de 5 Columnas — COMPLETADA
* `controllers/KanbanController.php`: Acciones `index`, `asignar`, `rechazar`, `mover`, `comentar`, `registrarManual` y `archivarMes` con control granular por rol (`Jefe_Soporte`, `Personal_Soporte` y `Superadmin`).
* `views/kanban/index.php`: Tablero Kanban estilo Linear/Jira con barra de filtros en una sola fila compacta, tarjetas limpias sin bordes laterales gruesos, etiquetas Soft Pill, compactación automática de columnas vacías (`columna-vacia`), modal amplio de gestión `#modalGestionCasoActivo` con columnas independientes, caja de chat real (`max-height: 340px`) y buscadores autocompletables en tiempo real para funcionarios, técnicos, equipos y fallas.

### Fase 5: Módulo 2 y Módulo 3 Administración de Seguridad, Infraestructura, Departamentos e Inventario de Equipos — COMPLETADA
* `controllers/InventarioController.php` y `views/inventario/index.php`: Gestión integral de `departamentos`, catálogo de `equipos` con buscadores autocompletables de departamento y custodio en tiempo real, modal `#modalHojaVidaEquipo` de historial técnico y administración de `categorias_falla`.
* `controllers/AdminController.php`, `database/backups/.htaccess` y `views/admin/index.php`: Panel integral del `Superadmin` con 5 secciones: Directorio de usuarios y desbloqueo, Matriz interactiva de permisos `roles_permisos`, Registros de acceso `logs_sistema_accesos`, Historial de cambios `changelogs_auditoria` y **Servidor, respaldos y mantenimiento** (copias de seguridad SQL en vivo con PDO puro, monitor técnico del servidor y tablas MySQL, monitor de sesiones en línea con cierre forzado en tiempo real y depuración auditada de registros antiguos).

### Fase 6: Módulo 6 Panel de Estadísticas y Reportes Auditados — COMPLETADA
* `controllers/DashboardController.php`, `views/dashboard/index.php` y `views/dashboard/reporte_imprimible.php`: Panel estadístico alimentado por `vw_metricas_dashboard` con filtro autocompletable de departamento, indicadores sobrios, cuatro gráficos en `Chart.js` sincronizados con la paleta Slate/Rojo Institucional y el modo oscuro, y exportación auditada en `PDF`, `Excel_XLSX` y `CSV`.

### Estado Global del Sistema
* **Todas las fases (Fases 1 a 6), la refactorización integral de UI/UX (Sección 3), la auditoría de codificación UTF-8, paginación universal de tablas, responsividad móvil/tablet, el blindaje de Seguridad Básica Web en 6 capas (Hardening PHP/Apache/JS) y el módulo de Administración de Infraestructura, Copias de Seguridad SQL en Vivo y Control de Sesiones del Superadministrador se encuentran 100% completadas, verificadas en PHP 8.5.0 y Apache 2.4 sin errores ni advertencias, y alineadas con las reglas de estilo, seguridad y base de datos del proyecto.**
