<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('nis')->nullable();
            $table->string('name');
            $table->foreignId('school_class_id')->constrained('school_classes')->onDelete('cascade');
            $table->string('device_user_id')->nullable()->unique(); // ID di mesin fingerprint
            $table->string('privilege')->default('0');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};