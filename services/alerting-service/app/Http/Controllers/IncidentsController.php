<?php

namespace App\Http\Controllers;

use App\Models\AlertIncident;
use App\Models\AlertRule;
use App\Services\Alerting\IncidentManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IncidentsController extends Controller
{
    public function __construct(private IncidentManager $manager) {}

    /**
     * GET /api/incidents
     */
    public function index(Request $request): JsonResponse
    {
        $query = AlertIncident::query()->with('rule')->orderByDesc('triggered_at');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($severity = $request->query('severity')) {
            $query->where('severity', $severity);
        }
        if ($rule = $request->query('rule_code')) {
            $query->where('rule_code', $rule);
        }

        $limit = min((int) $request->query('limit', 30), 100);

        return response()->json([
            'total' => AlertIncident::count(),
            'by_status' => AlertIncident::selectRaw('status, count(*) as total')
                ->groupBy('status')->pluck('total', 'status'),
            'by_severity' => AlertIncident::selectRaw('severity, count(*) as total')
                ->groupBy('severity')->pluck('total', 'severity'),
            'by_rule' => AlertIncident::selectRaw('rule_code, count(*) as total')
                ->groupBy('rule_code')->pluck('total', 'rule_code'),
            'incidents' => $query->limit($limit)->get(),
        ]);
    }

    /**
     * GET /api/incidents/{id}
     */
    public function show(string $id): JsonResponse
    {
        $incident = AlertIncident::with(['rule', 'events'])->findOrFail($id);
        return response()->json($incident);
    }

    /**
     * POST /api/incidents/{id}/acknowledge
     */
    public function acknowledge(string $id): JsonResponse
    {
        $incident = AlertIncident::findOrFail($id);
        $this->manager->acknowledge($incident);
        return response()->json($incident->fresh());
    }

    /**
     * POST /api/incidents/{id}/resolve
     */
    public function resolve(string $id): JsonResponse
    {
        $incident = AlertIncident::findOrFail($id);
        $this->manager->resolve($incident);
        return response()->json($incident->fresh());
    }

    /**
     * GET /api/rules
     */
    public function rules(): JsonResponse
    {
        return response()->json([
            'rules' => AlertRule::orderBy('rule_code')->get(),
        ]);
    }
}
