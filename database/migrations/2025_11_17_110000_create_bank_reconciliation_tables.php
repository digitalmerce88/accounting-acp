<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bank_transactions', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('business_id')->index();
            $t->unsignedBigInteger('bank_account_id')->index();
            $t->date('date');
            $t->decimal('amount_decimal', 12, 2); // positive = deposit, negative = withdrawal
            $t->string('description')->nullable();
            $t->string('reference')->nullable();
            $t->json('raw_payload')->nullable();
            $t->boolean('matched')->default(false)->index();
            $t->timestamps();
            $t->index(['bank_account_id','date']);
        });

        Schema::create('reconciliations', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('business_id')->index();
            $t->unsignedBigInteger('bank_account_id')->index();
            $t->date('period_start');
            $t->date('period_end');
            $t->decimal('statement_balance_decimal', 14, 2)->default(0);
            $t->decimal('calculated_balance_decimal', 14, 2)->default(0);
            $t->decimal('difference_decimal', 14, 2)->default(0);
            $t->enum('status', ['open','in_progress','closed'])->default('open');
            $t->timestamps();
            $t->index(['bank_account_id','period_end']);
        });

        Schema::create('reconciliation_matches', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('reconciliation_id')->index();
            $t->unsignedBigInteger('bank_transaction_id')->index();
            $t->unsignedBigInteger('transaction_id')->nullable()->index(); // internal app transactions.id
            $t->decimal('matched_amount_decimal', 12, 2);
            $t->enum('method', ['auto','manual'])->default('auto');
            $t->timestamps();
            $t->unique(['reconciliation_id','bank_transaction_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reconciliation_matches');
        Schema::dropIfExists('reconciliations');
        Schema::dropIfExists('bank_transactions');
    }
};
