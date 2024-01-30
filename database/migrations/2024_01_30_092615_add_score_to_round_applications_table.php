<?php

use App\Http\Controllers\RoundApplicationController;
use App\Models\RoundApplication;
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
        Schema::table('round_applications', function (Blueprint $table) {
            $table->integer('score')->after('status')->nullable();
        });

        $roundApplications = RoundApplication::all();

        foreach ($roundApplications as $roundApplication) {
            RoundApplicationController::updateScore($roundApplication);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('round_applications', function (Blueprint $table) {
            $table->dropColumn('score');
        });
    }
};
