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
    public function up(): void
    {
        // Add rpc_endpoint to chains table
        Schema::table('chains', function (Blueprint $table) {
            $table->string('rpc_endpoint')->after('name')->nullable();
        });

        // Delete all existing data
        DB::table('block_times')->delete();

        Schema::table('block_times', function (Blueprint $table) {
            $table->bigIncrements('id')->change();
            $table->dropColumn('created_at');
            $table->dropColumn('updated_at');

            // Add a constraint for block_number and chain_id
            $table->unique(['block_number', 'chain_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chains', function (Blueprint $table) {
            $table->dropColumn('rpc_endpoint');
        });

        Schema::table('block_times', function (Blueprint $table) {
            $table->dropUnique(['block_number', 'chain_id']);
            $table->timestamps();
        });
    }
};
