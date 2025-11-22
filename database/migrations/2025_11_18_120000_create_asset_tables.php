<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('asset_categories')) {
            Schema::create('asset_categories', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->index();
                $table->string('name');
                $table->integer('useful_life_months');
                $table->string('depreciation_method')->default('slm');
                $table->timestamps();
            });
        }
        if (!Schema::hasTable('assets')) {
            Schema::create('assets', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->index();
                $table->unsignedBigInteger('category_id')->nullable()->index();
                $table->string('asset_code')->index();
                $table->string('name');
                $table->date('purchase_date');
                $table->decimal('purchase_cost_decimal',15,2);
                $table->decimal('salvage_value_decimal',15,2)->default(0);
                $table->integer('useful_life_months');
                $table->string('depreciation_method')->default('slm');
                $table->date('start_depreciation_date');
                $table->enum('status',[ 'active','disposed' ])->default('active');
                $table->date('disposal_date')->nullable();
                $table->timestamps();
            });
        }
        if (!Schema::hasTable('asset_depreciation_entries')) {
            Schema::create('asset_depreciation_entries', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->index();
                $table->unsignedBigInteger('asset_id')->index();
                $table->integer('period_year');
                $table->integer('period_month');
                $table->decimal('amount_decimal',15,2);
                $table->unsignedBigInteger('posted_journal_entry_id')->nullable()->index();
                $table->timestamps();
                $table->unique(['asset_id','period_year','period_month'],'asset_period_unique');
            });
        }
        if (!Schema::hasTable('asset_disposals')) {
            Schema::create('asset_disposals', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->index();
                $table->unsignedBigInteger('asset_id')->index();
                $table->date('disposal_date');
                $table->decimal('proceed_amount_decimal',15,2)->default(0);
                $table->decimal('gain_loss_decimal',15,2)->default(0);
                $table->unsignedBigInteger('journal_entry_id')->nullable()->index();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_disposals');
        Schema::dropIfExists('asset_depreciation_entries');
        Schema::dropIfExists('assets');
        Schema::dropIfExists('asset_categories');
    }
};
