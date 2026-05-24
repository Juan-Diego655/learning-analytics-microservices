<?php

namespace App\Console\Commands;

use App\Models\AlertRule;
use App\Services\Alerting\Rules\R3LowEngagementEvaluator;
use Illuminate\Console\Command;

/**
 * EvaluateBatchRules
 * -----------------------------------------------------------------------------
 * Ejecuta las reglas en modo BATCH (R3). Diseñado para correr
 * periodicamente desde un schedule (cada N minutos) o manualmente:
 *
 *   php artisan alerts:evaluate-batch
 */
class EvaluateBatchRules extends Command
{
    protected $signature = 'alerts:evaluate-batch';
    protected $description = 'Evalua reglas BATCH (R3) consultando Telemetry y disparando incidentes';

    public function handle(R3LowEngagementEvaluator $r3): int
    {
        $this->info("Evaluando reglas BATCH...");
        $this->newLine();

        $batchRules = AlertRule::where('evaluation_mode', AlertRule::MODE_BATCH)
            ->where('is_active', true)
            ->get();

        if ($batchRules->isEmpty()) {
            $this->warn("No hay reglas batch activas.");
            return self::SUCCESS;
        }

        $totalTriggered = 0;

        foreach ($batchRules as $rule) {
            $this->line("→ Evaluando {$rule->rule_code}: {$rule->name}");
            $count = match ($rule->rule_code) {
                'R3' => $r3->evaluate($rule),
                default => 0,
            };
            $this->line("  incidentes disparados: $count");
            $totalTriggered += $count;
        }

        $this->newLine();
        $this->info("Total incidentes: $totalTriggered");

        return self::SUCCESS;
    }
}
