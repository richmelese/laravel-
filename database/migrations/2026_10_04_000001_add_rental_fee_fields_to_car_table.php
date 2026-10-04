<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The React admin's Car edit form has standalone "Service fee" and
     * "Buyer fee" controls (amount + percent/fixed type), separate from the
     * legacy itemized `service_fee` JSON column. The backend never had
     * columns to persist those scalar values, so they always saved as
     * nothing and therefore never re-populated the form on edit.
     */
    public function up()
    {
        Schema::table('bc_cars', function (Blueprint $table) {
            if (!Schema::hasColumn('bc_cars', 'service_fee_amount')) {
                $table->decimal('service_fee_amount', 12, 2)->nullable()->after('service_fee');
            }
            if (!Schema::hasColumn('bc_cars', 'service_fee_type')) {
                $table->string('service_fee_type', 20)->nullable()->default('percent')->after('service_fee_amount');
            }
            if (!Schema::hasColumn('bc_cars', 'enable_buyer_fee')) {
                $table->tinyInteger('enable_buyer_fee')->nullable()->after('service_fee_type');
            }
            if (!Schema::hasColumn('bc_cars', 'buyer_fee_amount')) {
                $table->decimal('buyer_fee_amount', 12, 2)->nullable()->after('enable_buyer_fee');
            }
            if (!Schema::hasColumn('bc_cars', 'buyer_fee_type')) {
                $table->string('buyer_fee_type', 20)->nullable()->default('percent')->after('buyer_fee_amount');
            }
        });
    }

    public function down()
    {
        Schema::table('bc_cars', function (Blueprint $table) {
            foreach (['service_fee_amount', 'service_fee_type', 'enable_buyer_fee', 'buyer_fee_amount', 'buyer_fee_type'] as $col) {
                if (Schema::hasColumn('bc_cars', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
