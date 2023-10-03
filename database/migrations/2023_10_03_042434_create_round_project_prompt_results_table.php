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
        Schema::create('round_application_prompt_results', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('application_id');
            $table->unsignedBigInteger('round_id');
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('prompt_id');
            $table->string('prompt_type')->default('chatgpt');
            $table->longText('prompt_data')->nullable();
            $table->longText('results_data')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('round_application_prompt_results');
    }
};
