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
        Schema::create('absensi', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Foreign key for User
        $table->date('tanggal'); // Date of attendance
        $table->string('kelas'); // Class (Cepatan, Lambatan, etc.)
        $table->string('kegiatan'); // Activity (Ngaji Subuh, Apel, etc.)
        $table->enum('status', ['hadir', 'telat', 'izin', 'sakit', 'alpha']); // Attendance status
        $table->text('keterangan')->nullable(); // Additional notes
        $table->string('kategori_absensi')->nullable(); // Category of attendance (e.g
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('absensi');
    }
};
