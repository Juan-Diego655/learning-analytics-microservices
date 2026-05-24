<?php

namespace App\Services\Ingestion\Adapters;

/**
 * Contrato del Anti-Corruption Layer.
 * Cada adaptador traduce el modelo extranjero de un LMS específico
 * al esquema canónico interno del sistema.
 */
interface LmsAdapterInterface
{
    /**
     * Identificador del LMS soportado (moodle, canvas, openedx).
     */
    public function source(): string;

    /**
     * Traduce el payload nativo del LMS al esquema canónico interno.
     *
     * @param array $rawPayload  Estructura nativa tal como llega del LMS.
     * @param string|null $institutionId  UUID de la IES que envía (obligatorio).
     * @return array  Estructura canónica con campos comunes.
     */
    public function toCanonical(array $rawPayload, ?string $institutionId): array;
}