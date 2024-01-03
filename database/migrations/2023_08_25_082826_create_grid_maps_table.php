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
        Schema::create('grid_maps', function (Blueprint $table) {
            $table->id('ID');
            $table->string('LONGITUDE');
            $table->string('LATITUDE');
            $table->string('REMARK');
            $table->foreignId('CPR_MST_DISTRICT_ID');
            $table->foreignId('CPR_MST_SUBDISTRICT_ID');
            $table->string('SUBDISTRICT');
            $table->timestamp('DATE_CREATED')->nullable(); // Custom created_at column
            $table->timestamp('DATE_MODIFIED')->nullable(); // Custom updated_at column    
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grid_maps');
    }
};
