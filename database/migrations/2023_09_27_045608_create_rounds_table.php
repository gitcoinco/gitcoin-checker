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
        Schema::create('rounds', function (Blueprint $table) {
            $table->id();
            $table->integer('chain_id');
            $table->string('round_addr', 42)->unique();
            $table->string('name')->nullable();
            $table->decimal('amount_usd', 20, 2)->default(0);
            $table->integer('votes')->default(0);
            $table->string('token')->nullable();
            $table->string('match_amount')->nullable();
            $table->decimal('match_amount_usd', 20, 2)->default(0)->nullable();
            $table->integer('unique_contributors')->default(0)->nullable();
            $table->string('application_meta_ptr')->nullable();
            $table->string('meta_ptr')->nullable();
            $table->dateTime('applications_start_time', 3)->nullable();
            $table->dateTime('applications_end_time', 3)->nullable();
            $table->dateTime('round_start_time', 3)->nullable();
            $table->dateTime('round_end_time', 3)->nullable();
            $table->integer('created_at_block')->nullable();
            $table->integer('updated_at_block')->nullable();
            $table->json('metadata')->nullable();
            $table->dateTime('highlighted_at')->nullable();
            $table->timestamps(3);
        });


        Schema::create('round_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('round_id')->constrained('rounds');
            $table->string('project_addr', 66);
            $table->string('status')->nullable();
            $table->bigInteger('last_updated_on')->nullable();
            $table->string('version')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('round_application_metadata', function (Blueprint $table) {
            $table->id();
            $table->foreignId('round_id')->constrained('rounds');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });



        Schema::create('round_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_metadata_id')->constrained('round_application_metadata');
            $table->integer('question_id');
            $table->string('title')->nullable();
            $table->string('type')->nullable();
            $table->boolean('required')->nullable();
            $table->string('info')->nullable();
            $table->boolean('hidden')->nullable();
            $table->boolean('encrypted')->nullable();
            $table->timestamps();
        });

        Schema::create('round_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_metadata_id')->constrained('round_application_metadata');
            $table->string('requirement')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rounds');
        Schema::dropIfExists('round_application_metadata');
        Schema::dropIfExists('round_questions');
        Schema::dropIfExists('round_requirements');
    }
};
