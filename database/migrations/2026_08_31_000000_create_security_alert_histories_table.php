<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_alert_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('security_alert_id')->constrained('security_alerts')->cascadeOnDelete();
            $table->string('action');
            $table->string('old_status', 20)->nullable();
            $table->string('new_status', 20);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();

            $table->index(['security_alert_id', 'created_at']);
            $table->index('new_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_alert_histories');
    }
};
