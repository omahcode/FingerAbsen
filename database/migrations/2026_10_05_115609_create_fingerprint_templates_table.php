<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fingerprint_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->string('device_user_id');
            $table->integer('finger_index'); // 0-9 untuk tiap jari
            $table->integer('size')->nullable();
            $table->integer('valid')->default(1);
            $table->text('template_data');
            $table->timestamps();

            $table->unique(['device_user_id', 'finger_index']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fingerprint_templates');
    }
};