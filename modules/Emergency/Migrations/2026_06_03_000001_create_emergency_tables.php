<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('bc_emergency_numbers')) {
            Schema::create('bc_emergency_numbers', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('label')->nullable();
                $table->string('number', 100)->nullable();
                $table->string('status', 50)->nullable()->default('publish');
                $table->integer('sort_order')->default(0);
                $table->bigInteger('create_user')->nullable();
                $table->bigInteger('update_user')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('bc_emergency_centres')) {
            Schema::create('bc_emergency_centres', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name')->nullable();
                $table->string('type', 100)->nullable();
                $table->string('address')->nullable();
                $table->string('phone', 100)->nullable();
                $table->string('status', 50)->nullable()->default('publish');
                $table->integer('sort_order')->default(0);
                $table->bigInteger('create_user')->nullable();
                $table->bigInteger('update_user')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('bc_emergency_covid_health')) {
            Schema::create('bc_emergency_covid_health', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('title')->nullable();
                $table->json('points')->nullable();
                $table->string('status', 50)->nullable()->default('publish');
                $table->integer('sort_order')->default(0);
                $table->bigInteger('create_user')->nullable();
                $table->bigInteger('update_user')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('bc_emergency_travel_support')) {
            Schema::create('bc_emergency_travel_support', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('scenario')->nullable();
                $table->json('steps')->nullable();
                $table->string('status', 50)->nullable()->default('publish');
                $table->integer('sort_order')->default(0);
                $table->bigInteger('create_user')->nullable();
                $table->bigInteger('update_user')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('bc_emergency_contacts')) {
            Schema::create('bc_emergency_contacts', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('label')->nullable();
                $table->string('value')->nullable();
                $table->string('href')->nullable();
                $table->string('status', 50)->nullable()->default('publish');
                $table->integer('sort_order')->default(0);
                $table->bigInteger('create_user')->nullable();
                $table->bigInteger('update_user')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('bc_emergency_contacts');
        Schema::dropIfExists('bc_emergency_travel_support');
        Schema::dropIfExists('bc_emergency_covid_health');
        Schema::dropIfExists('bc_emergency_centres');
        Schema::dropIfExists('bc_emergency_numbers');
    }
};
