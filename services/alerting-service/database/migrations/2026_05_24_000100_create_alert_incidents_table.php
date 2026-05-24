<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alert_incidents', function (Blueprint $table) {
            $table->uuid('incident_id')->primary();
            $table->string('rule_code', 16)->index();
            $table->foreign('rule_code')->references('rule_code')->on('alert_rules');

            $table->uuid('institution_id')->index();
            $table->string('student_external_id')->nullable()->index();
            $table->string('course_external_id')->nullable()->index();

            $table->enum('severity', ['info', 'warning', 'critical'])->index();
            $table->enum('status', ['triggered', 'notified', 'acknowledged', 'resolved'])
                ->default('triggered')->index();

            $table->jsonb('trigger_context');  // datos que detonaron la alerta
            $table->timestampTz('triggered_at')->useCurrent();
            $table->timestampTz('notified_at')->nullable();
            $table->timestampTz('acknowledged_at')->nullable();
            $table->timestampTz('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alert_incidents');
    }
};
