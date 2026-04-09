<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->decimal('calories_per_serving', 8, 2)->nullable()->after('input_ingredients');
            $table->decimal('protein_per_serving', 8, 2)->nullable()->after('calories_per_serving');
            $table->decimal('carbs_per_serving', 8, 2)->nullable()->after('protein_per_serving');
            $table->decimal('fat_per_serving', 8, 2)->nullable()->after('carbs_per_serving');
            $table->string('nutrition_source')->nullable()->after('fat_per_serving');
            $table->integer('servings')->nullable()->after('nutrition_source');
        });
    }

    public function down(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->dropColumn(['calories_per_serving', 'protein_per_serving', 'carbs_per_serving', 'fat_per_serving', 'nutrition_source', 'servings']);
        });
    }
};
