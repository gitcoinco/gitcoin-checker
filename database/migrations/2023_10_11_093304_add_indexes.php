<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;


return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE rounds ADD FULLTEXT index rounds_name_fulltext(name)');
        DB::statement('ALTER TABLE projects ADD FULLTEXT index projects_title_fulltext(title)');
        DB::statement('ALTER TABLE sessions ADD FULLTEXT index sessions_id_fulltext(id)');
        DB::statement('ALTER TABLE access_controls ADD FULLTEXT index access_controls_eth_addr_fulltext(eth_addr)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rounds', function (Blueprint $table) {
            $table->dropIndex('rounds_name_fulltext');
        });
        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex('projects_title_fulltext');
        });
        Schema::table('sessions', function (Blueprint $table) {
            $table->dropIndex('sessions_id_fulltext');
        });
        Schema::table('access_controls', function (Blueprint $table) {
            $table->dropIndex('access_controls_eth_addr_fulltext');
        });
    }
};
