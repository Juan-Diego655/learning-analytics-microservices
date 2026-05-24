<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('raw_events', function (Blueprint $table) {
            $table->uuid('event_id')->primary();
            $table->timestampTz('occurred_at')->index();
            $table->enum('lms_source', ['moodle', 'canvas', 'openedx'])->index();
            $table->uuid('institution_id')->index();
            $table->string('student_external_id');
            $table->string('course_external_id');
            $table->string('event_type', 64)->index();
            $table->jsonb('raw_payload');
            $table->jsonb('canonical_payload');
            $table->boolean('published_to_kafka')->default(false)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('raw_events');
    }
};
