<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('security_alerts', function (Blueprint $table) {
            $table->index(
                'canonical_alert_id',
                'security_alerts_canonical_alert_id_index'
            );

            $table->index(
                ['canonical_alert_id', 'alert_type', 'detected_at'],
                'security_alerts_canonical_type_detected_index'
            );

            $table->index(
                ['canonical_alert_id', 'database_name'],
                'security_alerts_canonical_database_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('security_alerts', function (Blueprint $table) {
            $table->dropIndex(
                'security_alerts_canonical_database_index'
            );

            $table->dropIndex(
                'security_alerts_canonical_type_detected_index'
            );

            $table->dropIndex(
                'security_alerts_canonical_alert_id_index'
            );
        });
    }
};
