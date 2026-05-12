<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBcBusesTable extends Migration
{
    public function up()
    {
        Schema::create('bc_buses', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title')->nullable();
            $table->string('bus_number')->nullable()->index();
            $table->string('bus_type')->nullable();
            $table->unsignedInteger('seat_capacity')->nullable();
            $table->string('driver_name')->nullable();
            $table->string('driver_phone')->nullable();
            $table->string('departure_city')->nullable()->index();
            $table->string('arrival_city')->nullable()->index();
            $table->string('departure_location')->nullable();
            $table->string('arrival_location')->nullable();
            $table->timestamp('departure_time')->nullable();
            $table->timestamp('arrival_time')->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->unsignedBigInteger('image_id')->nullable();
            $table->json('gallery')->nullable();
            $table->string('status', 50)->nullable()->default('active');
            $table->tinyInteger('is_active')->default(1);
            $table->unsignedBigInteger('create_user')->nullable();
            $table->unsignedBigInteger('update_user')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('bc_buses');
    }
}
