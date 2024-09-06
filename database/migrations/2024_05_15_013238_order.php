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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->integer('price_order')->nullable();
            $table->enum('status', ['selesai', 'sedang diproses', 'batal', 'pesanan siap', 'menunggu pembayaran','menunggu pengembalian dana','konfirmasi pesanan'])->default('menunggu pembayaran');
            $table->text('note')->nullable();
            $table->enum('payment_method', ['tunai', 'ovo', 'gopay', 'dana', 'linkaja', 'shopeepay', 'transfer'])->nullable(); // Use enum here
            $table->enum('order_method', ['dine-in', 'take-away', 'delivery'])->nullable(); // Use enum here
            
            // Add the cancelation-related fields
            $table->text('reason_cancel')->nullable();
            $table->enum('cancel_method',['tunai','BCA','BNI','BRI','BSI','Mandiri'])->nullable();
            $table->bigInteger('no_rekening')->nullable();
            $table->integer('admin_fee')->nullable();

            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('admin_fee');
        });
        Schema::dropIfExists('orders');
    }
};
