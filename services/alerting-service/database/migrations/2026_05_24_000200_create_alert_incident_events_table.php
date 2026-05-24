<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alert_incident_events', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('incident_id')->index();
            $table->foreign('incident_id')->references('incident_id')->on('alert_incidents');

            $table->string('event_type', 32);  // triggered, notified, acknowledged, resolved
            $table->jsonb('payload')->nullable();
            $table->timestampTz('occurred_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alert_incident_events');
    }
};
