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
        Schema::create('admins', function (Blueprint $table) {
            $table->id(); // This creates an unsignedBigInteger primary key
            $table->string('name');
            $table->string('profile_picture')->nullable();
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->boolean('user_verified')->default(false);
            $table->enum('role', ['admin', 'user'])->default('admin');
            $table->string('phone_number')->unique();
            $table->string('password');
            $table->timestamp('phone_verified_at')->nullable();
            $table->string('notification_token')->nullable();
            $table->decimal('latitude', 10, 8)->nullable()->default(-6.75241);
            $table->decimal('longitude', 11, 8)->nullable()->default(110.84299);
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};
