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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            
            // URL and Provider Information
            $table->string('url')->unique(); // Original product URL
            $table->string('provider'); // amazon, ebay, walmart, etc.
            $table->string('external_id')->nullable(); // ASIN, eBay Item ID, etc.
            $table->string('domain')->nullable(); // amazon.com, ebay.com, etc.
            
            // Generic Product Details
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->string('currency', 3)->nullable(); // USD, GBP, EUR
            $table->string('image_url')->nullable();
            $table->json('features')->nullable(); // Product bullet points
            $table->json('specifications')->nullable(); // Technical specs
            $table->string('availability')->nullable(); // In Stock, Out of Stock
            $table->decimal('rating', 3, 2)->nullable(); // Customer rating
            $table->integer('review_count')->nullable();
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            
            // Relationships & Status
            $table->foreignId('category_id')->nullable()->constrained('categories')->onDelete('set null');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->json('raw_data')->nullable(); // Full API response
            
            $table->timestamps();
            
            // Indexes for better performance
            $table->index(['provider', 'external_id']);
            $table->index(['category_id', 'is_active']);
            $table->index(['is_featured', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
