<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('bc_medical_bookings')) {
            return;
        }

        Schema::create('bc_medical_bookings', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Patient info
            $table->string('name', 255)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('nationality', 100)->nullable();
            $table->string('passport_number', 100)->nullable();
            $table->date('date_of_birth')->nullable();

            // Request details
            $table->string('request_type', 100)->nullable(); // medical_support | urgent_assistance | medical_centre_contact | travel_emergency
            $table->string('priority', 50)->nullable()->default('normal'); // normal | urgent | emergency
            $table->text('description')->nullable();
            $table->date('preferred_date')->nullable();
            $table->string('preferred_time', 50)->nullable();
            $table->string('location', 255)->nullable();
            $table->string('medical_centre_name', 255)->nullable();

            // Admin fields
            $table->string('status', 50)->nullable()->default('pending'); // pending | confirmed | in_progress | completed | cancelled
            $table->text('admin_note')->nullable();

            $table->integer('create_user')->nullable();
            $table->integer('update_user')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('status');
            $table->index('request_type');
            $table->index('priority');
        });
    }

    public function down()
    {
        Schema::dropIfExists('bc_medical_bookings');
    }
};
