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
            $table->timestamp('sla_started_at')->nullable()->after('detected_at');
            $table->index(['status', 'sla_started_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('security_alerts', function (Blueprint $table) {
            $table->dropIndex(['status', 'sla_started_at']);
            $table->dropColumn('sla_started_at');
        });
    }
};
