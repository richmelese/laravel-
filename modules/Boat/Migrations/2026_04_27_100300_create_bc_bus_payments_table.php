<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBcBusPaymentsTable extends Migration
{
    public function up()
    {
        Schema::create('bc_bus_payments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('booking_id')->index();
            $table->decimal('amount', 12, 2);
            $table->string('status', 20)->default('pending');
            $table->string('payment_method', 50);
            $table->string('provider_reference', 100)->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('bc_bus_payments');
    }
}
