<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('new_concepts', function (Blueprint $table) {
            $table->id();
            $table->string('level');
            $table->string('subject');
            $table->integer('worksheet');
            $table->enum('is_new_concept', ['Y', 'N'])->default('N');
            $table->timestamps();

            // Index for better performance
            $table->index(['level', 'subject']);
            $table->index(['worksheet']);
            $table->index(['is_new_concept']);
            
            // Unique constraint for level-subject-worksheet combination
            $table->unique(['level', 'subject', 'worksheet']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('new_concepts');
    }
};