<?php

namespace App\Services\Alerting;

use App\Models\AlertRule;
use App\Services\Alerting\Rules\R1HighFrequencyEvaluator;
use App\Services\Alerting\Rules\R2AbandonPatternEvaluator;
use Illuminate\Support\Facades\Log;

/**
 * RuleDispatcher
 * -----------------------------------------------------------------------------
 * Punto unico de entrada para evaluar reglas STREAM sobre un evento entrante.
 * Solo trabaja con reglas de modo 'stream' (R1 y R2).
 * R3 (batch) se invoca desde el comando 'alerts:evaluate-batch' (schedule).
 *
 * Patron: Strategy + Chain of Responsibility limitada.
 */
class RuleDispatcher
{
    public function __construct(
        private R1HighFrequencyEvaluator $r1,
        private R2AbandonPatternEvaluator $r2,
    ) {}

    /**
     * Recorre todas las reglas STREAM activas y las evalua contra un evento.
     */
    public function dispatchStream(array $event): array
    {
        $rules = AlertRule::where('evaluation_mode', AlertRule::MODE_STREAM)
            ->where('is_active', true)
            ->get();

        $triggered = [];

        foreach ($rules as $rule) {
            try {
                $fired = match ($rule->rule_code) {
                    'R1' => $this->r1->evaluate($event, $rule),
                    'R2' => $this->r2->evaluate($event, $rule),
                    default => false,
                };
                if ($fired) {
                    $triggered[] = $rule->rule_code;
                }
            } catch (\Throwable $e) {
                Log::error("Rule evaluation failed", [
                    'rule_code' => $rule->rule_code,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $triggered;
    }
}
