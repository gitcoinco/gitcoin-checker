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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('id_addr', 66)->unique();
            $table->string('title')->nullable();
            $table->unsignedBigInteger('project_number');
            $table->string('meta_ptr');
            $table->json('metadata');
            $table->json('owners');
            $table->unsignedBigInteger('created_at_block');
            $table->dateTime('highlighted_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
