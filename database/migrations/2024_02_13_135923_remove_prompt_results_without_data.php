<?php

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
        // Delete old trashed results from RoundApplicationPromptResult
        $results = RoundApplicationPromptResult::onlyTrashed()->get();
        foreach ($results as $result) {
            echo "Deleting trashed result {$result->id}" . PHP_EOL;
            $result->forceDelete();
        }


        $results = RoundApplicationPromptResult::where('results_data', '[]')->get();
        foreach ($results as $result) {
            echo "Deleting result {$result->id}" . PHP_EOL;
            $result->delete();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
