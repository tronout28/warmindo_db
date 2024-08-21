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
            $table->string('image')->nullable();
            $table->string('name_menu')->nullable();
            $table->integer('price')->nullable();
            $table->string('category')->nullable();
            $table->string('second_category')->nullable();
            $table->integer('stock')->nullable();
            $table->double('rating')->nullable();
            $table->text('description')->nullable();
            $table->boolean('status_menu')->default(true);
            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menus');
    }
};
