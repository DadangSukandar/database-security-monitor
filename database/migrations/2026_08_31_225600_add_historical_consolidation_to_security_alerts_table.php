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
        Schema::table('security_alerts', function (Blueprint $table) {
            $table->foreignId('canonical_alert_id')
                ->nullable()
                ->after('last_assessment_id')
                ->constrained('security_alerts')
                ->restrictOnDelete();

            $table->timestamp('consolidated_at')
                ->nullable()
                ->after('canonical_alert_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('security_alerts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('canonical_alert_id');
            $table->dropColumn('consolidated_at');
        });
    }
};
