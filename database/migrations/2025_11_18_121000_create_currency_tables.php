<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if(!Schema::hasTable('currencies')){
            Schema::create('currencies', function(Blueprint $t){
                $t->id();
                $t->string('code',3)->unique();
                $t->string('name');
                $t->unsignedTinyInteger('minor_unit')->default(2); // decimal places
                $t->boolean('is_base')->default(false); // one base currency per business (extend later)
                $t->timestamps();
            });
        }
        if(!Schema::hasTable('exchange_rates')){
            Schema::create('exchange_rates', function(Blueprint $t){
                $t->id();
                $t->string('base_currency',3)->index();
                $t->string('quote_currency',3)->index();
                $t->date('rate_date');
                $t->decimal('rate_decimal',18,8); // number of quote per base (e.g. 1 USD = 36.00000000 THB)
                $t->timestamps();
                $t->unique(['base_currency','quote_currency','rate_date'],'unique_rate');
            });
        }
    }
    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
        Schema::dropIfExists('currencies');
    }
};
