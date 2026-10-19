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
        Schema::create('student_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->enum('type', ['Sakit', 'Izin', 'Izin Ada Keperluan Keluarga', 'Izin Lainnya']);
            $table->date('date');
            $table->text('notes')->nullable();
            $table->string('attachment', 255)->nullable();
            $table->enum('status', ['menunggu', 'disetujui', 'ditolak'])->default('disetujui');
            $table->timestamps();

            $table->unique(['student_id', 'date'], 'student_date_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_permissions');
    }
};
