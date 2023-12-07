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
        Schema::table('round_applications', function (Blueprint $table) {
            $table->decimal('donor_amount_usd', 20, 2)->after('metadata');
            $table->decimal('match_amount_usd', 20, 2)->after('donor_amount_usd');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('round_applications', function (Blueprint $table) {
            $table->dropColumn('donor_amount_usd');
            $table->dropColumn('match_amount_usd');
        });
    }
};
