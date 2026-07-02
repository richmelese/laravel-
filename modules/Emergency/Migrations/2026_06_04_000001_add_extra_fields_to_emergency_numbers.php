<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up()
    {
        Schema::table('bc_emergency_numbers', function (Blueprint $table) {
            if (!Schema::hasColumn('bc_emergency_numbers', 'href')) {
                $table->string('href', 500)->nullable()->after('number');
            }
            if (!Schema::hasColumn('bc_emergency_numbers', 'color')) {
                $table->string('color', 100)->nullable()->after('href');
            }
            if (!Schema::hasColumn('bc_emergency_numbers', 'icon_name')) {
                $table->string('icon_name', 100)->nullable()->after('color');
            }
        });
    }

    public function down()
    {
        Schema::table('bc_emergency_numbers', function (Blueprint $table) {
            $table->dropColumn(['href', 'color', 'icon_name']);
        });
    }
};
