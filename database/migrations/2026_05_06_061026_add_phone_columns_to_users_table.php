<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();

            $table->string('phone')->nullable()->unique()->after('email');
            $table->timestamp('phone_verified_at')->nullable()->after('phone');
        });
    }

    /**
     * Reverse the migration.
     *
     * Note: email is NOT restored to NOT NULL because phone-only rows (email = null)
     * may exist after this migration runs. Restoring the NOT NULL constraint would
     * violate referential integrity for those rows. The conservative approach is to
     * leave email nullable and drop only the phone columns.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'phone_verified_at']);
        });
    }
};
