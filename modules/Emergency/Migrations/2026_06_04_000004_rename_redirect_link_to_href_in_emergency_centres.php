<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up()
    {
        Schema::table('bc_emergency_centres', function (Blueprint $table) {
            if (Schema::hasColumn('bc_emergency_centres', 'redirect_link') &&
                !Schema::hasColumn('bc_emergency_centres', 'href')) {
                $table->renameColumn('redirect_link', 'href');
            }
        });
    }

    public function down()
    {
        Schema::table('bc_emergency_centres', function (Blueprint $table) {
            if (Schema::hasColumn('bc_emergency_centres', 'href')) {
                $table->renameColumn('href', 'redirect_link');
            }
        });
    }
};
