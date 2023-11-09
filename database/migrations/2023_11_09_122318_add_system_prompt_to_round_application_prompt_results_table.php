<?php

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
            $table->text('system_prompt')->before('prompt_data')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('round_application_prompt_results', function (Blueprint $table) {
            $table->dropColumn('system_prompt');
        });
    }
};
