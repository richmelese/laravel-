<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Enable guest checkout so visitors can book without creating an account first.
        DB::table('core_settings')->updateOrInsert(
            ['name' => 'booking_guest_checkout'],
            ['val'  => '1']
        );
    }

    public function down()
    {
        DB::table('core_settings')
            ->where('name', 'booking_guest_checkout')
            ->update(['val' => '0']);
    }
};
