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
        // Change rounds and make round_addr, chain_id the unique key, instead of round_addr being unique
        Schema::table('rounds', function (Blueprint $table) {
            $table->dropUnique('rounds_round_addr_unique');
            $table->unique(['round_addr', 'chain_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rounds', function (Blueprint $table) {
            $table->dropUnique('rounds_round_addr_chain_id_unique');
            $table->unique('round_addr');
        });
    }
};
