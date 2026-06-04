<?php

use App\Enums\EventRosterScope;
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
        Schema::table('events', function (Blueprint $table) {
            $table->string('roster_scope')->default(EventRosterScope::AllActiveEmployees->value)->after('duplicate_policy');
        });

        Schema::create('event_department', function (Blueprint $table) {
            $table->foreignUlid('event_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('department_id')->constrained()->cascadeOnDelete();

            $table->primary(['event_id', 'department_id']);
        });

        Schema::create('event_user', function (Blueprint $table) {
            $table->foreignUlid('event_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();

            $table->primary(['event_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_user');
        Schema::dropIfExists('event_department');

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('roster_scope');
        });
    }
};
