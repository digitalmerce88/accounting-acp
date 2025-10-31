<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('customers')) {
        Schema::create('customers', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('business_id')->index();
            $t->string('name');
            $t->string('tax_id')->nullable()->index();
            $t->string('national_id')->nullable()->index();
            $t->string('phone')->nullable()->index();
            $t->string('email')->nullable();
            $t->text('address')->nullable();
            $t->timestamps();
        });
        }

        if (!Schema::hasTable('vendors')) {
        Schema::create('vendors', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('business_id')->index();
            $t->string('name');
            $t->string('tax_id')->nullable()->index();
            $t->string('national_id')->nullable()->index();
            $t->string('phone')->nullable()->index();
            $t->string('email')->nullable();
            $t->text('address')->nullable();
            $t->timestamps();
        });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vendors');
        Schema::dropIfExists('customers');
    }
};
