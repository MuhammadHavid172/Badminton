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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('code', 8)->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type'); // single, package
            $table->string('status')->default('pending_payment'); // pending_payment, paid, cancelled, expired
            $table->unsignedInteger('total_price');
            $table->timestamp('expires_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('cancel_reason')->nullable();
            $table->text('refund_note')->nullable();
            $table->foreignId('renewed_from_id')->nullable()->constrained('bookings')->nullOnDelete();
            $table->timestamp('renewal_notified_at')->nullable();
            $table->timestamp('last_day_notified_at')->nullable();
            $table->timestamp('ended_notified_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'expires_at']);
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
