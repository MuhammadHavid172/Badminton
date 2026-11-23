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
        Schema::create('booking_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('court_id')->constrained('courts')->cascadeOnDelete();
            $table->foreignId('play_session_id')->constrained('play_sessions')->cascadeOnDelete();
            $table->date('play_date');
            $table->timestamp('checked_in_at')->nullable();
            $table->foreignId('checked_in_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('attendance_note')->nullable();
            $table->timestamps();

            // Kunci integritas utama: satu lapangan pada satu sesi di satu tanggal hanya boleh satu slot
            $table->unique(['court_id', 'play_session_id', 'play_date'], 'unique_court_session_date');
            $table->index(['play_date', 'court_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_slots');
    }
};
