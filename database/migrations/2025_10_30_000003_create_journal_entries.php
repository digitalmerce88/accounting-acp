<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('journal_entries', function(Blueprint $t){
            $t->id();
            $t->foreignId('business_id')->nullable()->constrained('businesses')->nullOnDelete();
            $t->date('date')->index(); $t->string('memo',255)->nullable();
            $t->enum('status',['draft','posted'])->default('draft')->index();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('journal_entries'); }
};
