<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('bc_alert_requests')) return;

        Schema::create('bc_alert_requests', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Who sent it
            $table->unsignedBigInteger('user_id')->nullable();   // logged-in user
            $table->string('booking_code', 50)->nullable();       // existing booking reference

            // Contact info (auto-filled for logged-in users, manual for guests)
            $table->string('name', 255)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('phone', 50)->nullable();

            // Alert details
            // sos | help | medical | general
            $table->string('alert_type', 50)->nullable()->default('general');
            $table->text('message')->nullable();

            // Location
            $table->string('location', 255)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            // Admin management
            // new | acknowledged | in_progress | resolved | dismissed
            $table->string('status', 50)->nullable()->default('new');
            $table->text('admin_note')->nullable();
            $table->timestamp('responded_at')->nullable();

            $table->integer('create_user')->nullable();
            $table->integer('update_user')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('status');
            $table->index('alert_type');
            $table->index('user_id');
            $table->index('booking_code');
        });
    }

    public function down()
    {
        Schema::dropIfExists('bc_alert_requests');
    }
};
