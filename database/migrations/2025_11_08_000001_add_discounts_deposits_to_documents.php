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
