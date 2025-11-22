<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('discount_type')->default('none');
            $table->decimal('discount_value_decimal', 15, 2)->nullable();
            $table->decimal('discount_amount_decimal', 15, 2)->nullable();
            $table->string('deposit_type')->default('none');
            $table->decimal('deposit_value_decimal', 15, 2)->nullable();
            $table->decimal('deposit_amount_decimal', 15, 2)->nullable();
                // multi-currency fields (added later but grouped here for clarity on current schema when fresh install)
                if (!Schema::hasColumn('invoices','currency_code')) {
                    $table->string('currency_code',3)->nullable()->index();
                }
                if (!Schema::hasColumn('invoices','fx_rate_decimal')) {
                    $table->decimal('fx_rate_decimal',18,8)->default(1);
                }
                if (!Schema::hasColumn('invoices','base_total_decimal')) {
                    $table->decimal('base_total_decimal',15,2)->default(0);
                }
        });

        Schema::table('quotes', function (Blueprint $table) {
            $table->string('discount_type')->default('none');
            $table->decimal('discount_value_decimal', 15, 2)->nullable();
            $table->decimal('discount_amount_decimal', 15, 2)->nullable();
            $table->string('deposit_type')->default('none');
            $table->decimal('deposit_value_decimal', 15, 2)->nullable();
            $table->decimal('deposit_amount_decimal', 15, 2)->nullable();
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->string('discount_type')->default('none');
            $table->decimal('discount_value_decimal', 15, 2)->nullable();
            $table->decimal('discount_amount_decimal', 15, 2)->nullable();
            $table->string('deposit_type')->default('none');
            $table->decimal('deposit_value_decimal', 15, 2)->nullable();
            $table->decimal('deposit_amount_decimal', 15, 2)->nullable();
        });

        Schema::table('bills', function (Blueprint $table) {
            $table->string('discount_type')->default('none');
            $table->decimal('discount_value_decimal', 15, 2)->nullable();
            $table->decimal('discount_amount_decimal', 15, 2)->nullable();
            $table->string('deposit_type')->default('none');
            $table->decimal('deposit_value_decimal', 15, 2)->nullable();
            $table->decimal('deposit_amount_decimal', 15, 2)->nullable();
                if (!Schema::hasColumn('bills','currency_code')) {
                    $table->string('currency_code',3)->nullable()->index();
                }
                if (!Schema::hasColumn('bills','fx_rate_decimal')) {
                    $table->decimal('fx_rate_decimal',18,8)->default(1);
                }
                if (!Schema::hasColumn('bills','base_total_decimal')) {
                    $table->decimal('base_total_decimal',15,2)->default(0);
                }
        });
    }

    public function down()
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['discount_type','discount_value_decimal','discount_amount_decimal','deposit_type','deposit_value_decimal','deposit_amount_decimal']);
        });
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn(['discount_type','discount_value_decimal','discount_amount_decimal','deposit_type','deposit_value_decimal','deposit_amount_decimal']);
        });
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn(['discount_type','discount_value_decimal','discount_amount_decimal','deposit_type','deposit_value_decimal','deposit_amount_decimal']);
        });
        Schema::table('bills', function (Blueprint $table) {
            $table->dropColumn(['discount_type','discount_value_decimal','discount_amount_decimal','deposit_type','deposit_value_decimal','deposit_amount_decimal']);
        });
    }
};
