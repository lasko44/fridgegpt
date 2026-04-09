<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ingredients', function (Blueprint $table) {
            $table->string('amount')->nullable()->after('name');
            $table->string('unit')->nullable()->after('amount');
            $table->decimal('calories', 8, 2)->nullable()->after('unit');
            $table->decimal('protein', 8, 2)->nullable()->after('calories');
            $table->decimal('carbs', 8, 2)->nullable()->after('protein');
            $table->decimal('fat', 8, 2)->nullable()->after('carbs');
            $table->decimal('fiber', 8, 2)->nullable()->after('fat');
            $table->string('nutrition_source')->nullable()->after('fiber');
            $table->string('barcode')->nullable()->index()->after('nutrition_source');
        });
    }

    public function down(): void
    {
        Schema::table('ingredients', function (Blueprint $table) {
            $table->dropColumn(['amount', 'unit', 'calories', 'protein', 'carbs', 'fat', 'fiber', 'nutrition_source', 'barcode']);
        });
    }
};
