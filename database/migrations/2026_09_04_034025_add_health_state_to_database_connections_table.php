<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'database_connections',
            function (Blueprint $table): void {
                $table->string(
                    'health_status',
                    32
                )->default('UNKNOWN');

                $table->timestamp(
                    'last_health_checked_at'
                )->nullable();

                $table->timestamp(
                    'last_failed_at'
                )->nullable();

                $table->string(
                    'last_failure_type',
                    64
                )->nullable();

                $table->unsignedInteger(
                    'consecutive_failures'
                )->default(0);

                $table->timestamp(
                    'last_recovered_at'
                )->nullable();

                $table->index(
                    'health_status'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'database_connections',
            function (Blueprint $table): void {
                $table->dropIndex([
                    'health_status',
                ]);

                $table->dropColumn([
                    'health_status',
                    'last_health_checked_at',
                    'last_failed_at',
                    'last_failure_type',
                    'consecutive_failures',
                    'last_recovered_at',
                ]);
            }
        );
    }
};
