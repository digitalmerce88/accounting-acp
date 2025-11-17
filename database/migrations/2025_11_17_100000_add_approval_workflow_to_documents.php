<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        foreach (['quotes','invoices','purchase_orders','bills'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->enum('approval_status', ['draft','submitted','approved','locked'])->default('draft')->after('status');
                $t->unsignedBigInteger('submitted_by')->nullable()->after('approval_status');
                $t->timestamp('submitted_at')->nullable()->after('submitted_by');
                $t->unsignedBigInteger('approved_by')->nullable()->after('submitted_at');
                $t->timestamp('approved_at')->nullable()->after('approved_by');
                $t->unsignedBigInteger('locked_by')->nullable()->after('approved_at');
                $t->timestamp('locked_at')->nullable()->after('locked_by');
            });
        }

        Schema::create('approval_logs', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('business_id')->index();
            $t->unsignedBigInteger('user_id')->nullable()->index();
            $t->string('model_type');
            $t->unsignedBigInteger('model_id');
            $t->enum('action', ['submitted','approved','locked','unlocked','rejected','commented']);
            $t->text('comment')->nullable();
            $t->timestamps();
            $t->index(['model_type','model_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_logs');
        foreach (['quotes','invoices','purchase_orders','bills'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropColumn(['approval_status','submitted_by','submitted_at','approved_by','approved_at','locked_by','locked_at']);
            });
        }
    }
};
