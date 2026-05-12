<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBcBusBookingsTable extends Migration
{
    public function up()
    {
        Schema::create('bc_bus_bookings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('schedule_id')->index();
            $table->unsignedInteger('seat_number');
            $table->string('status', 20)->default('pending');
            $table->string('payment_status', 20)->default('unpaid');
            $table->string('ticket_code', 50)->nullable()->unique();
            $table->timestamps();

            $table->unique(['schedule_id', 'seat_number'], 'uniq_schedule_seat');
        });
    }

    public function down()
    {
        Schema::dropIfExists('bc_bus_bookings');
    }
}
