<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->index();
            $table->string('name');
            $table->string('tax_id')->nullable();
            $table->string('branch_no')->nullable();
            $table->text('address_text')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->enum('wht_type', ['none','3','53'])->default('none');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};
