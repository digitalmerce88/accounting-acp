<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('accounts', function(Blueprint $t){
            $t->id();
            $t->foreignId('business_id')->nullable()->constrained('businesses')->nullOnDelete();
            $t->string('code',50); $t->string('name',150);
            $t->enum('type',[
                'asset','liability','equity','revenue','expense'
            ])->index();
            $t->enum('normal_balance',['debit','credit'])->default('debit');
            $t->timestamps(); $t->unique(['business_id','code']);
        });
    }
    public function down(): void { Schema::dropIfExists('accounts'); }
};
