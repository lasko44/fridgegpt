<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meal_plans', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->integer('days')->default(7);
            $table->json('meals_per_day'); // ['breakfast','lunch','dinner']
            $table->integer('budget_cents')->nullable();
            $table->integer('target_calories')->nullable();
            $table->integer('target_protein')->nullable();
            $table->integer('target_carbs')->nullable();
            $table->integer('target_fat')->nullable();
            $table->json('dietary_restrictions')->nullable();
            $table->string('preferences')->nullable(); // free-text preferences
            $table->enum('status', ['draft', 'generating', 'complete', 'failed'])->default('draft');
            $table->json('grocery_list')->nullable();
            $table->integer('grocery_total_cents')->nullable();
            $table->integer('tokens_spent')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        Schema::create('meal_plan_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meal_plan_id')->constrained()->cascadeOnDelete();
            $table->integer('day_number'); // 1-14
            $table->string('meal_type'); // breakfast, lunch, dinner, snack
            $table->foreignId('recipe_id')->nullable()->constrained()->nullOnDelete();
            $table->string('recipe_name');
            $table->json('recipe_data')->nullable(); // full recipe if generated inline
            $table->boolean('is_generated')->default(false); // true = cost a token
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['meal_plan_id', 'day_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meal_plan_slots');
        Schema::dropIfExists('meal_plans');
    }
};
