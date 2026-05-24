"""
Simulador LMS · Learning Analytics Microservices
================================================
Genera eventos sinteticos de actividad estudiantil simulando 3 LMS
distintos (Moodle, Canvas, Open edX) y los envia al microservicio
de Event Ingestion. Permite demostrar end-to-end el Anti-Corruption
Layer y el flujo: simulador -> ingestion -> Postgres + Kafka.

Variables de entorno:
  SIMULATOR_INTERVAL_SECONDS  Segundos entre eventos (default: 2)
  SIMULATOR_INSTITUTION_ID    UUID de la IES emisora (default: fijo)
  INGESTION_BASE_URL          URL base del servicio (default: http://la-ingestion)
"""

import os
import random
import sys
import time
from datetime import datetime, timezone

import requests

# ============================================================================
# Configuracion
# ============================================================================
INGESTION_URL = os.getenv("INGESTION_BASE_URL", "http://la-ingestion")
INSTITUTION_ID = os.getenv(
    "SIMULATOR_INSTITUTION_ID",
    "2785a692-1ccc-44e3-bb3b-839018538a01",
)
INTERVAL = float(os.getenv("SIMULATOR_INTERVAL_SECONDS", "2"))

# Modo burst (envia N eventos lo mas rapido posible y termina).
# Util para Test del Mono o validacion de carga.
BURST_MODE = "--burst" in sys.argv
BURST_COUNT = 100

# ============================================================================
# Datos sinteticos
# ============================================================================
STUDENTS = [101, 102, 103, 104, 105]  # IDs sinteticos
COURSES = ["CS101", "MAT201", "FIS301"]


# ============================================================================
# Generadores por LMS (formato NATIVO de cada uno)
# ============================================================================
def generate_moodle_event() -> dict:
    """Formato Moodle: eventname tipo \\core\\event\\xxx, timecreated unix"""
    event_names = [
        r"\core\event\course_viewed",
        r"\core\event\assignment_submitted",
        r"\core\event\quiz_attempt_started",
        r"\core\event\user_loggedin",
        r"\core\event\forum_discussion_created",
    ]
    return {
        "eventname": random.choice(event_names),
        "userid": random.choice(STUDENTS),
        "courseid": random.randint(1, 50),
        "timecreated": int(time.time()),
        "contextid": random.randint(100, 999),
    }


def generate_canvas_event() -> dict:
    """Formato Canvas: event simple, user/context anidados, timestamp ISO"""
    events = [
        "logged_in",
        "assignment_submitted",
        "quiz_submitted",
        "page_viewed",
        "discussion_topic_created",
    ]
    return {
        "event": random.choice(events),
        "user": {
            "id": f"u-{random.choice(STUDENTS)}",
            "name": f"Student-{random.randint(1, 100)}",
        },
        "context": {
            "course_id": random.choice(COURSES),
            "course_name": "Curso demo",
        },
        "timestamp": datetime.now(timezone.utc).isoformat(),
    }


def generate_openedx_event() -> dict:
    """Formato Open edX: event_type con namespace, context anidado, time ISO"""
    event_types = [
        "edx.course.enrollment.activated",
        "edx.video.played",
        "edx.problem_check",
        "edx.forum.thread.created",
        "edx.page_view",
    ]
    return {
        "event_type": random.choice(event_types),
        "event_source": "server",
        "context": {
            "user_id": random.choice(STUDENTS),
            "course_id": f"course-v1:UNAB+{random.choice(COURSES)}+2026",
            "org_id": "UNAB",
        },
        "time": datetime.now(timezone.utc).isoformat(),
        "session": f"sess-{random.randint(10000, 99999)}",
    }


LMS_GENERATORS = {
    "moodle": generate_moodle_event,
    "canvas": generate_canvas_event,
    "openedx": generate_openedx_event,
}


# ============================================================================
# Envio al microservicio
# ============================================================================
def send_event(lms: str, payload: dict) -> dict | None:
    """POST al endpoint /api/events/{lms} de la-ingestion."""
    url = f"{INGESTION_URL}/api/events/{lms}"
    headers = {
        "Content-Type": "application/json",
        "Accept": "application/json",
        "X-Institution-Id": INSTITUTION_ID,
    }
    try:
        response = requests.post(url, json=payload, headers=headers, timeout=5)
        return {
            "status_code": response.status_code,
            "response": response.json() if response.status_code < 500 else None,
        }
    except requests.RequestException as e:
        return {"status_code": 0, "error": str(e)}


# ============================================================================
# Loop principal
# ============================================================================
def main():
    print("=" * 70)
    print("  Simulador LMS · Learning Analytics")
    print("=" * 70)
    print(f"  Ingestion URL    : {INGESTION_URL}")
    print(f"  Institution ID   : {INSTITUTION_ID}")
    print(f"  Mode             : {'BURST (' + str(BURST_COUNT) + ' events)' if BURST_MODE else f'continuous (every {INTERVAL}s)'}")
    print("=" * 70)
    print()

    sent = 0
    failed = 0

    iteration = 0
    while True:
        iteration += 1
        # Rotar entre los 3 LMS
        lms = list(LMS_GENERATORS.keys())[iteration % 3]
        payload = LMS_GENERATORS[lms]()
        result = send_event(lms, payload)

        status = result.get("status_code", 0)
        if status == 202:
            sent += 1
            event_id = result["response"].get("event_id", "?")[:8]
            event_type = result["response"].get("event_type", "?")
            published = "✓" if result["response"].get("published_to_kafka") else "✗"
            print(f"[{iteration:04d}] {lms:8} → {event_type:25} | id={event_id}... | kafka={published}")
        else:
            failed += 1
            err = result.get("error") or result.get("response", {}).get("message", "?")
            print(f"[{iteration:04d}] {lms:8} → ERROR ({status}): {err}")

        # En burst mode terminar tras N eventos
        if BURST_MODE and iteration >= BURST_COUNT:
            break

        time.sleep(INTERVAL if not BURST_MODE else 0.05)

    print()
    print("=" * 70)
    print(f"  Resultado: {sent} OK · {failed} fallidos · {sent + failed} total")
    print("=" * 70)


if __name__ == "__main__":
    try:
        main()
    except KeyboardInterrupt:
        print("\n\nSimulador detenido por el usuario.")
        sys.exit(0)