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
            'security_policies',
            function (Blueprint $table): void {
                $table->foreignId('team_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('teams')
                    ->nullOnDelete();
            }
        );

        $teamIds =
            DB::table('teams')
                ->orderBy('id')
                ->pluck('id');

        if ($teamIds->count() === 1) {
            DB::table('security_policies')
                ->whereNull('team_id')
                ->update([
                    'team_id' => $teamIds->first(),
                ]);
        }

        Schema::table(
            'security_policies',
            function (Blueprint $table): void {
                $table->dropUnique([
                    'code',
                ]);

                $table->unique([
                    'team_id',
                    'code',
                ]);

                $table->index([
                    'team_id',
                    'is_active',
                ]);

                $table->index([
                    'team_id',
                    'severity',
                ]);
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'security_policies',
            function (Blueprint $table): void {
                $table->dropUnique([
                    'team_id',
                    'code',
                ]);

                $table->dropIndex([
                    'team_id',
                    'is_active',
                ]);

                $table->dropIndex([
                    'team_id',
                    'severity',
                ]);

                $table->dropConstrainedForeignId(
                    'team_id'
                );

                $table->unique(
                    'code'
                );
            }
        );
    }
};
