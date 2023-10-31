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
        Schema::create('round_evaluation_questions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->integer('round_id')->unsigned();
            $table->json('questions')->nullable();
            $table->timestamps();
        });

        Schema::create('round_evaluation_answers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->integer('user_id')->unsigned();
            $table->integer('round_id')->unsigned();
            $table->integer('application_id')->unsigned();
            $table->json('questions')->nullable(); // The structure of the questions on submission
            $table->json('answers')->nullable();
            $table->integer('score')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('round_evaluation_questions');
        Schema::dropIfExists('round_evaluation_answers');
    }
};
