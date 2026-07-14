<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSideNumberAndPriceInWordsToBcBusesTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('bc_buses')) {
            return;
        }

        Schema::table('bc_buses', function (Blueprint $table) {
            if (!Schema::hasColumn('bc_buses', 'side_number')) {
                $table->string('side_number', 50)->nullable()->after('bus_number');
            }
            if (!Schema::hasColumn('bc_buses', 'price_in_words')) {
                $table->string('price_in_words', 255)->nullable()->after('price');
            }
        });
    }

    public function down()
    {
        if (!Schema::hasTable('bc_buses')) {
            return;
        }

        Schema::table('bc_buses', function (Blueprint $table) {
            foreach (['side_number', 'price_in_words'] as $column) {
                if (Schema::hasColumn('bc_buses', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}
