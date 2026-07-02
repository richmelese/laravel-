<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddListingFieldsToBcBusesTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('bc_buses')) {
            return;
        }

        Schema::table('bc_buses', function (Blueprint $table) {
            if (!Schema::hasColumn('bc_buses', 'slug')) {
                $table->string('slug')->nullable()->index();
            }
            if (!Schema::hasColumn('bc_buses', 'sale_price')) {
                $table->decimal('sale_price', 12, 2)->nullable();
            }
            if (!Schema::hasColumn('bc_buses', 'currency')) {
                $table->string('currency', 10)->nullable();
            }
            if (!Schema::hasColumn('bc_buses', 'location_id')) {
                $table->unsignedBigInteger('location_id')->nullable();
            }
            if (!Schema::hasColumn('bc_buses', 'address')) {
                $table->string('address')->nullable();
            }
            if (!Schema::hasColumn('bc_buses', 'map_lat')) {
                $table->string('map_lat', 20)->nullable();
            }
            if (!Schema::hasColumn('bc_buses', 'map_lng')) {
                $table->string('map_lng', 20)->nullable();
            }
            if (!Schema::hasColumn('bc_buses', 'map_zoom')) {
                $table->integer('map_zoom')->default(8)->nullable();
            }
            if (!Schema::hasColumn('bc_buses', 'banner_image_id')) {
                $table->unsignedBigInteger('banner_image_id')->nullable();
            }
            if (!Schema::hasColumn('bc_buses', 'video')) {
                $table->string('video', 500)->nullable();
            }
            if (!Schema::hasColumn('bc_buses', 'seo_title')) {
                $table->string('seo_title')->nullable();
            }
            if (!Schema::hasColumn('bc_buses', 'seo_desc')) {
                $table->text('seo_desc')->nullable();
            }
            if (!Schema::hasColumn('bc_buses', 'title_ja')) {
                $table->string('title_ja')->nullable();
            }
            if (!Schema::hasColumn('bc_buses', 'content_ja')) {
                $table->text('content_ja')->nullable();
            }
            if (!Schema::hasColumn('bc_buses', 'title_eg')) {
                $table->string('title_eg')->nullable();
            }
            if (!Schema::hasColumn('bc_buses', 'content_eg')) {
                $table->text('content_eg')->nullable();
            }
            if (!Schema::hasColumn('bc_buses', 'is_featured')) {
                $table->tinyInteger('is_featured')->default(0)->nullable();
            }
            if (!Schema::hasColumn('bc_buses', 'author_id')) {
                $table->unsignedBigInteger('author_id')->nullable();
            }
            if (!Schema::hasColumn('bc_buses', 'default_state')) {
                $table->tinyInteger('default_state')->default(1)->nullable();
            }
            if (!Schema::hasColumn('bc_buses', 'ical_import_url')) {
                $table->string('ical_import_url', 500)->nullable();
            }
            if (!Schema::hasColumn('bc_buses', 'min_day_before_booking')) {
                $table->integer('min_day_before_booking')->nullable();
            }
            if (!Schema::hasColumn('bc_buses', 'min_day_stays')) {
                $table->integer('min_day_stays')->nullable();
            }
            if (!Schema::hasColumn('bc_buses', 'start_time')) {
                $table->string('start_time', 50)->nullable();
            }
            if (!Schema::hasColumn('bc_buses', 'end_time')) {
                $table->string('end_time', 50)->nullable();
            }
            if (!Schema::hasColumn('bc_buses', 'duration_hour')) {
                $table->integer('duration_hour')->nullable();
            }
            if (!Schema::hasColumn('bc_buses', 'faqs')) {
                $table->json('faqs')->nullable();
            }
            if (!Schema::hasColumn('bc_buses', 'facility_labels')) {
                $table->json('facility_labels')->nullable();
            }
            if (!Schema::hasColumn('bc_buses', 'terms')) {
                $table->json('terms')->nullable();
            }
            if (!Schema::hasColumn('bc_buses', 'baggage')) {
                $table->string('baggage', 100)->nullable();
            }
            if (!Schema::hasColumn('bc_buses', 'door')) {
                $table->string('door', 50)->nullable();
            }
            if (!Schema::hasColumn('bc_buses', 'gear_shift')) {
                $table->string('gear_shift', 100)->nullable();
            }
        });
    }

    public function down()
    {
        if (!Schema::hasTable('bc_buses')) {
            return;
        }

        Schema::table('bc_buses', function (Blueprint $table) {
            foreach ([
                'slug', 'sale_price', 'currency', 'location_id', 'address', 'map_lat', 'map_lng', 'map_zoom',
                'banner_image_id', 'video', 'seo_title', 'seo_desc', 'title_ja', 'content_ja', 'title_eg',
                'content_eg', 'is_featured', 'author_id', 'default_state', 'ical_import_url',
                'min_day_before_booking', 'min_day_stays', 'start_time', 'end_time', 'duration_hour',
                'faqs', 'facility_labels', 'terms', 'baggage', 'door', 'gear_shift',
            ] as $column) {
                if (Schema::hasColumn('bc_buses', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}
