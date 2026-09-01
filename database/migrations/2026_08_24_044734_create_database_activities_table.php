<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('database_activities', function (Blueprint $table) {

            $table->id();

            $table->foreignId('database_connection_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('database_name')->nullable();

            $table->string('schema_name')->nullable();

            $table->string('table_name')->nullable();

            $table->string('username')->nullable();

            $table->string('client_ip')->nullable();

            $table->string('action')->nullable();

            $table->text('query')->nullable();

            $table->string('status')->default('success');

            $table->text('error_message')->nullable();

            $table->unsignedInteger('execution_time_ms')->nullable();

            $table->timestamp('executed_at')->index();

            $table->timestamps();

            $table->index([
                'database_connection_id',
                'executed_at',
            ]);

            $table->index([
                'action',
                'executed_at',
            ]);

            $table->index([
                'username',
                'executed_at',
            ]);

        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'database_activities'
        );
    }
};
