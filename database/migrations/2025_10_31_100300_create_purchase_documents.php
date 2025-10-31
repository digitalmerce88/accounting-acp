<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('business_id')->index();
            $t->unsignedBigInteger('vendor_id')->nullable()->index();
            $t->date('issue_date');
            $t->string('number')->nullable()->index();
            $t->decimal('subtotal', 12, 2)->default(0);
            $t->decimal('vat_decimal', 12, 2)->default(0);
            $t->decimal('total', 12, 2)->default(0);
            $t->enum('status', ['draft','sent','confirmed','received','void'])->default('draft');
            $t->string('note')->nullable();
            $t->timestamps();
        });
        Schema::create('po_items', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('purchase_order_id')->index();
            $t->string('name');
            $t->decimal('qty_decimal', 10, 2)->default(1);
            $t->decimal('unit_price_decimal', 12, 2)->default(0);
            $t->decimal('vat_rate_decimal', 5, 2)->default(0);
            $t->timestamps();
        });

        Schema::create('bills', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('business_id')->index();
            $t->unsignedBigInteger('vendor_id')->nullable()->index();
            $t->date('bill_date');
            $t->date('due_date')->nullable();
            $t->string('number')->nullable()->index();
            $t->decimal('subtotal', 12, 2)->default(0);
            $t->decimal('vat_decimal', 12, 2)->default(0);
            $t->decimal('total', 12, 2)->default(0);
            $t->decimal('wht_rate_decimal', 5, 2)->default(0);
            $t->decimal('wht_amount_decimal', 12, 2)->default(0);
            $t->enum('status', ['draft','approved','partially_paid','paid','void'])->default('draft');
            $t->string('note')->nullable();
            $t->timestamps();
        });
        Schema::create('bill_items', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('bill_id')->index();
            $t->string('name');
            $t->decimal('qty_decimal', 10, 2)->default(1);
            $t->decimal('unit_price_decimal', 12, 2)->default(0);
            $t->decimal('vat_rate_decimal', 5, 2)->default(0);
            $t->timestamps();
        });

        Schema::create('wht_certificates', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('business_id')->index();
            $t->unsignedBigInteger('vendor_id')->nullable()->index();
            $t->unsignedSmallInteger('period_month');
            $t->unsignedSmallInteger('period_year');
            $t->decimal('total_paid', 12, 2)->default(0);
            $t->decimal('wht_rate_decimal', 5, 2)->default(0);
            $t->decimal('wht_amount', 12, 2)->default(0);
            $t->enum('form_type', ['3','53'])->default('3');
            $t->string('number')->nullable()->index();
            $t->date('issued_at')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wht_certificates');
        Schema::dropIfExists('bill_items');
        Schema::dropIfExists('bills');
        Schema::dropIfExists('po_items');
        Schema::dropIfExists('purchase_orders');
    }
};
