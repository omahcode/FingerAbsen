<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->onDelete('cascade');
            $table->string('device_user_id');
            $table->foreignId('student_id')->nullable()->constrained('students')->onDelete('set null');
            $table->dateTime('timestamp');
            $table->integer('status_code')->nullable();
            $table->timestamps();
            
            $table->unique(['device_id', 'device_user_id', 'timestamp'], 'unique_attendance');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_logs');
    }
};