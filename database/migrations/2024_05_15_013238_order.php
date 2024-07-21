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
            $table->id(); // Menggunakan kolom 'id' sebagai primary key
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->unsignedBigInteger('menuID');
            $table->foreign('menuID')->references('menuID')->on('menus')->onDelete('cascade');
            $table->decimal('price_order', 8, 2); // Tentukan panjang dan presisi decimal
            $table->timestamp('order_date')->useCurrent();
            $table->enum('status', ['selesai', 'sedang diproses', 'batal', 'pesanan siap', 'menunggu batal']);
            $table->decimal('payment', 8, 2); // Tentukan panjang dan presisi decimal
            $table->boolean('refund')->default(false);
            $table->text('note')->nullable();
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