# CLAUDE.md — Plugin LMSACE Reports

## Descripcion General del Proyecto

**Paquete:** `report_lmsace_reports`
**Tipo:** Plugin de reportes para Moodle (se instala en `report/lmsace_reports`)
**Version:** v1.1 (codigo de version `2025060203`)
**Licencia:** GNU GPL v3
**Moodle soportado:** 4.04 (404) hasta 5.0 (500)
**PHP soportado:** 7.4, 8.0, 8.2

Este es un plugin integral de analitica y reportes para Moodle LMS. Proporciona widgets de dashboard configurables para reportes a nivel de sitio, curso, usuario, profesor y evaluacion. El plugin es de **solo lectura** — no crea tablas de base de datos propias, solo consulta las tablas estandar de Moodle.

## Estructura del Repositorio

```
├── amd/                          # Modulos JavaScript AMD (sistema de modulos JS de Moodle)
│   ├── build/                    # JS compilado/minificado (generado por Grunt)
│   └── src/                      # Archivos JS fuente
│       ├── main.js               # Orquestador principal, cargado por index.php
│       └── chartjs-plugin-datalabels.js  # Plugin Chart.js de terceros (MIT)
├── classes/                      # Clases PHP (autoload PSR-4 bajo report_lmsace_reports\)
│   ├── cache/loader.php          # Cargador de cache personalizado
│   ├── local/
│   │   ├── table/                # Clases de tablas dinamicas + clases filterset
│   │   └── widgets/              # Implementaciones de widgets (ubicacion CANONICA)
│   ├── output/                   # Clases renderable/renderer de Moodle
│   │   ├── lmsace_reports.php    # Renderable principal
│   │   ├── renderer.php          # Renderer del plugin (extiende plugin_renderer_base)
│   │   ├── report_widgets.php    # Renderer de coleccion de widgets
│   │   └── widgets_info.php      # Clase base para todos los widgets
│   ├── privacy/provider.php      # API de privacidad (null_provider — no almacena datos de usuario)
│   ├── table/                    # Clases de tablas legacy (duplicados de local/table/)
│   ├── widgets/                  # Clases de widgets legacy (duplicados de local/widgets/)
│   ├── report_helper.php         # Helper estatico central (~893 lineas de consultas SQL/procesamiento)
│   └── widgets.php               # Registro de widgets y helpers estaticos
├── db/
│   ├── access.php                # Definiciones de capacidades (7 capacidades)
│   ├── caches.php                # Definiciones de cache (reportwidgets)
│   └── services.php              # Definiciones de servicios web/endpoints AJAX (6 servicios)
├── form/
│   └── chooser_form.php          # Clases de formularios Moodle (selectores de curso/usuario/profesor)
├── lang/
│   └── en/report_lmsace_reports.php  # Cadenas de idioma en ingles
├── templates/                    # Plantillas Mustache
│   ├── widgets/                  # Plantillas especificas de widgets
│   └── widgetstable/             # Plantillas de widgets de tabla
├── tests/
│   └── behat/
│       └── reports_management.feature  # Tests de aceptacion Behat
├── .github/workflows/
│   └── moodle-ci.yml             # Pipeline de CI con GitHub Actions
├── cache.php                     # Manejador de purga de cache
├── externallib.php               # Implementaciones de servicios web (~437 lineas)
├── index.php                     # Punto de entrada principal / pagina de reportes
├── lib.php                       # Biblioteca del plugin: constantes + hooks de navegacion
├── settings.php                  # Configuracion del admin: habilitar/deshabilitar widgets
├── styles.css                    # CSS del plugin
├── thirdpartylibs.xml            # Declaraciones de bibliotecas de terceros
└── version.php                   # Metadatos de version del plugin
```

## Arquitectura

### Sistema de Widgets

El plugin utiliza una arquitectura basada en widgets. Cada elemento de reporte es un widget autocontenido:

1. **Stack widgets** agrupan widgets hijos por categoria de reporte (ej: `stacksitereportswidget` contiene todos los widgets a nivel de sitio)
2. **Widgets individuales** manejan la obtencion de datos y el renderizado de plantillas
3. Todos los widgets extienden `classes/output/widgets_info.php`
4. Los widgets se registran mediante constantes en `lib.php` y se activan/desactivan en `settings.php`

Las clases de widgets se encuentran en `classes/local/widgets/`. Existe un conjunto paralelo en `classes/widgets/` (legacy). Las versiones en `local/` son las canonicas.

### Flujo de Datos

```
Navegador → JS AMD (amd/src/main.js)
          → Llamadas AJAX a servicios web (externallib.php)
          → Clases de widgets obtienen datos via report_helper.php
          → Consultas SQL contra tablas estandar de Moodle
          → Plantillas Mustache renderizan la salida
          → Respuesta devuelta al navegador
```

