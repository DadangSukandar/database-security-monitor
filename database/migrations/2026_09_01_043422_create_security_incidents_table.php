<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('security_incidents', function (Blueprint $table) {
            $table->id();

            /*
             * Human-readable incident identifier.
             *
             * Example:
             * INC-20260901-0001
             */
            $table->string('incident_number', 50)->unique();

            /*
             * Source canonical security alert.
             *
             * One canonical alert may create at most one incident.
             */
            $table->foreignId('security_alert_id')
                ->unique()
                ->constrained('security_alerts')
                ->restrictOnDelete();

            /*
             * Incident information.
             */
            $table->string('title');

            $table->text('description')
                ->nullable();

            $table->string('severity', 20);

            /*
             * OPEN
             * ACKNOWLEDGED
             * INVESTIGATING
             * CONTAINED
             * RESOLVED
             * CLOSED
             */
            $table->string('status', 30)
                ->default('OPEN');

            /*
             * Incident ownership / PIC.
             */
            $table->foreignId('assigned_to_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('assigned_at')
                ->nullable();

            /*
             * User who escalated the alert into an incident.
             *
             * Nullable so audit evidence survives if the user
             * account is removed later.
             */
            $table->foreignId('created_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            /*
             * Lifecycle timestamps.
             */
            $table->timestamp('opened_at')
                ->nullable();

            $table->timestamp('acknowledged_at')
                ->nullable();

            $table->timestamp('investigation_started_at')
                ->nullable();

            $table->timestamp('contained_at')
                ->nullable();

            $table->timestamp('resolved_at')
                ->nullable();

            $table->timestamp('closed_at')
                ->nullable();

            /*
             * Final resolution information.
             */
            $table->text('resolution_note')
                ->nullable();

            $table->timestamps();

            /*
             * Common SOC dashboard/filter indexes.
             */
            $table->index([
                'status',
                'severity',
            ]);

            $table->index([
                'status',
                'assigned_to_user_id',
            ]);

            $table->index('opened_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('security_incidents');
    }
};
