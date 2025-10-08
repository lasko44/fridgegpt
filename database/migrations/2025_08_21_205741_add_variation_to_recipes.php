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
        Schema::table('recipes', function (Blueprint $table) {
            $table->boolean('is_variation')->default(false)->after('description');
            $table->unsignedBigInteger('recipe_id')->nullable()->after('is_variation');
            $table->foreign('recipe_id')->references('id')->on('recipes')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->dropForeign(['recipe_id']);
            $table->dropColumn(['is_variation', 'recipe_id']);
        });
    }
};
