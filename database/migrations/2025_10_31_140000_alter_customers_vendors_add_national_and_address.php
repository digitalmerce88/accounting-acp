<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('customers')) {
            Schema::table('customers', function (Blueprint $t) {
                if (!Schema::hasColumn('customers','national_id')) {
                    $t->string('national_id')->nullable()->index()->after('tax_id');
                }
                if (!Schema::hasColumn('customers','address')) {
                    $t->text('address')->nullable()->after('phone');
                }
                if (Schema::hasColumn('customers','phone')) {
                    // Ensure index on phone for quick search (ignore if already indexed)
                    try { $t->index('phone'); } catch (\Throwable $e) { /* ignore */ }
                }
                if (Schema::hasColumn('customers','tax_id')) {
                    try { $t->index('tax_id'); } catch (\Throwable $e) { /* ignore */ }
                }
            });
        }

        if (Schema::hasTable('vendors')) {
            Schema::table('vendors', function (Blueprint $t) {
                if (!Schema::hasColumn('vendors','national_id')) {
                    $t->string('national_id')->nullable()->index()->after('tax_id');
                }
                if (!Schema::hasColumn('vendors','address')) {
                    $t->text('address')->nullable()->after('phone');
                }
                if (Schema::hasColumn('vendors','phone')) {
                    try { $t->index('phone'); } catch (\Throwable $e) { /* ignore */ }
                }
                if (Schema::hasColumn('vendors','tax_id')) {
                    try { $t->index('tax_id'); } catch (\Throwable $e) { /* ignore */ }
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('customers')) {
            Schema::table('customers', function (Blueprint $t) {
                if (Schema::hasColumn('customers','national_id')) {
                    $t->dropColumn('national_id');
                }
                if (Schema::hasColumn('customers','address')) {
                    $t->dropColumn('address');
                }
            });
        }
        if (Schema::hasTable('vendors')) {
            Schema::table('vendors', function (Blueprint $t) {
                if (Schema::hasColumn('vendors','national_id')) {
                    $t->dropColumn('national_id');
                }
                if (Schema::hasColumn('vendors','address')) {
                    $t->dropColumn('address');
                }
            });
        }
    }
};
