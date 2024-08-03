<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('toppings', function (Blueprint $table) {
            $table->id();
            $table->string('name_topping');
            $table->decimal('price');
            $table->integer('stock_topping');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('toppings');
    }
};
