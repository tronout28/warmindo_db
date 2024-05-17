<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
     /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id('order_id');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('menuID')->constrained('menus')->onDelete('cascade'); // Asumsi tabel menus ada
            $table->decimal('price_order', 10, 2);
            $table->timestamp('order_date')->useCurrent();
            $table->string('status');
            $table->decimal('payment', 10, 2);
            $table->boolean('refund')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('orders');
    }
};
