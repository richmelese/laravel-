<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('bc_medical_bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bc_medical_bookings', 'booking_code')) {
                $table->string('booking_code', 50)->nullable()->unique()->after('id');
            }
            if (!Schema::hasColumn('bc_medical_bookings', 'fee')) {
                $table->decimal('fee', 10, 2)->nullable()->default(0)->after('admin_note');
            }
            if (!Schema::hasColumn('bc_medical_bookings', 'currency')) {
                $table->string('currency', 10)->nullable()->default('USD')->after('fee');
            }
            if (!Schema::hasColumn('bc_medical_bookings', 'payment_status')) {
                // unpaid | pending | paid | refunded | failed
                $table->string('payment_status', 50)->nullable()->default('unpaid')->after('currency');
            }
            if (!Schema::hasColumn('bc_medical_bookings', 'payment_gateway')) {
                $table->string('payment_gateway', 100)->nullable()->after('payment_status');
            }
            if (!Schema::hasColumn('bc_medical_bookings', 'payment_reference')) {
                $table->string('payment_reference', 255)->nullable()->after('payment_gateway');
            }
            if (!Schema::hasColumn('bc_medical_bookings', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('payment_reference');
            }
        });
    }

    public function down()
    {
        Schema::table('bc_medical_bookings', function (Blueprint $table) {
            $table->dropColumn([
                'booking_code', 'fee', 'currency',
                'payment_status', 'payment_gateway', 'payment_reference', 'paid_at',
            ]);
        });
    }
};
