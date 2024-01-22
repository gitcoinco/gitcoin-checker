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
        ProjectDonation::query()->forceDelete();
        ProjectOwner::query()->forceDelete();
        Project::query()->forceDelete();
        RoundApplicationEvaluationAnswers::query()->forceDelete();
        RoundApplicationEvaluationQuestions::query()->forceDelete();
        RoundApplicationMetadata::query()->forceDelete();
        RoundApplicationPromptResult::query()->forceDelete();
        RoundApplication::query()->forceDelete();
        RoundPrompt::query()->forceDelete();
        RoundQuestion::query()->forceDelete();
        RoundRequirement::query()->delete();
        Round::query()->forceDelete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