### Tipos de Reportes

| Tipo de Reporte | Capacidad Requerida | Nivel de Contexto | Cantidad de Widgets |
|---|---|---|---|
| Sitio | `viewsitereports` | Sistema | 10 |
| Curso | `viewcoursereports` | Curso | 6 |
| Usuario | `viewuserreports` | Usuario | 8 |
| Profesor | `viewteacherreports` | Sistema | 5 |
| Evaluacion | `viewevaluationreports` | Sistema | 4 |

### Clases Principales

- **`report_helper`** (`classes/report_helper.php`): Capa central de acceso a datos. Contiene todas las consultas SQL y procesamiento de datos como metodos estaticos.
- **`widgets`** (`classes/widgets.php`): Registro de widgets. Mapea identificadores de widgets a nombres de clases.
- **`renderer`** (`classes/output/renderer.php`): Renderer de Moodle. Orquesta que widgets mostrar segun el tipo de reporte y la configuracion del admin.
- **`report_lmsace_reports_external`** (`externallib.php`): Los 6 metodos de servicios web AJAX.

### Servicios Web (Endpoints AJAX)

Definidos en `db/services.php`, implementados en `externallib.php`:

| Servicio | Metodo | Proposito |
|---|---|---|
| `report_lmsace_reports_get_chart_reports` | `get_chart_reports` | Obtener datos de graficos |
| `report_lmsace_reports_activity_progress_reports` | `get_activity_progress_reports` | Progreso de actividades |
| `report_lmsace_reports_table_reports` | `get_table_reports` | Datos de tablas/info |
| `report_lmsace_reports_enrollment_completion_month` | `get_enrollment_completion_bymonths` | Estadisticas mensuales |
| `report_lmsace_reports_site_visits` | `get_site_visits` | Registros de visitas |
| `report_lmsace_reports_get_moodle_used_size` | `get_moodle_used_size` | Uso de disco |

### Capacidades

Definidas en `db/access.php`:

- `report/lmsace_reports:definestudents` — Rol estudiante (contexto de curso)
- `report/lmsace_reports:viewsitereports` — Rol manager (contexto de sistema)
- `report/lmsace_reports:viewcoursereports` — Profesor/profesor editor (contexto de curso)
- `report/lmsace_reports:viewuserreports` — Rol usuario (contexto de usuario)
- `report/lmsace_reports:viewotheruserreports` — Rol manager (contexto de usuario)
- `report/lmsace_reports:viewteacherreports` — Rol manager (contexto de sistema)
- `report/lmsace_reports:viewevaluationreports` — Rol manager (contexto de sistema)

## Flujo de Desarrollo

### Pipeline de CI

El workflow de GitHub Actions (`.github/workflows/moodle-ci.yml`) se ejecuta en push y pull requests con esta matriz de pruebas:

| PHP | Rama de Moodle | Base de Datos |
|---|---|---|
| 8.2 | MOODLE_403_STABLE | PostgreSQL 13 |
| 8.0 | MOODLE_402_STABLE | MariaDB 10 |
| 8.0 | MOODLE_401_STABLE | PostgreSQL 13 |
| 7.4 | MOODLE_400_STABLE | MariaDB 10 |

### Verificaciones de CI (en orden)

1. **PHP Lint** — `moodle-plugin-ci phplint`
2. **PHP Mess Detector** — `moodle-plugin-ci phpmd` (continua ante errores)
3. **Moodle Code Checker (PHPCS)** — `moodle-plugin-ci phpcs --max-warnings 0`
4. **Moodle PHPDoc Checker** — `moodle-plugin-ci phpdoc --max-warnings 0`
5. **Validacion** — `moodle-plugin-ci validate`
6. **Upgrade Savepoints** — `moodle-plugin-ci savepoints`
7. **Mustache Lint** — `moodle-plugin-ci mustache`
8. **Grunt** — `moodle-plugin-ci grunt` (compilacion JS)
9. **PHPUnit** — `moodle-plugin-ci phpunit --fail-on-warning`
10. **Behat** — `moodle-plugin-ci behat --profile chrome`

### Compilacion de JavaScript

Los archivos fuente de JavaScript estan en `amd/src/`. Moodle requiere versiones compiladas/minificadas en `amd/build/`.

- Herramienta de compilacion: **Grunt** (compilador JS estandar de Moodle)
- El archivo `chartjs-plugin-datalabels.js` esta excluido del linting via `IGNORE_NAMES` en la config de CI
- Despues de modificar cualquier JS en `amd/src/`, se debe recompilar con Grunt antes de hacer commit

### Testing

- **Tests Behat** en `tests/behat/reports_management.feature`
- Los tests cubren flujos de habilitar/deshabilitar widgets de reportes de sitio, curso y usuario
- Los tests usan las definiciones de pasos Behat de Moodle con la etiqueta `@javascript` (requiere driver de navegador)
- Actualmente no existen archivos de tests PHPUnit en el repositorio

