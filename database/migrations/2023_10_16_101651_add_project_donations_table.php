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
        Schema::create('project_donations', function (Blueprint $table) {
            $table->id();
            $table->integer('project_id')->unsigned();
            $table->integer('application_id')->unsigned();
            $table->integer('round_id')->unsigned();
            $table->decimal('amount_usd', 10, 2)->unsigned();
            $table->string('transaction_addr', 66)->unique();
            $table->string('voter_addr', 42);
            $table->string('grant_addr', 42);
            $table->integer('block_number')->unsigned();
            $table->timestamps();

            // Adding indexes
            $table->index('project_id');
            $table->index('transaction_addr');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_donations');
    }
};
