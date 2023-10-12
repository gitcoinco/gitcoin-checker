<?php

use App\Models\Project;
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

        Schema::table('projects', function (Blueprint $table) {
            $table->string('slug')->unique()->nullable()->after('id_addr');
        });

        $projects = Project::whereNull('slug')->get();

        foreach ($projects as $project) {
            $project->slug = $project->createUniqueSlug();
            $project->save();
        }

        // Slug should not be nullable
        Schema::table('projects', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};