## Convenciones de Codigo

### PHP

- **Namespace:** `report_lmsace_reports` (todas las clases bajo `classes/`)
- **Cabecera de archivo:** Cada archivo PHP debe incluir la cabecera de licencia GPL v3 y `@package report_lmsace_reports`
- **Guarda de seguridad:** Cada archivo PHP comienza con `defined('MOODLE_INTERNAL') || die;` (o `die()`)
- **Estilo de codigo Moodle:** Sigue el ruleset PHPCS de Moodle (forzado por CI). Reglas clave:
  - Indentacion de 4 espacios
  - `snake_case` para funciones y variables
  - PHPDoc completo en todas las clases y metodos publicos
  - Sin etiqueta de cierre PHP inline
- **Manejo de parametros:** Usar `optional_param()` / `required_param()` con constantes de tipo `PARAM_*`
- **Verificacion de capacidades:** Siempre verificar capacidades antes de mostrar datos o ejecutar operaciones
- **SQL:** Usar la API `$DB` de Moodle con consultas parametrizadas. Nunca concatenar entrada de usuario en SQL.
- **Cadenas de idioma:** Todo texto visible al usuario va en `lang/en/report_lmsace_reports.php` y se accede via `get_string()`

### JavaScript

- Formato de modulos AMD (compatible con RequireJS)
- Fuente en `amd/src/`, salida compilada en `amd/build/`
- Punto de entrada principal: `amd/src/main.js` (cargado via `$PAGE->requires->js_call_amd()`)
- Usa Chart.js para visualizacion de datos

### Plantillas

- Plantillas Mustache en `templates/`
- Siguen las convenciones Mustache de Moodle
- Sin logica en plantillas — todos los datos se preparan en los renderables PHP

### CSS

- Un solo archivo `styles.css` en la raiz
- Moodle lo carga automaticamente para el plugin
- Usar la clase body `.lmsace-reports-body` para estilos con scope

## Patrones Clave para Asistentes de IA

### Agregar un Nuevo Widget

1. Crear la clase del widget en `classes/local/widgets/` extendiendo `widgets_info`
2. Agregar una constante en `lib.php` para el identificador del widget
3. Registrar el widget en la clase stack widget correspondiente
4. Agregar el checkbox de habilitar/deshabilitar en `settings.php`
5. Agregar cadenas de idioma en `lang/en/report_lmsace_reports.php`
6. Crear una plantilla Mustache en `templates/` si es necesario
7. Agregar metodos de datos AJAX en `externallib.php` y registrar en `db/services.php`
8. Incrementar la version en `version.php`

### Agregar una Nueva Tabla

1. Crear la clase de tabla en `classes/local/table/` extendiendo `dynamic_table` de Moodle
2. Crear una clase `*_filterset` correspondiente para filtrado dinamico
3. Conectarla al widget o servicio externo relevante

### Errores Comunes

- **Ubicaciones duales de clases:** Las clases de widgets y tablas existen tanto en `classes/local/` como en `classes/` (sin `local`). Las versiones en `classes/local/` son las canonicas. Los directorios `classes/widgets/` y `classes/table/` en la raiz contienen copias legacy.
- **Incremento de version:** Cualquier cambio en archivos de `db/` (access, services, caches) requiere incrementar `$plugin->version` en `version.php`
- **Recompilacion de JS requerida:** Despues de editar `amd/src/*.js`, las versiones minificadas en `amd/build/` deben ser regeneradas
- **Sin tablas de BD propias:** Este plugin nunca crea sus propias tablas. Todos los datos provienen de tablas estandar de Moodle.
- **Exclusion de usuario admin:** Los reportes de usuario excluyen explicitamente a los admins (verificacion `is_siteadmin()` en `index.php`)
- **Invalidacion de cache:** Las definiciones de widgets se cachean a nivel de aplicacion. Usar `cache.php` para purgar.

### Puntos de Entrada de Navegacion

- **Admin del sitio:** `Administracion del sitio > Reportes > LMSACE Reports`
- **Reportes de curso:** Agregado via `report_lmsace_reports_extend_navigation_course()` en `lib.php`
- **Perfil de usuario:** Agregado via `report_lmsace_reports_myprofile_navigation()` en `lib.php`
- **URL principal:** `/report/lmsace_reports/index.php?report={sitereport|coursereport|userreport|teacherreport|evaluationreport}`

### Dependencias de Terceros

- **Plugin Chart.js datalabels** v2.2.0 (MIT) — declarado en `thirdpartylibs.xml`, ubicado en `amd/src/chartjs-plugin-datalabels.js`
- Sin dependencias de Composer
- Sin `package.json` de npm (usa la configuracion Grunt de Moodle)
