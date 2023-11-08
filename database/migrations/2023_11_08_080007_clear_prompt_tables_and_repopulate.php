<?php

use App\Http\Controllers\RoundPromptController;
use App\Models\Round;
use App\Models\RoundApplicationEvaluationAnswers;
use App\Models\RoundApplicationEvaluationQuestions;
use App\Models\RoundApplicationPromptResult;
use App\Models\RoundPrompt;
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
        // Clear all existing prompts
        $prompts = RoundPrompt::truncate();

        // Repupoluate with our generic defaults
        $rounds = Round::get();
        foreach ($rounds as $round) {
            RoundPromptController::ensurePromptExists($round);
        }

        $questions = RoundApplicationEvaluationQuestions::truncate();
        $answers = RoundApplicationEvaluationAnswers::truncate();
        $results = RoundApplicationPromptResult::truncate();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
