<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->index();
            $table->enum('kind', ['income','expense']);
            $table->date('date');
            $table->string('memo')->nullable();
            $table->decimal('amount', 12, 2);
            $table->decimal('vat', 12, 2)->default(0);
            $table->decimal('wht', 12, 2)->default(0);
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('vendor_id')->nullable();
            $table->enum('payment_method', ['cash','bank','card','wallet','transfer'])->default('bank');
            $table->unsignedBigInteger('bank_account_id')->nullable();
            $table->enum('price_input_mode', ['gross','net','novat'])->default('gross');
            $table->boolean('vat_applicable')->default(false);
            $table->decimal('wht_rate', 5, 4)->default(0); // e.g., 0.0300
            $table->enum('status', ['draft','posted'])->default('posted');
            $table->unsignedBigInteger('journal_entry_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
