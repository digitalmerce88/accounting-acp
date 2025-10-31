<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('company_profiles') && !Schema::hasColumn('company_profiles','logo_path')) {
            Schema::table('company_profiles', function (Blueprint $t) {
                $t->string('logo_path')->nullable()->after('postcode');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('company_profiles') && Schema::hasColumn('company_profiles','logo_path')) {
            Schema::table('company_profiles', function (Blueprint $t) {
                $t->dropColumn('logo_path');
            });
        }
    }
};
