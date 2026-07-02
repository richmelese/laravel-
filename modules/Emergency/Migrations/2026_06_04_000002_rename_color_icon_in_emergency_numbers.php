<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up()
    {
        Schema::table('bc_emergency_numbers', function (Blueprint $table) {
            if (Schema::hasColumn('bc_emergency_numbers', 'dot_color') &&
                !Schema::hasColumn('bc_emergency_numbers', 'color')) {
                $table->renameColumn('dot_color', 'color');
            }
            if (Schema::hasColumn('bc_emergency_numbers', 'icon') &&
                !Schema::hasColumn('bc_emergency_numbers', 'icon_name')) {
                $table->renameColumn('icon', 'icon_name');
            }
        });
    }

    public function down()
    {
        Schema::table('bc_emergency_numbers', function (Blueprint $table) {
            if (Schema::hasColumn('bc_emergency_numbers', 'color')) {
                $table->renameColumn('color', 'dot_color');
            }
            if (Schema::hasColumn('bc_emergency_numbers', 'icon_name')) {
                $table->renameColumn('icon_name', 'icon');
            }
        });
    }
};
