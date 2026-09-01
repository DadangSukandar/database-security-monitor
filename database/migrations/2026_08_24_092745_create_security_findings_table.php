<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_findings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('database_connection_id')
                ->nullable()
                ->constrained('database_connections')
                ->nullOnDelete();

            $table->string('database_name')->nullable();

            $table->string('finding_type');

            $table->string('category')->nullable();

            $table->enum('severity', [
                'CRITICAL',
                'HIGH',
                'MEDIUM',
                'LOW',
            ])->default('LOW');

            $table->string('title');

            $table->text('description')->nullable();

            $table->string('object_type')->nullable();

            $table->string('object_name')->nullable();

            $table->string('username')->nullable();

            $table->text('recommendation')->nullable();

            $table->enum('status', [
                'OPEN',
                'RESOLVED',
                'IGNORED',
            ])->default('OPEN');

            $table->timestamp('detected_at')->nullable();

            $table->timestamp('resolved_at')->nullable();

            $table->timestamps();

            $table->index('severity');
            $table->index('status');
            $table->index('finding_type');
            $table->index('category');
            $table->index('database_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_findings');
    }
};
