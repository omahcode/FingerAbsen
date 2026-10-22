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
        Schema::create('school_events', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // Contoh: "Kemah Pramuka / Ekspram", "Libur Semester 2 Minggu"
            $table->string('type')->default('event'); // 'event' (kegiatan kemah/ekspram), 'holiday' (libur sekolah), 'dispensation'
            $table->date('start_date');
            $table->date('end_date');
            $table->string('target_type')->default('all'); // 'all', 'grade', 'class'
            $table->string('grade_level')->nullable(); // '10', '11', '12'
            $table->foreignId('school_class_id')->nullable()->constrained('school_classes')->cascadeOnDelete();
            $table->boolean('preserve_streak')->default(true); // Lindungi streak siswa agar tidak putus
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_events');
    }
};
