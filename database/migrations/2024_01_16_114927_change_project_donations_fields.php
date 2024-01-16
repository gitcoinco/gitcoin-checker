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
        Schema::table('project_donations', function (Blueprint $table) {
            $table->renameColumn('voter_addr', 'donor_address');
            $table->renameColumn('grant_addr', 'recipient_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_donations', function (Blueprint $table) {
            $table->renameColumn('donor_address', 'voter_addr');
            $table->renameColumn('recipient_address', 'grant_addr');
        });
    }
};
