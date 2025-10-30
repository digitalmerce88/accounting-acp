<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('businesses', function(Blueprint $t){
            $t->id(); $t->string('name',150); $t->string('country',2)->default('TH'); $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('businesses'); }
};
