# =============================================================================
# Simulador LMS · Python container
# =============================================================================
FROM python:3.12-slim

WORKDIR /app

# Dependencias
COPY services/simulator-lms/requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

# Script
COPY services/simulator-lms/simulator.py .

# El simulador queda en background, espera al usuario para iniciarlo
# (lo arrancamos con `docker compose up simulator-lms`)
CMD ["python", "-u", "simulator.py"]