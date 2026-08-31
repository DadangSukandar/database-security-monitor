<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('database_users', function (Blueprint $table) {

            $table->id();

            $table->foreignId(
                'database_connection_id'
            )
                ->constrained(
                    'database_connections'
                )
                ->cascadeOnDelete();

            $table->string('username');

            $table->string(
                'host'
            )->nullable();

            $table->string(
                'authentication_plugin'
            )->nullable();

            $table->boolean(
                'can_login'
            )->default(true);

            $table->boolean(
                'is_superuser'
            )->default(false);

            $table->boolean(
                'is_locked'
            )->default(false);

            $table->boolean(
                'can_create_database'
            )->default(false);

            $table->boolean(
                'can_create_role'
            )->default(false);

            $table->boolean(
                'can_grant'
            )->default(false);

            $table->boolean(
                'is_replication'
            )->default(false);

            $table->boolean(
                'bypass_rls'
            )->default(false);

            $table->string(
                'risk_level'
            )->default('LOW');

            $table->text(
                'risk_reasons'
            )->nullable();

            $table->timestamp(
                'last_scanned_at'
            )->nullable();

            $table->timestamps();

            $table->unique([
                'database_connection_id',
                'username',
                'host',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'database_users'
        );
    }
};