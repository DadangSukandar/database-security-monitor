<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /** @var array<string, callable(Blueprint): mixed> $columns */
        $columns = [
            'database_activity_id' => fn (Blueprint $table) => $table->foreignId('database_activity_id')->nullable()->constrained('database_activities')->nullOnDelete(),
            'database_connection_id' => fn (Blueprint $table) => $table->foreignId('database_connection_id')->nullable()->constrained('database_connections')->nullOnDelete(),
            'username' => fn (Blueprint $table) => $table->string('username')->nullable(),
            'client_ip' => fn (Blueprint $table) => $table->string('client_ip', 45)->nullable(),
            'alert_type' => fn (Blueprint $table) => $table->string('alert_type')->nullable(),
            'action' => fn (Blueprint $table) => $table->string('action')->nullable(),
            'rule' => fn (Blueprint $table) => $table->string('rule')->nullable(),
            'description' => fn (Blueprint $table) => $table->text('description')->nullable(),
            'query' => fn (Blueprint $table) => $table->longText('query')->nullable(),
            'table_name' => fn (Blueprint $table) => $table->string('table_name')->nullable(),
            'detected_at' => fn (Blueprint $table) => $table->timestamp('detected_at')->nullable(),
            'resolution_note' => fn (Blueprint $table) => $table->text('resolution_note')->nullable(),
        ];

        foreach ($columns as $column => $definition) {
            if (! Schema::hasColumn('security_alerts', $column)) {
                Schema::table('security_alerts', $definition);
            }
        }
    }

    public function down(): void
    {
        /**
         * The columns may predate this compatibility migration in existing
         * installations, so rollback intentionally preserves application data.
         */
    }
};
