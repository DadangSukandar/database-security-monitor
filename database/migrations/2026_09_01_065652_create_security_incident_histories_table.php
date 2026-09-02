<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'security_incident_histories',
            function (Blueprint $table) {
                $table->id();

                $table->foreignId('security_incident_id')
                    ->constrained('security_incidents')
                    ->cascadeOnDelete();

                $table->string('action', 50);

                $table->string(
                    'old_status',
                    30
                )->nullable();

                $table->string(
                    'new_status',
                    30
                )->nullable();

                $table->text('notes')->nullable();

                $table->foreignId('user_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamps();

                $table->index([
                    'security_incident_id',
                    'created_at',
                ]);

                $table->index([
                    'security_incident_id',
                    'action',
                ]);
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'security_incident_histories'
        );
    }
};
