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
            $table->string('application_id', 66)->nullable()->after('id');
            $table->index('application_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('round_applications', function (Blueprint $table) {
            $table->dropColumn('application_id');
        });
    }
};
