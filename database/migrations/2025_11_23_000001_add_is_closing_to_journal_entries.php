<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('journal_entries', 'is_closing')) {
            Schema::table('journal_entries', function (Blueprint $table) {
                $table->boolean('is_closing')->default(false)->after('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('journal_entries', 'is_closing')) {
            Schema::table('journal_entries', function (Blueprint $table) {
                $table->dropColumn('is_closing');
            });
        }
    }
};
