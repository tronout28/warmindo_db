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
            $table->unsignedBigInteger('menuID');
            $table->foreign('menuID')->references('menuID')->on('menus')->onDelete('cascade');
            $table->decimal('price_order');
            $table->timestamp('order_date')->useCurrent();
            $table->string('status');
            $table->decimal('payment');
            $table->boolean('refund')->default(false);
            $table->text('note')->nullable();
            $table->enum('status', ['done', 'in progress', 'cancelled', 'ready', 'waiting to cancelled'])->change();
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
