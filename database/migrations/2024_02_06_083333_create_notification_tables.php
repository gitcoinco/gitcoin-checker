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
        Schema::create('notification_setup', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->string('medium')->default('email');
            $table->text('additional_emails')->nullable();
            $table->string('details');  // individual (for each new record) or summary
            $table->boolean('include_applications')->default(true);
            $table->boolean('include_rounds')->default(true);
            $table->string('summary_frequency')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // The list of rounds to include in the notification
        Schema::create('notification_setup_rounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_id')->constrained('notification_setup')->onDelete('cascade');
            $table->unsignedBigInteger('round_id');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_id')->constrained('notification_setup')->onDelete('cascade');
            $table->string('subject');
            $table->text('message');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('notification_log_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_log_id')->constrained('notification_logs')->onDelete('cascade');
            $table->unsignedBigInteger('application_id');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('notification_log_rounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_log_id')->constrained('notification_logs')->onDelete('cascade');
            $table->unsignedBigInteger('round_id');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_log_applications');
        Schema::dropIfExists('notification_logs');
        Schema::dropIfExists('notification_setup_rounds');
        Schema::dropIfExists('notification_setup');
    }
};
