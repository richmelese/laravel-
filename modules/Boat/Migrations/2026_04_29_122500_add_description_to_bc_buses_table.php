<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDescriptionToBcBusesTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('bc_buses')) {
            return;
        }

        Schema::table('bc_buses', function (Blueprint $table) {
            if (!Schema::hasColumn('bc_buses', 'description')) {
                $table->text('description')->nullable()->after('title');
            }
        });
    }

    public function down()
    {
        if (!Schema::hasTable('bc_buses') || !Schema::hasColumn('bc_buses', 'description')) {
            return;
        }

        Schema::table('bc_buses', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
}
