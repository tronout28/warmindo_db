<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
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
            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menus');
    }
};
