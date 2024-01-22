<?php

use App\Models\Project;
use App\Models\ProjectDonation;
use App\Models\ProjectOwner;
use App\Models\Round;
use App\Models\RoundApplication;
use App\Models\RoundApplicationEvaluationAnswers;
use App\Models\RoundApplicationEvaluationQuestions;
use App\Models\RoundApplicationMetadata;
use App\Models\RoundApplicationPromptResult;
use App\Models\RoundPrompt;
use App\Models\RoundQuestion;
use App\Models\RoundRequirement;
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
        ProjectDonation::all()->delete();
        ProjectOwner::all()->delete();
        Project::all()->delete();
        RoundApplicationEvaluationAnswers::all()->delete();
        RoundApplicationEvaluationQuestions::all()->delete();
        RoundApplicationMetadata::all()->delete();
        RoundApplicationPromptResult::all()->delete();
        RoundApplication::all()->delete();
        RoundPrompt::all()->delete();
        RoundQuestion::all()->delete();
        RoundRequirement::all()->delete();
        Round::all()->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
