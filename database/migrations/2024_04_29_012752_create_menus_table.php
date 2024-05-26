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
        Schema::create('menus', function (Blueprint $table) {
            $table->id('menuID');
            $table->string('image');
            $table->string('name_menu');
            $table->decimal('price');
            $table->string('category');
            $table->integer('stock');
            $table->float('ratings');
            $table->text('description');
            $table->unsignedBigInteger('topping_id')->nullable();
            $table->foreign('topping_id')->references('topping_id')->on('toppings')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menus');
    }
};
