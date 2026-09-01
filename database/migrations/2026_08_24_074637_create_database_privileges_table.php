<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('database_privileges', function (Blueprint $table) {

            $table->id();

            $table->foreignId('database_connection_id')
                ->constrained('database_connections')
                ->cascadeOnDelete();

            $table->string('username');

            $table->string('host')->nullable();

            $table->string('database_name')->nullable();

            $table->string('schema_name')->nullable();

            $table->string('table_name')->nullable();

            $table->string('privilege');

            $table->boolean('is_grantable')
                ->default(false);

            $table->string('risk_level')
                ->default('LOW');

            $table->text('risk_reason')->nullable();

            $table->timestamp('last_scanned_at')
                ->nullable();

            $table->timestamps();

            $table->index([
                'database_connection_id',
                'username',
            ]);

            $table->index([
                'database_name',
                'schema_name',
                'table_name',
            ]);

            $table->index('privilege');

            $table->index('risk_level');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'database_privileges'
        );
    }
};
