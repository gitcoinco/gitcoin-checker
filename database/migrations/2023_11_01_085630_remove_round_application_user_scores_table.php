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
        Schema::dropIfExists('round_application_user_scores');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('round_application_user_scores', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->integer('round_id')->unsigned();
            $table->integer('application_id')->unsigned();
            $table->integer('user_id')->unsigned();
            $table->tinyInteger('score')->unsigned();
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }
};
