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
            $table->integer('donor_contributions_count')->after('donor_amount_usd')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('round_applications', function (Blueprint $table) {
            $table->dropColumn('donor_contributions_count');
        });
    }
};
