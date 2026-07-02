<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('bc_emergency_hotlines')) {
            return;
        }

        Schema::create('bc_emergency_hotlines', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title')->nullable();
            $table->string('number', 100)->nullable();
            $table->string('description')->nullable();
            $table->string('status', 50)->nullable()->default('publish');
            $table->integer('sort_order')->default(0);
            $table->bigInteger('create_user')->nullable();
            $table->bigInteger('update_user')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('bc_emergency_hotlines');
    }
};
