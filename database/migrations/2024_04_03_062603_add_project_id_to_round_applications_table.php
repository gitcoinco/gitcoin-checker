<?php

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
            $table->unsignedBigInteger('project_id')
                ->after('round_id')->nullable();
        });

        $applications = RoundApplication::with('project')->get();

        foreach ($applications as $application) {
            if ($application->project) {
                $application->project_id = $application->project->id;
                $application->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('round_applications', function (Blueprint $table) {
            $table->dropColumn('project_id');
        });
    }
};
