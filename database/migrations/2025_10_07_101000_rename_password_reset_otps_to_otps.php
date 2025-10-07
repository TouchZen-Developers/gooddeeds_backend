<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('password_reset_otps') && !Schema::hasTable('otps')) {
            Schema::rename('password_reset_otps', 'otps');
        }

        Schema::table('otps', function (Blueprint $table) {
            if (!Schema::hasColumn('otps', 'context')) {
                $table->string('context')->default('password_reset')->after('email');
            }
            if (!Schema::hasColumn('otps', 'metadata')) {
                $table->json('metadata')->nullable()->after('context');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('otps')) {
            Schema::table('otps', function (Blueprint $table) {
                if (Schema::hasColumn('otps', 'metadata')) {
                    $table->dropColumn('metadata');
                }
                if (Schema::hasColumn('otps', 'context')) {
                    $table->dropColumn('context');
                }
            });

            if (!Schema::hasTable('password_reset_otps')) {
                Schema::rename('otps', 'password_reset_otps');
            }
        }
    }
};
