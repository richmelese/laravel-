<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('bc_alert_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('bc_alert_requests', 'booked_services')) {
                // JSON array of booking IDs the user attaches to this alert
                $table->json('booked_services')->nullable()->after('booking_code');
            }
        });
    }

    public function down()
    {
        Schema::table('bc_alert_requests', function (Blueprint $table) {
            $table->dropColumn('booked_services');
        });
    }
};
