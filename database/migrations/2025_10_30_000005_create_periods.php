<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('periods', function(Blueprint $t){
            $t->id();
            $t->foreignId('business_id')->nullable()->constrained('businesses')->nullOnDelete();
            $t->unsignedTinyInteger('month'); $t->unsignedSmallInteger('year');
            $t->enum('status',['open','locked'])->default('open');
            $t->timestamps(); $t->unique(['business_id','month','year']);
        });
    }
    public function down(): void { Schema::dropIfExists('periods'); }
};
