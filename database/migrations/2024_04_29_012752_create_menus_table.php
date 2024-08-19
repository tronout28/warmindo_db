<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->string('image');
            $table->string('name_menu');
            $table->integer('price');
            $table->string('category');
            $table->string('second_category');
            $table->integer('stock');
            $table->float('rating')->nullable();
            $table->text('description');
            $table->boolean('status_menu')->default(true);
            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menus');
    }
};
