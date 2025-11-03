<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('beneficiaries', function (Blueprint $table) {
            // Add latitude and longitude as decimal columns
            // These will be used for distance calculations using Haversine formula
            $table->decimal('latitude', 10, 8)->nullable()->after('zip_code');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            
            // Add indexes for performance on location-based queries
            $table->index(['latitude', 'longitude']);
        });

        // Try to add PostGIS geography column if PostGIS is available
        // This provides better performance for spatial queries
        try {
            // Check if PostGIS is available
            $postgisAvailable = DB::select("SELECT EXISTS(SELECT 1 FROM pg_extension WHERE extname = 'postgis')")[0]->exists;
            
            if ($postgisAvailable) {
                // Add PostGIS geography point column
                DB::statement('ALTER TABLE beneficiaries ADD COLUMN location GEOGRAPHY(POINT, 4326)');
                
                // Create spatial index for performance
                DB::statement('CREATE INDEX beneficiaries_location_idx ON beneficiaries USING GIST (location)');
                
                // Create trigger to automatically update PostGIS point when lat/lng changes
                DB::statement("
                    CREATE OR REPLACE FUNCTION update_beneficiary_location()
                    RETURNS TRIGGER AS $$
                    BEGIN
                        IF NEW.latitude IS NOT NULL AND NEW.longitude IS NOT NULL THEN
                            NEW.location = ST_SetSRID(ST_MakePoint(NEW.longitude, NEW.latitude), 4326)::geography;
                        ELSE
                            NEW.location = NULL;
                        END IF;
                        RETURN NEW;
                    END;
                    $$ LANGUAGE plpgsql;
                ");
                
                DB::statement("
                    CREATE TRIGGER trigger_update_beneficiary_location
                    BEFORE INSERT OR UPDATE ON beneficiaries
                    FOR EACH ROW
                    EXECUTE FUNCTION update_beneficiary_location();
                ");
            }
        } catch (\Exception $e) {
            // PostGIS not available, skip - will use Haversine formula instead
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Try to drop PostGIS-related objects if they exist
        try {
            DB::statement('DROP TRIGGER IF EXISTS trigger_update_beneficiary_location ON beneficiaries');
            DB::statement('DROP FUNCTION IF EXISTS update_beneficiary_location()');
            DB::statement('DROP INDEX IF EXISTS beneficiaries_location_idx');
        } catch (\Exception $e) {
            // Ignore if PostGIS objects don't exist
        }
        
        Schema::table('beneficiaries', function (Blueprint $table) {
            // Try to drop PostGIS column if it exists
            try {
                $table->dropColumn('location');
            } catch (\Exception $e) {
                // Ignore if column doesn't exist
            }
            
            // Drop indexes
            $table->dropIndex(['latitude', 'longitude']);
            
            // Drop lat/lng columns
            $table->dropColumn(['latitude', 'longitude']);
        });
    }
};
