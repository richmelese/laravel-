<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tour `duration` was always interpreted as hours. Multi-day tours need
     * a unit, so store it alongside the number ("hour" or "day").
     */
    public function up()
    {
        Schema::table('bc_tours', function (Blueprint $table) {
            if (!Schema::hasColumn('bc_tours', 'duration_unit')) {
                $table->string('duration_unit', 10)->nullable()->default('hour')->after('duration');
            }
        });
    }

    public function down()
    {
        Schema::table('bc_tours', function (Blueprint $table) {
            if (Schema::hasColumn('bc_tours', 'duration_unit')) {
                $table->dropColumn('duration_unit');
            }
        });
    }
};
