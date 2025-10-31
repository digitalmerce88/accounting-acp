<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pay_components', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('business_id')->index();
            $t->string('code')->index();
            $t->string('name');
            $t->enum('type', ['earning','deduction']);
            $t->decimal('default_amount_decimal', 12, 2)->default(0);
            $t->boolean('taxable')->default(true);
            $t->boolean('sso_applicable')->default(true);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pay_components');
    }
};
