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
        /**
         * Score how well a round is setup in terms of the eligibility criteria and the questions asked in the round applications.
         */

        Schema::create('gpt_round_eligibility_scores', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('round_id')->constrained('rounds')->onDelete('cascade');
            $table->longText('gpt_prompt');
            $table->json('gpt_response');
            $table->integer('score');
            $table->text('reason');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gpt_round_eligibility_scores');
    }
};
