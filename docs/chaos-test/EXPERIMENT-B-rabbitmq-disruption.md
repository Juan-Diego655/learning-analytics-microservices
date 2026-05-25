# Chaos Test B: RabbitMQ Disruption

**Fecha**: 2026-05-25
**Duración total**: 3 minutos
**Hipótesis**: Si RabbitMQ se cae, los microservicios que NO dependen del broker
siguen operativos. Los incidentes se persisten en BD aunque las notificaciones
no se envíen.

## Setup

- 5 microservicios Laravel + Kafka + RabbitMQ + Redis + 3 Postgres
- Sistema en régimen estable (39 notificaciones, 20 incidentes existentes)
- Cadena Saga validada previamente: `curl → Ingestion → Kafka → Alerting → RabbitMQ → Notification`

## Protocolo

### Fase 1 — Baseline (10:50:19)

| Métrica | Valor |
|---------|-------|
| Servicios healthy | 5/5 (rabbitmq, alerting, alerting-worker, notification, notification-worker) |
| Notification stats: total | 39 |
| Notification stats: sent | 38 |
| Notification stats: failed | 1 (SMTP timeout simulado previo) |
| Incidents en Alerting | 20 (16 notified, 3 resolved, 1 acknowledged) |
| Publish total RabbitMQ exchange 'alerts' | 0 (contador reseteado por restart previo) |

### Fase 2 — Disruption (10:52:02)

Comando: `docker compose stop rabbitmq`
Tiempo de detección de falla por el worker: **~1 segundo**

Output del worker:
[15:52:03] Conexion perdida: Broken pipe or closed connection
Reintentando en 5 segundos...

Estado de los demás servicios:
- la-alerting: HTTP 200
- la-telemetry: HTTP 200
- la-ingestion: HTTP 200
- la-notification (HTTP): HTTP 200

**Conclusión Fase 2**: ✅ Solo el componente que depende activamente de RabbitMQ
(notification-worker) detecta la falla. El resto del sistema queda intacto.

### Fase 3 — Operating Under Failure (10:52:24)

Acción: burst de 15 eventos POST → `la-ingestion` con student=999

Resultados:
- Eventos recibidos por Ingestion: 15 ✓
- Eventos publicados a Kafka: 15 ✓
- Eventos consumidos por Alerting Worker: 15 ✓
- Reglas disparadas: R1 (critical) + R2 (warning) ✓
- Incidentes persistidos en BD: 22 (de 20 → 22, **+2 nuevos**) ✓
- Notificaciones enviadas durante la falla: **0** ✗ (esperado)

Output del Alerting Worker durante la falla:
[15:52:27] 🚨 f2e6d233 · student=999 · rules=[R2]
[15:52:30] 🚨 4d9f0d5a · student=999 · rules=[R1]

Notification stats post-falla: 39 (sin cambio — confirma que NO se enviaron notificaciones)

**Conclusión Fase 3**: ✅ El pipeline `Ingestion → Kafka → Alerting → BD` funciona
con plena autonomía respecto a RabbitMQ. **Database per Service** y **bounded
contexts** materializados en práctica.

### Fase 4 — Recovery (10:52:42)

Comando: `docker compose start rabbitmq` (apagado total: ~40 segundos)

Progresión del worker durante el recovery (logs reales):
[15:52:03] Broken pipe or closed connection
[15:52:15] getaddrinfo for rabbitmq failed: Try again
[15:52:24] Name does not resolve
[15:52:32] Name does not resolve
[15:52:41] Name does not resolve
Esperando mensajes del exchange 'alerts'...  ← reconectado

Tiempo desde "rabbitmq start" hasta "consumer reconectado": **~30 segundos**
(broker arrancando + 3-4 ciclos de retry del worker).

Nuevo burst (student=555) post-recovery:
- Incidentes generados: 2 (R1 + R2)
- Mensajes publicados a RabbitMQ: 2
- Notificaciones enviadas: 6 (3 canales × 2 incidentes)

Output del worker post-recovery:
[15:53:15] ✉  2b235364 · R2 · sent=3 failed=0 total=3
[15:53:15] ✉  2da981d7 · R1 · sent=3 failed=0 total=3

**Conclusión Fase 4**: ✅ Reconnect automático sin intervención humana.
El sistema vuelve a régimen pleno funcional.

## Métricas finales

| Métrica | Antes | Durante falla | Después recovery |
|---------|-------|---------------|------------------|
| Incidents totales | 20 | 22 (+2) | 24 (+2 más) |
| Notification stats: total | 39 | 39 (sin cambio) | 45 (+6) |
| Mensajes publish a 'alerts' | 0 | 0 | 2 |

## Hallazgo crítico: pérdida de notificaciones del student=999

Los 2 incidentes generados durante la falla (student=999) **NO disparon
notificaciones cuando RabbitMQ volvió**. Los incidentes están en BD pero
nunca fueron notificados.

Causa raíz: el `RabbitMQPublisher` actual publica el mensaje **directamente
desde IncidentManager** después de la transacción de BD (publish-after-commit).
Si RabbitMQ está caído, el publish falla silenciosamente (try/catch isolated)
y el mensaje no se reintenta posteriormente.

**Mitigación arquitectónica para Fase 3 (ADR-05 propuesto)**:

Implementar **Transactional Outbox Pattern**:
1. Persistir el mensaje a publicar en una tabla `outbox_messages` dentro de la
   misma transacción que crea el `alert_incident`.
2. Crear un worker `outbox-publisher` que vacía esa tabla a RabbitMQ con
   retry policy exponencial.
3. Marcar como `published_at = now()` solo después de ACK del broker.

Esto garantiza:
- **At-least-once delivery** aunque RabbitMQ esté caído por horas.
- **Eventual consistency** entre el dominio (Alerting BD) y la integración
  (RabbitMQ broker).
- **Recovery automático** sin pérdida de mensajes.

## Conceptos arquitectónicos demostrados

| Concepto | Evidencia |
|----------|-----------|
| **Bulkhead Pattern** | Falla aislada a notification-worker; resto del sistema sigue |
| **Database per Service** | Alerting persistió incidents sin depender de Notification |
| **Bounded Contexts** | Frontend siguió mostrando incidents (lee de Alerting, no de Notification) |
| **Circuit Retry** | Worker no murió; reintenta cada 5s sin intervención |
| **Durable Queues** | Mensajes durable=true, queue durable=true (default RabbitMQ) |
| **Mensajería híbrida** | Kafka (volumen alto, replay) ⊥ RabbitMQ (derivados, ACK manual) |

## Conclusión

El sistema **demuestra resiliencia operacional** ante la pérdida del broker
de mensajería derivada (RabbitMQ). El **trade-off documentado** es que las
notificaciones generadas durante la falla se pierden, lo cual se mitigaría
con Outbox Pattern en Fase 3.

Para un MVP académico, este comportamiento es aceptable porque:
1. El **incident NO se pierde** (queda en BD del Alerting con `notified_at: null`).
2. Un operador humano puede consultar `/api/incidents?status=notified` y ver
   el incidente sin notificar.
3. Re-notificar manualmente sería trivial (endpoint dedicado a futuro).
