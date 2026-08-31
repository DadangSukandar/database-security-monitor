<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discovered_tables', function (Blueprint $table) {
            $table->id();

            $table->foreignId('discovered_database_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('schema_name')->nullable();

            $table->string('name');

            $table->string('type')->nullable();

            $table->unsignedBigInteger('estimated_rows')->nullable();

            $table->timestamps();

            $table->unique([
                'discovered_database_id',
                'schema_name',
                'name'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discovered_tables');
    }
};