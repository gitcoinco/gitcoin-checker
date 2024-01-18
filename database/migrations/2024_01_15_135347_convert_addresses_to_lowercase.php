<?php

use App\Models\AccessControl;
use App\Models\Project;
use App\Models\ProjectDonation;
use App\Models\ProjectOwner;
use App\Models\Round;
use App\Models\RoundApplication;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $rounds = Round::all();
        foreach ($rounds as $round) {
            $round->round_addr = Str::lower($round->round_addr);
            $round->match_token_address = Str::lower($round->match_token_address);
            $round->save();
        }

        $roundApplications = RoundApplication::all();
        foreach ($roundApplications as $roundApplication) {
            $roundApplication->project_addr = Str::lower($roundApplication->project_addr);
            $roundApplication->save();
        }

        $projects = Project::all();
        foreach ($projects as $project) {
            $project->id_addr = Str::lower($project->id_addr);
            $project->save();
        }

        $projectOwners = ProjectOwner::all();
        foreach ($projectOwners as $projectOwner) {
            $projectOwner->eth_addr = Str::lower($projectOwner->eth_addr);
            $projectOwner->save();
        }

        $projectDonations = ProjectDonation::all();
        foreach ($projectDonations as $projectDonation) {
            $projectDonation->grant_addr = Str::lower($projectDonation->grant_addr);
            $projectDonation->save();
        }

        $accessControl = AccessControl::all();
        foreach ($accessControl as $access) {
            $access->eth_addr = Str::lower($access->eth_addr);
            $access->save();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
