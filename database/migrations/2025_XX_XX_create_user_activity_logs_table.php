<?php

// database/migrations/2025_01_XX_create_user_activity_logs_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('user_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('action'); // 'update_page', 'update_level', 'update_repeats', 'delete_data', 'save_grade', etc.
            $table->string('entity_type')->nullable(); // 'lesson_plan', 'grade', 'student_config', etc.
            $table->unsignedBigInteger('entity_id')->nullable(); // ID of the affected record
            $table->string('student_name')->nullable();
            $table->string('subject')->nullable();
            $table->string('month')->nullable();
            $table->integer('date')->nullable();
            $table->json('old_values')->nullable(); // Previous values before change
            $table->json('new_values')->nullable(); // New values after change
            $table->text('description')->nullable(); // Human readable description
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['user_id', 'created_at']);
            $table->index(['student_name', 'subject']);
            $table->index(['action', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_activity_logs');
    }
};