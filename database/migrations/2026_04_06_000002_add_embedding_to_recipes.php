<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->text('input_ingredients')->nullable()->after('image_url');
        });

        DB::statement('ALTER TABLE recipes ADD COLUMN embedding vector(1536)');

        // Create an IVFFlat index for fast cosine similarity search
        DB::statement('CREATE INDEX recipes_embedding_idx ON recipes USING ivfflat (embedding vector_cosine_ops) WITH (lists = 100)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS recipes_embedding_idx');
        DB::statement('ALTER TABLE recipes DROP COLUMN IF EXISTS embedding');

        Schema::table('recipes', function (Blueprint $table) {
            $table->dropColumn('input_ingredients');
        });
    }
};
