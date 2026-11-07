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
        Schema::create('device_fingerprint_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained('devices')->cascadeOnDelete();
            $table->string('device_user_id', 50)->index();
            $table->integer('finger_index')->default(0);
            $table->foreignId('fingerprint_template_id')->nullable()->constrained('fingerprint_templates')->nullOnDelete();
            $table->string('status', 30)->default('synced');
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['device_id', 'device_user_id', 'finger_index'], 'dev_user_fp_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_fingerprint_templates');
    }
};
