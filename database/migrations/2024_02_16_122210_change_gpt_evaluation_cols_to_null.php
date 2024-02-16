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
        Schema::table('gpt_round_eligibility_scores', function (Blueprint $table) {
            $table->string('score')->nullable()->change();
            $table->string('reason')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gpt_round_eligibility_scores', function (Blueprint $table) {
            $table->string('score')->nullable(false)->change();
            $table->string('reason')->nullable(false)->change();
        });
    }
};
