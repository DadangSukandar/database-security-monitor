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
            $table->foreignId('assigned_to_user_id')
                ->nullable()
                ->after('sla_started_at')
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('assigned_at')
                ->nullable()
                ->after('assigned_to_user_id');

            $table->index([
                'status',
                'assigned_to_user_id',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('security_alerts', function (Blueprint $table) {
            $table->dropIndex([
                'status',
                'assigned_to_user_id',
            ]);

            $table->dropConstrainedForeignId(
                'assigned_to_user_id'
            );

            $table->dropColumn('assigned_at');
        });
    }
};
