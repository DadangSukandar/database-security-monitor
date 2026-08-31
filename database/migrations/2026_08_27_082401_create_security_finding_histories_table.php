<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_finding_histories', function (Blueprint $table) {

            $table->id();

            $table->foreignId('security_finding_id')
                ->constrained('security_findings')
                ->cascadeOnDelete();

            $table->string('action')->nullable();

            $table->string('old_status')->nullable();

            $table->string('new_status')->nullable();

            $table->text('notes')->nullable();

            $table->unsignedBigInteger('user_id')->nullable();

            $table->timestamps();

            $table->index('security_finding_id');

            $table->index('new_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'security_finding_histories'
        );
    }
};