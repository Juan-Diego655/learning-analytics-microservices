<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events_processed', function (Blueprint $table) {
            $table->uuid('event_id')->primary();
            $table->timestampTz('occurred_at')->index();
            $table->string('lms_source', 16)->index();
            $table->uuid('institution_id')->index();
            $table->string('student_external_id');
            $table->string('course_external_id');
            $table->string('event_type', 64)->index();
            $table->timestampTz('processed_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events_processed');
    }
};
