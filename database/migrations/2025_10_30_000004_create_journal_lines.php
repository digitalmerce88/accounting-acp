<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('journal_lines', function(Blueprint $t){
            $t->id();
            $t->foreignId('entry_id')->constrained('journal_entries')->cascadeOnDelete();
            $t->foreignId('account_id')->constrained('accounts');
            $t->decimal('debit',18,2)->default(0); $t->decimal('credit',18,2)->default(0);
            $t->timestamps(); $t->index(['account_id','entry_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('journal_lines'); }
};
