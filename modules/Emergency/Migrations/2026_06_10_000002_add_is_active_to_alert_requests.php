<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('bc_alert_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('bc_alert_requests', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('status');
                $table->index('is_active');
            }
        });
    }

    public function down()
    {
        Schema::table('bc_alert_requests', function (Blueprint $table) {
            if (Schema::hasColumn('bc_alert_requests', 'is_active')) {
                $table->dropIndex(['is_active']);
                $table->dropColumn('is_active');
            }
        });
    }
};
