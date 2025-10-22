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
        // Delete all existing OTPs before changing column length
        \DB::table('otps')->delete();
        
        Schema::table('otps', function (Blueprint $table) {
            $table->string('otp', 4)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('otps', function (Blueprint $table) {
            $table->string('otp', 6)->change();
        });
    }
};
