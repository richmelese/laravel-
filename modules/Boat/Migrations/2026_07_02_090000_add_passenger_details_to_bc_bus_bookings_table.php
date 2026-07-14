<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPassengerDetailsToBcBusBookingsTable extends Migration
{
    public function up()
    {
        Schema::table('bc_bus_bookings', function (Blueprint $table) {
            $table->string('booking_group', 36)->nullable()->index()->after('user_id');
            $table->string('trip_type', 20)->nullable()->after('status');
            $table->date('departure_date')->nullable()->after('trip_type');
            $table->unsignedInteger('passengers')->nullable()->after('departure_date');
            $table->string('passenger_name', 150)->nullable()->after('passengers');
            $table->string('email', 150)->nullable()->after('passenger_name');
            $table->string('phone', 30)->nullable()->after('email');
            $table->string('boarding_point', 191)->nullable()->after('phone');
            $table->string('dropping_point', 191)->nullable()->after('boarding_point');
            $table->unsignedInteger('paid_luggage')->default(0)->after('dropping_point');
            $table->unsignedInteger('special_luggage')->default(0)->after('paid_luggage');
        });
    }

    public function down()
    {
        Schema::table('bc_bus_bookings', function (Blueprint $table) {
            $table->dropColumn([
                'booking_group',
                'trip_type',
                'departure_date',
                'passengers',
                'passenger_name',
                'email',
                'phone',
                'boarding_point',
                'dropping_point',
                'paid_luggage',
                'special_luggage',
            ]);
        });
    }
}
