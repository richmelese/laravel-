<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up()
    {
        Schema::table('bc_emergency_centres', function (Blueprint $table) {
            if (!Schema::hasColumn('bc_emergency_centres', 'alias')) {
                $table->string('alias')->nullable()->after('name');
            }
            if (!Schema::hasColumn('bc_emergency_centres', 'hours')) {
                $table->string('hours')->nullable()->after('phone');
            }
            if (!Schema::hasColumn('bc_emergency_centres', 'services')) {
                $table->json('services')->nullable()->after('hours');
            }
            if (!Schema::hasColumn('bc_emergency_centres', 'note')) {
                $table->text('note')->nullable()->after('services');
            }
            if (!Schema::hasColumn('bc_emergency_centres', 'dot_color')) {
                $table->string('dot_color', 100)->nullable()->after('note');
            }
        });
    }

    public function down()
    {
        Schema::table('bc_emergency_centres', function (Blueprint $table) {
            $table->dropColumn(['alias', 'hours', 'services', 'note', 'dot_color']);
        });
    }
};
