<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up()
    {
        Schema::table('bc_emergency_centres', function (Blueprint $table) {
            if (!Schema::hasColumn('bc_emergency_centres', 'redirect_link')) {
                $table->string('redirect_link', 500)->nullable()->after('note');
            }
        });
    }

    public function down()
    {
        Schema::table('bc_emergency_centres', function (Blueprint $table) {
            $table->dropColumn('redirect_link');
        });
    }
};
