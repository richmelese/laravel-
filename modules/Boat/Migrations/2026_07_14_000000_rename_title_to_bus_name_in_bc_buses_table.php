<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up()
    {
        Schema::table('bc_buses', function (Blueprint $table) {
            if (Schema::hasColumn('bc_buses', 'title') && !Schema::hasColumn('bc_buses', 'bus_name')) {
                $table->renameColumn('title', 'bus_name');
            }
        });
    }

    public function down()
    {
        Schema::table('bc_buses', function (Blueprint $table) {
            if (Schema::hasColumn('bc_buses', 'bus_name') && !Schema::hasColumn('bc_buses', 'title')) {
                $table->renameColumn('bus_name', 'title');
            }
        });
    }
};
