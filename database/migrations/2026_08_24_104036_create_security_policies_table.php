<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_policies', function (Blueprint $table) {
            $table->id();

            $table->string('name');

            $table->string('code')->unique();

            $table->string('rule_type');

            $table->string('severity')->default('MEDIUM');

            $table->text('description')->nullable();

            $table->json('conditions')->nullable();

            $table->boolean('is_active')->default(true);

            $table->unsignedInteger('priority')->default(100);

            $table->timestamps();

            $table->index('rule_type');
            $table->index('severity');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_policies');
    }
};