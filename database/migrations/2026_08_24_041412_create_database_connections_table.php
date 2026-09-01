<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('database_connections', function (Blueprint $table) {
            $table->id();

            $table->string('name');

            $table->enum('driver', [
                'mysql',
                'pgsql',
            ]);

            $table->string('host');
            $table->unsignedInteger('port');

            $table->string('database');
            $table->string('username');

            $table->text('password')->nullable();

            $table->string('schema')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamp('last_connected_at')->nullable();
            $table->timestamp('last_scanned_at')->nullable();

            $table->text('description')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('database_connections');
    }
};
