<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discovered_columns', function (Blueprint $table) {
            $table->id();

            $table->foreignId('discovered_table_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name');

            $table->string('data_type')->nullable();

            $table->string('column_type')->nullable();

            $table->boolean('is_nullable')->default(true);

            $table->string('default_value')->nullable();

            $table->boolean('is_primary')->default(false);

            $table->timestamps();

            $table->unique([
                'discovered_table_id',
                'name'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discovered_columns');
    }
};