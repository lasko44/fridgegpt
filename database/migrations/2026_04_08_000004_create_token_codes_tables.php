<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('token_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->enum('type', ['promo', 'single']);
            $table->integer('tokens');
            $table->integer('max_uses')->nullable(); // null = unlimited
            $table->integer('uses_count')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('token_code_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('token_code_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->integer('tokens_received');
            $table->timestamps();

            $table->unique(['token_code_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('token_code_redemptions');
        Schema::dropIfExists('token_codes');
    }
};
