<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'security_findings',
            function (Blueprint $table): void {
                $table->foreignId('team_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('teams')
                    ->nullOnDelete();

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
         * Backfill dari DatabaseConnection.
         */
        DB::table('security_findings')
            ->whereNull('team_id')
            ->whereNotNull('database_connection_id')
            ->orderBy('id')
            ->chunkById(
                200,
                function ($findings): void {
                    foreach ($findings as $finding) {
                        $teamId = DB::table(
                            'database_connections'
                        )
                            ->where(
                                'id',
                                $finding->database_connection_id
                            )
                            ->value('team_id');

                        if ($teamId === null) {
                            continue;
                        }

                        DB::table('security_findings')
                            ->where(
                                'id',
                                $finding->id
                            )
                            ->update([
                                'team_id' => $teamId,
                            ]);
                    }
                }
            );

        /*
         * Legacy finding tanpa connection hanya aman
         * di-backfill otomatis jika sistem baru memiliki
         * tepat satu team.
         */
        $teamIds = DB::table('teams')
            ->orderBy('id')
            ->pluck('id');

        if ($teamIds->count() === 1) {
            DB::table('security_findings')
                ->whereNull('team_id')
                ->update([
                    'team_id' => $teamIds->first(),
                ]);
        }
    }

    public function down(): void
    {
        Schema::table(
            'security_findings',
            function (Blueprint $table): void {
                $table->dropIndex([
                    'team_id',
                    'status',
                ]);

                $table->dropIndex([
                    'team_id',
                    'severity',
                ]);

                $table->dropConstrainedForeignId(
                    'team_id'
                );
            }
        );
    }
};
