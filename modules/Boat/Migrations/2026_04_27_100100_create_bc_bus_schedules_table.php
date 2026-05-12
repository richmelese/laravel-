<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBcBusSchedulesTable extends Migration
{
    public function up()
    {
        Schema::create('bc_bus_schedules', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('bus_id')->index();
            $table->unsignedBigInteger('route_id')->index();
            $table->timestamp('departure_time');
            $table->timestamp('arrival_time')->nullable();
            $table->decimal('price', 12, 2);
            $table->string('status', 20)->default('scheduled');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('bc_bus_schedules');
    }
}
