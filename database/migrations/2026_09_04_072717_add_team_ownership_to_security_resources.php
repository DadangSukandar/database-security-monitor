<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * =========================================================
         * DATABASE CONNECTIONS
         * =========================================================
         */
        Schema::table(
            'database_connections',
            function (Blueprint $table): void {
                $table->foreignId('team_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('teams');

                $table->index([
                    'team_id',
                    'is_active',
                ]);
            }
        );

        /*
         * =========================================================
         * SECURITY ALERTS
         * =========================================================
         */
        Schema::table(
            'security_alerts',
            function (Blueprint $table): void {
                $table->foreignId('team_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('teams');

                $table->index([
                    'team_id',
                    'status',
                ]);

                $table->index([
                    'team_id',
                    'severity',
                ]);
            }
        );

        /*
         * =========================================================
         * SECURITY INCIDENTS
         * =========================================================
         */
        Schema::table(
            'security_incidents',
            function (Blueprint $table): void {
                $table->foreignId('team_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('teams');

                $table->index([
                    'team_id',
                    'status',
                ]);

                $table->index([
                    'team_id',
                    'severity',
                ]);
            }
        );

        /*
         * =========================================================
         * SAFE EXISTING-DATA BACKFILL
         * =========================================================
         *
         * Existing installations created before tenant ownership
         * did not have enough metadata to infer ownership.
         *
         * Automatic backfill is safe only when exactly one team
         * exists.
         */
        $teamIds = DB::table('teams')
            ->orderBy('id')
            ->pluck('id');

        if ($teamIds->count() > 1) {
            throw new RuntimeException(
                'Cannot automatically backfill tenant ownership: '.
                'more than one team exists.'
            );
        }

        if ($teamIds->count() === 1) {
            $teamId = (int) $teamIds->first();

            DB::table('database_connections')
                ->whereNull('team_id')
                ->update([
                    'team_id' => $teamId,
                ]);

            DB::table('security_alerts')
                ->whereNull('team_id')
                ->update([
                    'team_id' => $teamId,
                ]);

            DB::table('security_incidents')
                ->whereNull('team_id')
                ->update([
                    'team_id' => $teamId,
                ]);
        }
    }

    public function down(): void
    {
        Schema::table(
            'security_incidents',
            function (Blueprint $table): void {
                $table->dropIndex([
                    'team_id',
                    'severity',
                ]);

                $table->dropIndex([
                    'team_id',
                    'status',
                ]);

                $table->dropConstrainedForeignId('team_id');
            }
        );

        Schema::table(
            'security_alerts',
            function (Blueprint $table): void {
                $table->dropIndex([
                    'team_id',
                    'severity',
                ]);

                $table->dropIndex([
                    'team_id',
                    'status',
                ]);

                $table->dropConstrainedForeignId('team_id');
            }
        );

        Schema::table(
            'database_connections',
            function (Blueprint $table): void {
                $table->dropIndex([
                    'team_id',
                    'is_active',
                ]);

                $table->dropConstrainedForeignId('team_id');
            }
        );
    }
};
