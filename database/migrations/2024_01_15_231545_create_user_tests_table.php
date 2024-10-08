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
        Schema::create('user_tests', function (Blueprint $table) {
            $table->id('ID');
            $table->string('NAME');
            $table->string('EMAIL');
            $table->timestamp('DATE_CREATED')->nullable(); // Custom created_at column
            $table->timestamp('DATE_MODIFIED')->nullable(); // Custom updated_at column 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_tests');
    }
};
