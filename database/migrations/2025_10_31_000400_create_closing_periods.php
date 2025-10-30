<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('closing_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->unsignedSmallInteger('period_month');
            $table->unsignedSmallInteger('period_year');
            $table->timestamp('closed_at')->nullable();
            $table->string('note', 255)->nullable();
            $table->timestamps();
            $table->unique(['business_id','period_month','period_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('closing_periods');
    }
};
