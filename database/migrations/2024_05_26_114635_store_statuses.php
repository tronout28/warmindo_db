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
        Schema::create('store_statuses', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_open')->default(true);
            $table->string('days')->nullable(); // Store days like "Monday, Tuesday"
            $table->string('hours')->nullable(); // Store hours like "24 Hours"
            $table->string('temporary_closure_duration')->nullable(); // Store closure duration like "30 minutes"
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_statuses');
    }
};
