<?php

use App\Http\Controllers\RoundApplicationPromptResultController;
use App\Models\RoundApplicationPromptResult;
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
        Schema::table('round_application_prompt_results', function (Blueprint $table) {
            $table->integer('score')->nullable()->after('prompt_data');
        });

        Schema::table('round_applications', function (Blueprint $table) {
            $table->integer('score')->after('status')->nullable();
        });


        $applications = RoundApplicationPromptResult::all();

        foreach ($applications as $application) {
            RoundApplicationPromptResultController::calculateScore($application);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('round_application_prompt_results', function (Blueprint $table) {
            $table->dropColumn('score');
        });

        Schema::table('round_applications', function (Blueprint $table) {
            $table->dropColumn('score');
        });
    }
};
