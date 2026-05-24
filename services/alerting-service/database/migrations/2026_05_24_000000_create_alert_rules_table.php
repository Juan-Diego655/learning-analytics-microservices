<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alert_rules', function (Blueprint $table) {
            $table->string('rule_code', 16)->primary();  // R1, R2, R3
            $table->string('name');
            $table->text('description');
            $table->enum('severity', ['info', 'warning', 'critical'])->default('warning');
            $table->enum('evaluation_mode', ['stream', 'batch'])->default('stream');
            $table->jsonb('config');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alert_rules');
    }
};
