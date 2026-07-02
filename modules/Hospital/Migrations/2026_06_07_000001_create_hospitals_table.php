<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('bc_hospitals')) {
            Schema::create('bc_hospitals', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name', 255);
                $table->text('description')->nullable();
                $table->string('address', 255)->nullable();
                $table->string('city', 100)->nullable();
                $table->string('country', 100)->nullable();
                $table->string('phone', 50)->nullable();
                $table->string('email', 255)->nullable();
                $table->string('website', 255)->nullable();
                $table->decimal('latitude', 10, 7)->nullable();
                $table->decimal('longitude', 10, 7)->nullable();
                $table->unsignedBigInteger('image_id')->nullable();
                // publish | draft
                $table->string('status', 50)->default('publish');
                // The user who owns/manages this hospital (primary vendor)
                $table->unsignedBigInteger('author_id')->nullable();
                $table->integer('create_user')->nullable();
                $table->integer('update_user')->nullable();
                $table->softDeletes();
                $table->timestamps();

                $table->index('status');
                $table->index('author_id');
            });
        }

        if (!Schema::hasTable('bc_hospital_staff')) {
            Schema::create('bc_hospital_staff', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('hospital_id');
                $table->unsignedBigInteger('user_id');
                // owner | manager | staff
                $table->string('role', 50)->default('staff');
                $table->timestamps();

                $table->unique(['hospital_id', 'user_id']);
                $table->index('hospital_id');
                $table->index('user_id');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('bc_hospital_staff');
        Schema::dropIfExists('bc_hospitals');
    }
};
