<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_alerts', function (Blueprint $table) {

            $table->id();

            $table->foreignId('vulnerability_assessment_id')
                ->nullable()
                ->constrained('vulnerability_assessments')
                ->nullOnDelete();

            $table->foreignId('vulnerability_finding_id')
                ->nullable()
                ->constrained('vulnerability_findings')
                ->nullOnDelete();

            $table->string('severity', 20);

            $table->string('title');

            $table->text('message')
                ->nullable();

            $table->string('database_name')
                ->nullable();

            $table->string('status', 20)
                ->default('OPEN');

            $table->timestamp('acknowledged_at')
                ->nullable();

            $table->timestamp('resolved_at')
                ->nullable();

            $table->timestamps();

            $table->index([
                'status',
                'severity',
            ]);

            /*
             * Mencegah finding yang sama membuat
             * alert duplikat.
             */
            $table->unique(
                'vulnerability_finding_id'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'security_alerts'
        );
    }
};
