<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metrics_window_5min', function (Blueprint $table) {
            $table->id();
            $table->timestampTz('window_start')->index();
            $table->timestampTz('window_end');
            $table->uuid('institution_id')->index();
            $table->string('course_external_id')->index();
            $table->string('event_type', 64)->index();
            $table->unsignedInteger('event_count')->default(0);
            $table->unsignedInteger('unique_students')->default(0);
            $table->jsonb('student_ids')->nullable();  // para calcular unique
            $table->timestamps();

            // Una sola fila por combinación (window_start, institution, course, event_type)
            $table->unique(
                ['window_start', 'institution_id', 'course_external_id', 'event_type'],
                'metrics_window_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metrics_window_5min');
    }
};
