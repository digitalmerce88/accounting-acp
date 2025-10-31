<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('company_profiles')) {
            Schema::create('company_profiles', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('business_id')->index();
                $t->string('name');
                $t->string('tax_id')->nullable();
                $t->string('phone')->nullable();
                $t->string('email')->nullable();
                $t->string('address_line1')->nullable();
                $t->string('address_line2')->nullable();
                $t->string('province')->nullable();
                $t->string('postcode')->nullable();
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('company_profiles');
    }
};
