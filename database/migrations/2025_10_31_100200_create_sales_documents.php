<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('quotes', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('business_id')->index();
            $t->unsignedBigInteger('customer_id')->nullable()->index();
            $t->date('issue_date');
            $t->string('number')->nullable()->index();
            $t->string('subject')->nullable();
            $t->decimal('subtotal', 12, 2)->default(0);
            $t->decimal('vat_decimal', 12, 2)->default(0);
            $t->decimal('total', 12, 2)->default(0);
            $t->enum('status', ['draft','sent','accepted','void'])->default('draft');
            $t->string('note')->nullable();
            $t->timestamps();
        });
        Schema::create('quote_items', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('quote_id')->index();
            $t->string('name');
            $t->decimal('qty_decimal', 10, 2)->default(1);
            $t->decimal('unit_price_decimal', 12, 2)->default(0);
            $t->decimal('vat_rate_decimal', 5, 2)->default(0);
            $t->timestamps();
        });

        Schema::create('invoices', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('business_id')->index();
            $t->unsignedBigInteger('customer_id')->nullable()->index();
            $t->date('issue_date');
            $t->date('due_date')->nullable();
            $t->string('number')->nullable()->index();
            $t->boolean('is_tax_invoice')->default(false);
            $t->decimal('subtotal', 12, 2)->default(0);
            $t->decimal('vat_decimal', 12, 2)->default(0);
            $t->decimal('total', 12, 2)->default(0);
            $t->enum('status', ['draft','sent','partially_paid','paid','void'])->default('draft');
            $t->string('note')->nullable();
            $t->timestamps();
        });
        Schema::create('invoice_items', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('invoice_id')->index();
            $t->string('name');
            $t->decimal('qty_decimal', 10, 2)->default(1);
            $t->decimal('unit_price_decimal', 12, 2)->default(0);
            $t->decimal('vat_rate_decimal', 5, 2)->default(0);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('quote_items');
        Schema::dropIfExists('quotes');
    }
};
