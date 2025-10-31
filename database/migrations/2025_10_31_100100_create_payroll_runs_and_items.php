<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payroll_runs', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('business_id')->index();
            $t->unsignedSmallInteger('period_month');
            $t->unsignedSmallInteger('period_year');
            $t->enum('status', ['draft','locked','paid'])->default('draft');
            $t->timestamp('processed_at')->nullable();
            $t->string('note')->nullable();
            $t->timestamps();
        });

        Schema::create('payroll_items', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('payroll_run_id')->index();
            $t->unsignedBigInteger('employee_id')->index();
            $t->decimal('earning_basic_decimal', 12, 2)->default(0);
            $t->decimal('earning_other_decimal', 12, 2)->default(0);
            $t->decimal('sso_employee_decimal', 12, 2)->default(0);
            $t->decimal('sso_employer_decimal', 12, 2)->default(0);
            $t->decimal('wht_decimal', 12, 2)->default(0);
            $t->decimal('net_pay_decimal', 12, 2)->default(0);
            $t->json('meta_json')->nullable();
            $t->string('note')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_items');
        Schema::dropIfExists('payroll_runs');
    }
};
