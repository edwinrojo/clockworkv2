<?php

use App\Enums\DuplicatePolicy;
use App\Enums\EventStatus;
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
        Schema::create('events', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('venue_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type');
            $table->string('status')->default(EventStatus::Draft->value);
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->timestamp('check_in_opens_at')->nullable();
            $table->timestamp('check_in_closes_at')->nullable();
            $table->unsignedSmallInteger('qr_rotation_seconds')->default(60);
            $table->string('duplicate_policy')->default(DuplicatePolicy::PerEvent->value);
            $table->string('display_secret', 64)->unique();
            $table->string('display_pin_hash')->nullable();
            $table->timestamps();

            $table->index(['status', 'starts_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
