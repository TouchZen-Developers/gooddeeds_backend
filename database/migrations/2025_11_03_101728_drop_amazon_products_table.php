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
        Schema::dropIfExists('amazon_products');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('amazon_products', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });
    }
};
