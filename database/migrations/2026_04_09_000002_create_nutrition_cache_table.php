<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nutrition_cache', function (Blueprint $table) {
            $table->id();
            $table->string('lookup_key')->unique();
            $table->string('source'); // 'usda' or 'openfoodfacts'
            $table->string('product_name')->nullable();
            $table->json('nutrition');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nutrition_cache');
    }
};
