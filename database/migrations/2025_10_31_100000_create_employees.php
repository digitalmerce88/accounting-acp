<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('business_id')->index();
            $t->string('emp_code')->nullable()->index();
            $t->string('name');
            $t->string('citizen_id')->nullable();
            $t->date('start_date')->nullable();
            $t->string('position')->nullable();
            $t->decimal('base_salary_decimal', 12, 2)->default(0);
            $t->json('bank_account_json')->nullable();
            $t->string('email')->nullable();
            $t->string('phone')->nullable();
            $t->json('tax_profile_json')->nullable();
            $t->boolean('sso_enabled')->default(true);
            $t->boolean('active')->default(true);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
