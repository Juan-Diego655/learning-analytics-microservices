<?php

namespace Database\Seeders;

use App\Models\AlertRule;
use Illuminate\Database\Seeder;

class AlertRuleSeeder extends Seeder
{
    public function run(): void
    {
        AlertRule::updateOrCreate(
            ['rule_code' => 'R1'],
            [
                'name' => 'Alta frecuencia anormal de eventos',
                'description' => 'Detecta cuando un estudiante genera 10 o más eventos en menos de 30 segundos. '
                    . 'Puede indicar uso de bot, problema de cliente LMS o comportamiento erratico.',
                'severity' => AlertRule::SEVERITY_CRITICAL,
                'evaluation_mode' => AlertRule::MODE_STREAM,
                'config' => [
                    'threshold' => (int) env('RULE_R1_THRESHOLD', 10),
                    'window_seconds' => (int) env('RULE_R1_WINDOW_SECONDS', 30),
                ],
                'is_active' => true,
            ]
        );

        AlertRule::updateOrCreate(
            ['rule_code' => 'R2'],
            [
                'name' => 'Patron de abandono de tareas',
                'description' => 'Detecta cuando un estudiante registra 3+ eventos de actividad '
                    . '(forum_post, quiz_attempted, course_viewed) en un curso sin entregar '
                    . 'ningun assignment_submitted en una ventana de 10 minutos. '
                    . 'Indica riesgo de no entrega.',
                'severity' => AlertRule::SEVERITY_WARNING,
                'evaluation_mode' => AlertRule::MODE_STREAM,
                'config' => [
                    'threshold' => (int) env('RULE_R2_THRESHOLD', 3),
                    'window_seconds' => (int) env('RULE_R2_WINDOW_SECONDS', 600),
                    'tracked_events' => ['forum_post', 'quiz_attempted', 'course_viewed'],
                    'expected_event' => 'assignment_submitted',
                ],
                'is_active' => true,
            ]
        );

        AlertRule::updateOrCreate(
            ['rule_code' => 'R3'],
            [
                'name' => 'Engagement bajo en curso activo',
                'description' => 'Detecta estudiantes con menos de 2 eventos registrados '
                    . 'en una ventana de 5 minutos dentro de un curso que tiene actividad '
                    . 'general. Indica posible desconexion del curso.',
                'severity' => AlertRule::SEVERITY_WARNING,
                'evaluation_mode' => AlertRule::MODE_BATCH,
                'config' => [
                    'min_events' => (int) env('RULE_R3_MIN_EVENTS', 2),
                    'window_seconds' => (int) env('RULE_R3_WINDOW_SECONDS', 300),
                ],
                'is_active' => true,
            ]
        );
    }
}
