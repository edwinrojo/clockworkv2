<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->index(['role', 'is_active']);
            $table->index('department_id');
        });

        Schema::table('event_sessions', function (Blueprint $table) {
            $table->index(['event_id', 'status', 'started_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role', 'is_active']);
            $table->dropIndex(['department_id']);
        });

        Schema::table('event_sessions', function (Blueprint $table) {
            $table->dropIndex(['event_id', 'status', 'started_at']);
        });
    }
};
