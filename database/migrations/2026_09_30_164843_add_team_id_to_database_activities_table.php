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
            'database_activities',
            function (Blueprint $table): void {
                $table->foreignId('team_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('teams')
                    ->nullOnDelete();

                $table->index([
                    'team_id',
                    'executed_at',
                ]);

                $table->index([
                    'team_id',
                    'action',
                ]);
            }
        );

        DB::table('database_activities')
            ->whereNull('team_id')
            ->whereNotNull('database_connection_id')
            ->orderBy('id')
            ->chunkById(
                200,
                function ($activities): void {
                    foreach ($activities as $activity) {
                        $teamId = DB::table(
                            'database_connections'
                        )
                            ->where(
                                'id',
                                $activity->database_connection_id
                            )
                            ->value('team_id');

                        if ($teamId === null) {
                            continue;
                        }

                        DB::table('database_activities')
                            ->where(
                                'id',
                                $activity->id
                            )
                            ->update([
                                'team_id' => $teamId,
                            ]);
                    }
                }
            );

        $teamIds = DB::table('teams')
            ->orderBy('id')
            ->pluck('id');

        if ($teamIds->count() === 1) {
            DB::table('database_activities')
                ->whereNull('team_id')
                ->update([
                    'team_id' => $teamIds->first(),
                ]);
        }
    }

    public function down(): void
    {
        Schema::table(
            'database_activities',
            function (Blueprint $table): void {
                $table->dropIndex([
                    'team_id',
                    'executed_at',
                ]);

                $table->dropIndex([
                    'team_id',
                    'action',
                ]);

                $table->dropConstrainedForeignId(
                    'team_id'
                );
            }
        );
    }
};
