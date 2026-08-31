<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sensitive_data_findings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('discovered_column_id')
                ->constrained('discovered_columns')
                ->cascadeOnDelete();

            $table->string('category', 50);
            $table->string('risk_level', 30);
            $table->string('rule_name', 100);
            $table->text('description')->nullable();

            $table->timestamps();

            $table->index('category');
            $table->index('risk_level');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sensitive_data_findings');
    }
};