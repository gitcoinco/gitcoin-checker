<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('projects', function (Blueprint $table) {
            // Create a new column
            $table->longText('description_new')->after('title')->nullable();
        });

        // Copy data from old column to new column
        DB::statement('UPDATE projects SET description_new = description');

        Schema::table('projects', function (Blueprint $table) {
            // Drop the old column
            $table->dropColumn('description');

            // Rename the new column to old column's name
            $table->renameColumn('description_new', 'description');
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->text('description')->change();
        });
    }
};
