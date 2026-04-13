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
        Schema::table('users', function (Blueprint $table) {
            $table->string('zip_code', 10)->nullable()->after('expo_push_token');
            $table->string('preferred_store_id')->nullable()->after('zip_code');
            $table->string('preferred_store_name')->nullable()->after('preferred_store_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['zip_code', 'preferred_store_id', 'preferred_store_name']);
        });
    }
};
