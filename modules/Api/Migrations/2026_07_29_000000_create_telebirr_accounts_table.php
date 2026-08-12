<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telebirr_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('open_id', 191)->unique();
            $table->string('identity_id', 191)->nullable()->index();
            $table->string('identity_type', 50)->nullable();
            $table->string('wallet_identity_id', 191)->nullable();
            $table->string('identifier', 191)->nullable();
            $table->string('nickname')->nullable();
            $table->string('status', 50)->nullable();
            $table->json('profile')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telebirr_accounts');
    }
};
