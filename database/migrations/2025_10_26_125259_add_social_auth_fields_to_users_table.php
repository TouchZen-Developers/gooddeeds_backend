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
        Schema::table('users', function (Blueprint $table) {
            $table->string('google_id')->nullable()->after('email');
            $table->string('apple_id')->nullable()->after('google_id');
            $table->string('social_provider')->nullable()->after('apple_id');
            $table->string('social_avatar_url')->nullable()->after('social_provider');
            $table->boolean('is_profile_complete')->default(false)->after('social_avatar_url');
            
            // Add indexes for better performance
            $table->index('google_id');
            $table->index('apple_id');
            $table->index('social_provider');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['google_id']);
            $table->dropIndex(['apple_id']);
            $table->dropIndex(['social_provider']);
            
            $table->dropColumn([
                'google_id',
                'apple_id', 
                'social_provider',
                'social_avatar_url',
                'is_profile_complete'
            ]);
        });
    }
};
