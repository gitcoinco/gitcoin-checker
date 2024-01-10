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
        Schema::table('rounds', function (Blueprint $table) {
            $table->renameColumn('amount_usd', 'total_amount_donated_in_usd');
            $table->renameColumn('match_amount_usd', 'match_amount_in_usd');
            $table->renameColumn('unique_contributors', 'unique_donors_count');
            $table->renameColumn('round_start_time', 'donations_start_time');
            $table->renameColumn('round_end_time', 'donations_end_time');
            $table->renameColumn('metadata', 'round_metadata');
            $table->renameColumn('token', 'match_token_address');
            $table->renameColumn('votes', 'total_donations_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rounds', function (Blueprint $table) {
            $table->renameColumn('total_amount_donated_in_usd', 'amount_usd');
            $table->renameColumn('match_amount_in_usd', 'match_amount_usd');
            $table->renameColumn('unique_donors_count', 'unique_contributors');
            $table->renameColumn('donations_start_time', 'round_start_time');
            $table->renameColumn('donations_end_time', 'round_end_time');
            $table->renameColumn('round_metadata', 'metadata');
            $table->renameColumn('match_token_address', 'token');
            $table->renameColumn('total_donations_count', 'votes');
        });
    }
};
