<?php
namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class UnSplashService
{
    protected string $accessKey;

    public function __construct()
    {
        // prefer explicit access key, fall back to app id if named that way
        $this->accessKey = env('UNSPLASH_ACCESS') ?: env('UNSPLASH_APP_ID');
    }

    /**
     * @throws ConnectionException
     */
    public function searchPhotos(string $query, int $page = 1, int $perPage = 1): array
    {
        $response = Http::withHeaders(['Accept-Version' => 'v1'])
            ->get('https://api.unsplash.com/search/photos', [
                'query' => $query,
                'page' => $page,
                'per_page' => $perPage,
                'client_id' => $this->accessKey,
            ]);

        return $response->successful() ? $response->json() : ['errors' => $response->body()];
    }

    public function randomPhoto(?string $query = null, int $count = 1): array
    {
        $params = ['count' => $count, 'client_id' => $this->accessKey];
        if ($query) {
            $params['query'] = $query;
        }

        $response = Http::withHeaders(['Accept-Version' => 'v1'])
            ->get('https://api.unsplash.com/photos/random', $params);

        return $response->successful() ? $response->json() : ['errors' => $response->body()];
    }
}
