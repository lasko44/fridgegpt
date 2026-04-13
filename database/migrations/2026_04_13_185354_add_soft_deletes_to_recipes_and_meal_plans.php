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
        // Recipes already has deleted_at from the original schema
        // Just add it to meal_plans and meal_plan_slots
        Schema::table('meal_plans', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('meal_plan_slots', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('meal_plans', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('meal_plan_slots', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
