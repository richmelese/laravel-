<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('bc_app_promo_redemptions')) {
            return;
        }

        Schema::create('bc_app_promo_redemptions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('user_id')->nullable();   // null for guests
            $table->string('email', 255);                // always stored
            $table->string('booking_code', 100)->nullable();
            $table->string('promo_code', 100);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->timestamp('redeemed_at')->nullable();
            $table->bigInteger('create_user')->nullable();
            $table->timestamps();

            $table->index('email');
            $table->index('user_id');
            $table->index('promo_code');
        });
    }

    public function down()
    {
        Schema::dropIfExists('bc_app_promo_redemptions');
    }
};
