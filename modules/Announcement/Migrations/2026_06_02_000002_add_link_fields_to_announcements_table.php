<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('bc_announcements')) {
            return;
        }

        Schema::table('bc_announcements', function (Blueprint $table) {
            if (!Schema::hasColumn('bc_announcements', 'url')) {
                $table->string('url')->nullable()->after('end_date');
            }
            if (!Schema::hasColumn('bc_announcements', 'button_label')) {
                $table->string('button_label', 100)->nullable()->after('url');
            }
        });
    }

    public function down()
    {
        if (!Schema::hasTable('bc_announcements')) {
            return;
        }

        Schema::table('bc_announcements', function (Blueprint $table) {
            if (Schema::hasColumn('bc_announcements', 'button_label')) {
                $table->dropColumn('button_label');
            }
            if (Schema::hasColumn('bc_announcements', 'url')) {
                $table->dropColumn('url');
            }
        });
    }
};

