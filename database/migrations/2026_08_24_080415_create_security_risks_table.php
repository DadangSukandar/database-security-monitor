<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_risks', function (Blueprint $table) {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Connection
            |--------------------------------------------------------------------------
            */

            $table->foreignId('database_connection_id')
                ->constrained('database_connections')
                ->cascadeOnDelete();


            /*
            |--------------------------------------------------------------------------
            | User / Access
            |--------------------------------------------------------------------------
            */

            $table->string('username')->nullable();

            $table->string('host')->nullable();


            /*
            |--------------------------------------------------------------------------
            | Database Object
            |--------------------------------------------------------------------------
            */

            $table->string('database_name')->nullable();

            $table->string('schema_name')->nullable();

            $table->string('table_name')->nullable();

            $table->string('column_name')->nullable();


            /*
            |--------------------------------------------------------------------------
            | Privilege
            |--------------------------------------------------------------------------
            */

            $table->string('privilege')->nullable();

            $table->boolean('is_grantable')
                ->default(false);


            /*
            |--------------------------------------------------------------------------
            | Sensitive Data
            |--------------------------------------------------------------------------
            */

            $table->string('sensitive_category')->nullable();

            $table->string('sensitive_rule')->nullable();


            /*
            |--------------------------------------------------------------------------
            | Risk
            |--------------------------------------------------------------------------
            */

            $table->string('risk_level')
                ->default('LOW');

            $table->text('risk_reason')->nullable();


            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            */

            $table->boolean('is_resolved')
                ->default(false);


            /*
            |--------------------------------------------------------------------------
            | Scan
            |--------------------------------------------------------------------------
            */

            $table->timestamp('last_scanned_at')
                ->nullable();

            $table->timestamps();


            /*
            |--------------------------------------------------------------------------
            | Index
            |--------------------------------------------------------------------------
            */

            $table->index([
                'database_connection_id',
                'risk_level',
            ]);

            $table->index([
                'username',
            ]);

            $table->index([
                'database_name',
                'table_name',
            ]);

            $table->index([
                'is_resolved',
            ]);
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('security_risks');
    }
};