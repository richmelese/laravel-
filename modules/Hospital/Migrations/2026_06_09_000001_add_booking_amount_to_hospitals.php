<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('bc_hospitals', function (Blueprint $table) {
            $table->decimal('booking_amount', 10, 2)->nullable()->after('status');
            $table->string('currency', 10)->default('ETB')->after('booking_amount');
        });
    }

    public function down()
    {
        Schema::table('bc_hospitals', function (Blueprint $table) {
            $table->dropColumn(['booking_amount', 'currency']);
        });
    }
};
