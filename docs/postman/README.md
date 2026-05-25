# Postman Collection · Learning Analytics

Colección Postman completa para ejercitar la API REST distribuida del sistema.

## Contenido

- `learning-analytics.postman_collection.json` — ~30 requests organizados en 6 carpetas
- `learning-analytics.postman_environment.json` — variables del entorno local

## Cómo usar

### 1. Importar en Postman

1. Abrir Postman → **Import** (botón arriba a la izquierda)
2. Seleccionar AMBOS archivos JSON (`collection` + `environment`)
3. Click en **Import**

### 2. Activar el environment

En la esquina superior derecha de Postman, seleccionar **Learning Analytics - Local** del dropdown de environments.

### 3. Validar que los servicios estén corriendo

Ejecutar las requests de `📥 Event Ingestion → Health` en adelante.
Todas deben devolver `{"status":"ok",...}`.

### 4. Login automático

En `🔑 Auth (Identity Service) → Login (admin)`, presionar **Send**.

El test script extrae el JWT del response y lo guarda automáticamente en la variable `JWT_TOKEN`. A partir de aquí, todas las requests autenticadas usan ese token.

## Organización por carpetas

| Carpeta | Servicio | Endpoints |
|---------|----------|-----------|
| 🔑 Auth | Identity (`:8000` vía Gateway) | login, logout, me |
| 📥 Event Ingestion | `:8002` | moodle/canvas/openedx events, recent |
| 📊 Telemetry | `:8003` | summary, windows, by-lms |
| 🚨 Alerting | `:8004` | rules, incidents (CRUD + acknowledge/resolve) |
| 🔔 Notification | `:8005` | recent, stats, by-incident |
| 🧪 Demo Flows | varios | secuencias completas para sustentación |

## Casos de uso pre-armados (Demo Flows)

### Flujo A — Disparar incidente y validar cadena Saga
1. `Flujo A`: ejecutar ~15 veces seguidas (F5 rápido al Send)
2. Esperar 10 segundos
3. `Flujo A.2`: validar incidente generado en Alerting
4. `Flujo A.3`: validar 3 notificaciones generadas (email, slack, log)

Esto ejercita los 6 microservicios en una sola secuencia.

### Flujo B — Gestión de incidentes
1. `🚨 Alerting → List all incidents` (auto-pobla `LAST_INCIDENT_ID`)
2. `Flujo B.1`: acknowledge
3. `Flujo B.2`: resolve

### Flujo C — Health check inicial
Ejecutar para mostrar al inicio de la sustentación que todo el sistema está operativo.

## Variables del environment

| Variable | Auto-poblada | Descripción |
|----------|--------------|-------------|
| `BASE_*` | No (fija) | URLs base de los 6 servicios |
| `INSTITUTION_ID` | No (fija) | Tenant demo (multi-tenancy) |
| `JWT_TOKEN` | Sí (al login) | Token de sesión |
| `USER_ID` | Sí (al login) | ID del usuario autenticado |
| `LAST_INCIDENT_ID` | Sí (al listar incidents) | Último incidente para acknowledge/resolve |

## Credenciales seed disponibles

| Email | Password | Rol |
|-------|----------|-----|
| `admin@learning.test` | `password` | admin |
| `docente@learning.test` | `password` | docente |
| `estudiante@learning.test` | `password` | estudiante |
