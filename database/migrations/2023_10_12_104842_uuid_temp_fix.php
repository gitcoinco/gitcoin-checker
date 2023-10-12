<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Hashids\Hashids;


return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // access_controls, chains, projects, rounds, round_applications, users
        $tables = ['access_controls', 'chains', 'projects', 'round_application_metadata', 'round_application_prompt_results', 'rounds', 'round_applications', 'round_prompts', 'round_questions', 'round_requirements', 'users'];

        // Set the uuids for existing data
        foreach ($tables as $table) {
            $models = DB::table($table)->get();
            foreach ($models as $model) {
                $hashids = new Hashids(class_basename($model), 7);
                $uuid = $hashids->encode($model->id);

                DB::table($table)->where('id', $model->id)->update(['uuid' => $uuid]);
            }
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
