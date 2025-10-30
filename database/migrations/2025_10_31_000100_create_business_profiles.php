<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('business_profiles', function (Blueprint $table) {
            $table->id();
            // Link to existing businesses table to avoid duplication
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('tax_id', 32)->nullable();
            $table->text('address_text')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email', 150)->nullable();
            $table->date('vat_registered_at')->nullable();
            $table->timestamps();
            $table->unique('business_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_profiles');
    }
};
