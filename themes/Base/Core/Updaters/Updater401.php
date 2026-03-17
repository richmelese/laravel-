<?php

namespace Themes\Base\Core\Updaters;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Updater401
{


    public static function run()
    {
        $version = '1.0.1';
        if (version_compare(setting_item('update_to_401'), $version, '>=')) return;

        Artisan::call('migrate', [
            '--force' => true,
        ]);

        if(Schema::hasTable('bc_hotels'))
        {
            Schema::table('bc_hotels', function (Blueprint $table) {
                if (!Schema::hasColumn('bc_hotels', 'phone')) {
                    $table->string('phone')->nullable();
                    $table->string('website')->nullable();
                }
            });
        }
        if (Schema::hasTable('bc_contact')) {
            Schema::table('bc_contact', function (Blueprint $table) {
                if (!Schema::hasColumn('bc_contact', 'phone')) {
                    $table->string('phone')->nullable();
                }
            });
        }

        // Run Update
        Artisan::call('cache:clear');

        setting_update_item('update_to_401', $version);
    }
}
