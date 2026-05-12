<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBcBusRoutesTable extends Migration
{
    public function up()
    {
        Schema::create('bc_bus_routes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('from_location')->index();
            $table->string('to_location')->index();
            $table->decimal('distance_km', 10, 2)->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('bc_bus_routes');
    }
}
