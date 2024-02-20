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
        Schema::create('round_roles', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->nullable()->constrained();
            $table->foreignId('round_id')->constrained();
            $table->string('role');
            $table->string('address');
            $table->timestamps();
        });

        // ensure that users.eth_addr is unique
        Schema::table('users', function (Blueprint $table) {
            $table->string('eth_addr')->unique()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('round_roles');
        Schema::table('users', function (Blueprint $table) {
            $table->string('eth_addr')->unique(false)->change();
        });
    }
};
