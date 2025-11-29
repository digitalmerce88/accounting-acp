<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('payroll_runs', 'posting_entry_id')) {
            Schema::table('payroll_runs', function (Blueprint $table) {
                $table->unsignedBigInteger('posting_entry_id')->nullable()->after('processed_at');
            });
        }

        if (! Schema::hasColumn('invoices', 'posting_entry_id')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->unsignedBigInteger('posting_entry_id')->nullable()->after('status');
            });
        }

        if (! Schema::hasColumn('bills', 'posting_entry_id')) {
            Schema::table('bills', function (Blueprint $table) {
                $table->unsignedBigInteger('posting_entry_id')->nullable()->after('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('payroll_runs', 'posting_entry_id')) {
            Schema::table('payroll_runs', function (Blueprint $table) {
                $table->dropColumn('posting_entry_id');
            });
        }
        if (Schema::hasColumn('invoices', 'posting_entry_id')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropColumn('posting_entry_id');
            });
        }
        if (Schema::hasColumn('bills', 'posting_entry_id')) {
            Schema::table('bills', function (Blueprint $table) {
                $table->dropColumn('posting_entry_id');
            });
        }
    }
};
