<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BusesImageIdGalleryIds extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('bc_buses')) {
            return;
        }

        Schema::table('bc_buses', function (Blueprint $table) {
            if (!Schema::hasColumn('bc_buses', 'image_id') && Schema::hasColumn('bc_buses', 'image_url')) {
                $table->unsignedBigInteger('image_id')->nullable()->after('price');
            } elseif (!Schema::hasColumn('bc_buses', 'image_id')) {
                $table->unsignedBigInteger('image_id')->nullable()->after('price');
            }
        });

        if (Schema::hasColumn('bc_buses', 'image_url')) {
            Schema::table('bc_buses', function (Blueprint $table) {
                $table->dropColumn('image_url');
            });
        }

        if (Schema::hasColumn('bc_buses', 'gallery')) {
            $driver = Schema::getConnection()->getDriverName();
            if ($driver === 'mysql' || $driver === 'mariadb') {
                try {
                    DB::statement('ALTER TABLE bc_buses MODIFY gallery JSON NULL');
                } catch (\Throwable) {
                    // keep existing column type; app stores JSON in text
                }
            }
        }
    }

    public function down()
    {
        if (!Schema::hasTable('bc_buses')) {
            return;
        }

        Schema::table('bc_buses', function (Blueprint $table) {
            if (Schema::hasColumn('bc_buses', 'image_id')) {
                $table->string('image_url', 500)->nullable()->after('price');
            }
        });

        if (Schema::hasColumn('bc_buses', 'image_id')) {
            Schema::table('bc_buses', function (Blueprint $table) {
                $table->dropColumn('image_id');
            });
        }
    }
}
