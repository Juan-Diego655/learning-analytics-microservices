# Learning Analytics Microservices

Implementación de microservicios para un sistema nacional de Learning Analytics. Fase 2 del parcial de Arquitectura de Software (UNAB, quinto semestre).

> **Estado:** 🚧 En construcción · día 1 de 7 · entrega prevista 27 de mayo de 2026.

---

## Información académica

| | |
|---|---|
| **Autor** | Juan Diego Niño Solano |
| **Curso** | Arquitectura de Software |
| **Semestre** | Quinto |
| **Docente** | Mg. Fabian Suárez / Mg. Javier Pinzón |
| **Universidad** | Universidad Autónoma de Bucaramanga (UNAB) |
| **Programa** | Ingeniería de Sistemas |
| **Fase** | 2 de 2 — Implementación |

---

## Descripción

Este proyecto implementa una arquitectura de microservicios para un sistema nacional de Learning Analytics, articulando fuentes heterogéneas (LMS, repositorios de código, evaluadores) bajo un modelo unificado de ingesta, procesamiento, alerta y notificación. Es la continuación práctica del diseño conceptual entregado en la Fase 1.

El sistema demuestra patrones de arquitectura de microservicios sobre Docker Compose: API Gateway, Anti-Corruption Layer, Database per Service, Asynchronous Messaging, Saga + State Machine, y comunicación entre servicios mediante RabbitMQ y un topic Kafka representativo.

> *Documentación detallada de arquitectura, decisiones técnicas y validación: ver `/docs`.*

---

## Microservicios

| # | Servicio | Stack | Puerto | Responsabilidad |
|---|---|---|---|---|
| 1 | API Gateway | Laravel + Sanctum | 8000 | Entry point único, validación JWT, routing |
| 2 | Identity & Access | Laravel | 8001 | Gestión de usuarios y roles, emisión de JWT |
| 3 | Event Ingestion | Laravel | 8002 | Anti-Corruption Layer, adaptadores LMS |
| 4 | Telemetry Processing | Laravel + Horizon | 8003 | Stream processing, agregaciones por ventana |
| 5 | Alerting Service | Laravel | 8004 | Reglas, máquina de estados, incidentes |
| 6 | Notification Dispatcher | Laravel + Horizon | 8005 | Consumer de comandos, envío multi-canal |
| + | Simulador LMS | Python | — | Cliente externo que genera eventos sintéticos |

*Infraestructura compartida:* PostgreSQL (×6 instancias o esquemas), RabbitMQ, Kafka, Redis.

---

## Requisitos previos

- Docker Engine **24+**
- Docker Compose **v2+**
- Git
- (Opcional para pruebas) Postman / Insomnia / cURL

---

## Ejecución

> *Las instrucciones definitivas se completarán al finalizar el día 1 de implementación.*

```bash
# Clonar
git clone https://github.com/<tu-usuario>/learning-analytics-microservices.git
cd learning-analytics-microservices

# Variables de entorno
cp .env.example .env

# Levantar todos los servicios
docker compose up --build

# Verificar health
curl http://localhost:8000/health
```

---

## Pruebas

> *Postman collection y ejemplos curl se publican al final del día 6.*

- Colección Postman: `postman/learning-analytics.postman_collection.json`
- Ejemplos curl: `docs/pruebas-curl.md`

---

## Documentación

| Documento | Ruta |
|---|---|
| Diseño Fase 1 (PDF) | `docs/fase1-diseno.pdf` *(se agrega al final)* |
| Documento técnico Fase 2 | `docs/fase2-tecnico.pdf` *(se agrega al final)* |
| Diagrama de arquitectura final | `docs/arquitectura-final.png` |
| Comparación Fase 1 vs Fase 2 | `docs/comparacion-fase1-fase2.md` |
| Test del Mono — evidencias | `docs/chaos-test.md` |

---

## Estructura del repositorio

```
learning-analytics-microservices/
├── api-gateway/              # [Laravel] Entry point + JWT
├── identity-access/          # [Laravel] Usuarios y roles
├── event-ingestion/          # [Laravel] ACL + adaptadores LMS
├── telemetry-processing/     # [Laravel + Horizon] Stream processing
├── alerting-service/         # [Laravel] Saga + state machine
├── notification-dispatcher/  # [Laravel + Horizon] Notificaciones
├── simulator-lms/            # [Python] Cliente sintético
├── docs/                     # Documentación técnica y diagramas
├── postman/                  # Colecciones de pruebas
├── docker-compose.yml
├── .env.example
└── README.md
```

---

## Patrones implementados

> *Detalle de implementación y limitaciones: ver documento técnico (`/docs/fase2-tecnico.pdf`).*

- ✅ API Gateway
- ✅ Anti-Corruption Layer
- ✅ Asynchronous Messaging (RabbitMQ + Kafka)
- ✅ Database per Service
- ✅ Health Check
- ✅ Chained Microservice / Event-driven flow
- ✅ Saga + State Machine
- ✅ JWT / SSO simplificado

---

## Licencia

Sin licencia explícita — trabajo académico individual. Todos los derechos reservados al autor.
