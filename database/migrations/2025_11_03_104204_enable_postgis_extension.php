<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Try to enable PostGIS extension if available
        // Skip if PostGIS is not installed
        try {
            DB::statement('CREATE EXTENSION IF NOT EXISTS postgis');
        } catch (\Exception $e) {
            // PostGIS not available, skip - will use Haversine formula instead
            // You can install PostGIS later for better performance
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Do not drop PostGIS extension as it might be used by other tables
        // DB::statement('DROP EXTENSION IF EXISTS postgis');
    }
};
