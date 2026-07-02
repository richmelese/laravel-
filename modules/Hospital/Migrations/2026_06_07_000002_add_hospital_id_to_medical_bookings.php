<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('bc_medical_bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bc_medical_bookings', 'hospital_id')) {
                $table->unsignedBigInteger('hospital_id')->nullable()->after('id');
                $table->index('hospital_id');
            }
        });
    }

    public function down()
    {
        Schema::table('bc_medical_bookings', function (Blueprint $table) {
            $table->dropColumn('hospital_id');
        });
    }
};
