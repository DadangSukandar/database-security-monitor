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
        if (! Schema::hasColumn('security_alerts', 'acknowledged_at')) {
            Schema::table('security_alerts', function (Blueprint $table): void {
                $table->timestamp('acknowledged_at')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        /**
         * Existing installations may already have this lifecycle column,
         * so rollback intentionally preserves it and its data.
         */
    }
};
