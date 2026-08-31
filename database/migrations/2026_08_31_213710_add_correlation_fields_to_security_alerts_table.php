<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('security_alerts', function (Blueprint $table) {
            $table->string('fingerprint', 64)
                ->nullable()
                ->after('alert_type');

            $table->unsignedInteger('occurrence_count')
                ->default(1)
                ->after('status');

            $table->timestamp('first_seen_at')
                ->nullable()
                ->after('occurrence_count');

            $table->timestamp('last_seen_at')
                ->nullable()
                ->after('first_seen_at');

            $table->unsignedBigInteger('last_assessment_id')
                ->nullable()
                ->after('last_seen_at');

            $table->index('fingerprint');

            $table->index([
                'status',
                'last_seen_at',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('security_alerts', function (Blueprint $table) {
            $table->dropIndex([
                'fingerprint',
            ]);

            $table->dropIndex([
                'status',
                'last_seen_at',
            ]);

            $table->dropColumn([
                'fingerprint',
                'occurrence_count',
                'first_seen_at',
                'last_seen_at',
                'last_assessment_id',
            ]);
        });
    }
};