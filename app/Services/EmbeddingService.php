<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EmbeddingService
{
    private string $model;
    private int $dimensions;
    private string $apiKey;

    public function __construct()
    {
        $this->model = config('ai.embedding.model');
        $this->dimensions = config('ai.embedding.dimensions');
        $this->apiKey = config('ai.openai_api_key');
    }

    /**
     * Generate an embedding vector for the given text.
     *
     * @throws ConnectionException
     */
    public function embed(string $text): array
    {
        $cacheKey = 'embedding_' . md5($text . $this->model);
        $cached = Cache::get($cacheKey);

        if ($cached) {
            return $cached;
        }

        $text = mb_substr($text, 0, 22500);

        $response = Http::retry(3, 2000)->withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post('https://api.openai.com/v1/embeddings', [
            'model' => $this->model,
            'input' => $text,
            'dimensions' => $this->dimensions,
        ]);

        if (!$response->successful()) {
            Log::error('Embedding API failed', ['status' => $response->status(), 'body' => $response->body()]);
            throw new \RuntimeException('Failed to generate embedding: ' . $response->body());
        }

        $embedding = $response->json('data.0.embedding');

        Cache::put($cacheKey, $embedding, now()->addDays(7));

        return $embedding;
    }

    /**
     * @param array<string> $texts
     * @return array<array<float>>
     * @throws ConnectionException
     */
    public function embedBatch(array $texts): array
    {
        $texts = array_map(fn(string $t) => mb_substr($t, 0, 22500), $texts);

        $response = Http::retry(3, 2000)->withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post('https://api.openai.com/v1/embeddings', [
            'model' => $this->model,
            'input' => array_values($texts),
            'dimensions' => $this->dimensions,
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException('Failed to generate batch embeddings: ' . $response->body());
        }

        return array_map(fn($item) => $item['embedding'], $response->json('data'));
    }
}
