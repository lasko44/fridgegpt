<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Integrates with the Kroger API for real grocery prices and store locations.
 * Uses OAuth2 client credentials flow (no user login needed).
 */
class KrogerService
{
    private string $baseUrl;

    private string $clientId;

    private string $clientSecret;

    public function __construct()
    {
        $this->baseUrl = config('services.kroger.base_url');
        $this->clientId = config('services.kroger.client_id');
        $this->clientSecret = config('services.kroger.client_secret');
    }

    /**
     * Find nearby Kroger-family stores by zip code.
     *
     * @return array{locationId: string, name: string, address: string, distance: float}[]
     */
    public function findStores(string $zipCode, int $limit = 5): array
    {
        $cacheKey = "kroger:stores:{$zipCode}";

        return Cache::remember($cacheKey, 86400, function () use ($zipCode, $limit) {
            $response = $this->authenticatedRequest('GET', '/locations', [
                'filter.zipCode.near' => $zipCode,
                'filter.limit' => $limit,
                'filter.radiusInMiles' => 25,
            ]);

            if (! $response || empty($response['data'])) {
                return [];
            }

            return collect($response['data'])->map(fn ($store) => [
                'locationId' => $store['locationId'],
                'name' => $store['name'] ?? 'Kroger',
                'address' => $this->formatAddress($store['address'] ?? []),
                'distance' => round($store['geolocation']['latLng']['latitude'] ?? 0, 2),
                'chain' => $store['chain'] ?? 'Kroger',
            ])->toArray();
        });
    }

    /**
     * Search for a product and return the best price match.
     *
     * @return array{name: string, price: float, size: string, image: string|null}|null
     */
    public function searchProduct(string $term, string $locationId): ?array
    {
        $cacheKey = "kroger:product:" . md5("{$term}:{$locationId}");

        return Cache::remember($cacheKey, 3600, function () use ($term, $locationId) {
            // Try progressively broader search terms until we find a match
            $searchTerms = $this->generateSearchVariations($term);

            foreach ($searchTerms as $searchTerm) {
                // Try with location first, then without (broader catalog)
                $response = $this->authenticatedRequest('GET', '/products', [
                    'filter.term' => $searchTerm,
                    'filter.locationId' => $locationId,
                    'filter.limit' => 10,
                ]);

                // If no results with location, try without location constraint
                if ((! $response || empty($response['data'])) && $locationId) {
                    $response = $this->authenticatedRequest('GET', '/products', [
                        'filter.term' => $searchTerm,
                        'filter.limit' => 10,
                    ]);
                }

                if ($response && ! empty($response['data'])) {
                    // Pick the best match — prefer items with a price
                    $product = $this->pickBestMatch($response['data'], $term);

                    if ($product) {
                        $price = $product['items'][0]['price']['regular'] ?? null;
                        $promoPrice = $product['items'][0]['price']['promo'] ?? null;

                        if (($promoPrice ?? $price ?? 0) > 0) {
                            return [
                                'name' => $product['description'] ?? $term,
                                'price' => $promoPrice ?? $price ?? 0,
                                'size' => $product['items'][0]['size'] ?? '',
                                'image' => $product['images'][0]['sizes'][0]['url'] ?? null,
                                'upc' => $product['upc'] ?? null,
                            ];
                        }
                    }
                }
            }

            return null;
        });
    }

    /**
     * Generate search term variations from broad to specific.
     * "red bell pepper" → ["red bell pepper", "bell pepper", "pepper"]
     * "coconut aminos" → ["coconut aminos", "aminos", "coconut sauce"]
     * "ground beef" → ["ground beef", "beef"]
     */
    /**
     * Generate fallback search variations when the primary term (from GPT) fails.
     * Keeps it simple — just structural transforms, no hardcoded product names.
     */
    private function generateSearchVariations(string $term): array
    {
        $term = strtolower(trim($term));
        $variations = [$term];

        // Singular/plural
        if (str_ends_with($term, 's') && strlen($term) > 3) {
            $variations[] = rtrim($term, 's');
        }
        if (str_ends_with($term, 'es') && strlen($term) > 4) {
            $variations[] = substr($term, 0, -2);
        }

        // Remove common modifiers
        $stripped = preg_replace('/^(fresh|organic|diced|chopped|cooked|raw|frozen|canned|dried|sliced|minced|whole|boneless|skinless|unsweetened|plain|pure)\s+/i', '', $term);
        if ($stripped !== $term && $stripped !== '') {
            $variations[] = $stripped;
        }

        // Drop words progressively
        $words = explode(' ', $term);
        if (count($words) > 1) {
            $variations[] = implode(' ', array_slice($words, 1));
            if (count($words) > 2) {
                $variations[] = implode(' ', array_slice($words, -2));
            }
        }

        return array_unique($variations);
    }

    /**
     * Pick the best product match from Kroger results.
     * STRICT: only returns a product if its description shares meaningful words
     * with the search term. Returns null if nothing relevant is found.
     */
    private function pickBestMatch(array $products, string $originalTerm): ?array
    {
        $termWords = $this->significantWords($originalTerm);

        if (empty($termWords)) {
            return null;
        }

        // The "primary" word is the last significant word — usually the noun
        // "olive oil" → "oil", "green bell pepper" → "pepper", "broccoli" → "broccoli"
        $primaryWord = end($termWords);

        $scored = [];

        foreach ($products as $product) {
            $price = $product['items'][0]['price']['regular'] ?? $product['items'][0]['price']['promo'] ?? 0;
            if ($price <= 0) {
                continue;
            }

            $desc = strtolower($product['description'] ?? '');
            $descWords = $this->significantWords($desc);

            // The product description MUST contain the primary word
            if (! in_array($primaryWord, $descWords)) {
                continue;
            }

            // Score by how many search words appear in the description
            $matches = count(array_intersect($termWords, $descWords));

            // Bonus: if the description STARTS with a search word, it's likely the main product
            $descStart = $descWords[0] ?? '';
            $startsWithTerm = in_array($descStart, $termWords) ? 10 : 0;

            // Penalty: long descriptions with many extra words are likely blends/combos
            $extraWords = count($descWords) - $matches;
            $penalty = min($extraWords * 0.5, 5);

            $scored[] = [
                'product' => $product,
                'score' => $matches + $startsWithTerm - $penalty,
                'price' => $price,
            ];
        }

        if (empty($scored)) {
            return null;
        }

        usort($scored, function ($a, $b) {
            if (abs($a['score'] - $b['score']) > 0.5) {
                return $b['score'] <=> $a['score'];
            }
            return $a['price'] <=> $b['price'];
        });

        return $scored[0]['product'];
    }

    /**
     * Extract significant words (3+ chars, no stop words) for matching.
     */
    private function significantWords(string $text): array
    {
        $stop = ['the', 'and', 'for', 'with', 'from', 'each', 'per', 'all', 'any', 'our', 'new', 'free', 'style', 'type', 'size', 'pack', 'count'];

        $words = preg_split('/[\s\-\/,.()\[\]]+/', strtolower($text));

        return array_values(array_unique(array_filter($words, function ($w) use ($stop) {
            return strlen($w) >= 3 && ! in_array($w, $stop) && ! is_numeric($w);
        })));
    }

    /**
     * Use GPT to convert generic ingredient names into store-friendly search terms.
     * One call for the whole list — fast and accurate.
     *
     * @param  string[]  $ingredientNames
     * @return array<string, string>  Maps original name → store search term
     */
    public function translateToStoreTerms(array $ingredientNames): array
    {
        $cacheKey = 'kroger:translate:' . md5(implode('|', $ingredientNames));

        return Cache::remember($cacheKey, 86400, function () use ($ingredientNames) {
            $list = implode("\n", array_map(fn ($n, $i) => ($i + 1) . ". {$n}", $ingredientNames, array_keys($ingredientNames)));

            $response = Http::timeout(15)->withHeaders([
                'Authorization' => 'Bearer ' . config('ai.openai_api_key'),
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o-mini',
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You convert recipe ingredient names into grocery store product search terms. '
                            . 'For each ingredient, return the term a customer would type into a grocery store search bar to find it. '
                            . 'Use singular form. Use the common US grocery store product name. '
                            . 'Examples: "avocados" → "hass avocado", "whole grain bread" → "whole wheat bread", '
                            . '"bell pepper" → "green bell pepper", "eggs" → "large eggs grade A", '
                            . '"coconut aminos" → "coconut aminos", "greek yogurt" → "greek yogurt plain", '
                            . '"honey" → "clover honey", "olive oil" → "extra virgin olive oil". '
                            . 'Return JSON: {"terms": {"original_name": "store_search_term", ...}}',
                    ],
                    [
                        'role' => 'user',
                        'content' => "Convert these ingredient names to grocery store search terms:\n{$list}",
                    ],
                ],
            ]);

            $content = $response->json('choices.0.message.content');
            $parsed = json_decode($content, true);

            return $parsed['terms'] ?? [];
        });
    }

    /**
     * Price an entire grocery list against a specific store.
     * Uses GPT to translate ingredient names to store-friendly terms first.
     *
     * @param  array{name: string}[]  $items
     * @return array{items: array, total: float, store: array}
     */
    public function priceGroceryList(array $items, string $locationId, array $store = []): array
    {
        $pricedItems = [];
        $total = 0;

        // Translate all ingredient names to store terms in one GPT call
        $names = array_filter(array_map(fn ($i) => $i['name'] ?? '', $items));
        $storeTerms = ! empty($names) ? $this->translateToStoreTerms(array_values($names)) : [];

        foreach ($items as $item) {
            $name = $item['name'] ?? '';
            if (! $name) {
                continue;
            }

            // Use the GPT-translated store term if available, otherwise the original
            $searchTerm = $storeTerms[$name] ?? $name;
            $product = $this->searchProduct($searchTerm, $locationId);
            $hasRealPrice = $product && $product['price'] > 0;
            $fallbackCents = $item['estimated_price_cents'] ?? null;

            $pricedItems[] = [
                'name' => $name,
                'quantity' => $item['quantity'] ?? null,
                'unit' => $item['unit'] ?? null,
                'estimated_price_cents' => $fallbackCents,
                'kroger_name' => $hasRealPrice ? $product['name'] : null,
                'kroger_price' => $hasRealPrice ? (int) round($product['price'] * 100) : null,
                'kroger_size' => $hasRealPrice ? ($product['size'] ?? null) : null,
                'kroger_image' => $hasRealPrice ? ($product['image'] ?? null) : null,
                'kroger_not_found' => ! $hasRealPrice,
            ];

            // Use real price if available, fall back to GPT estimate for the total
            $total += $hasRealPrice
                ? $product['price']
                : ($fallbackCents ? $fallbackCents / 100 : 0);
        }

        return [
            'items' => $pricedItems,
            'total_cents' => (int) round($total * 100),
            'store' => $store,
        ];
    }

    private function getAccessToken(): ?string
    {
        return Cache::remember('kroger:access_token', 1700, function () {
            $response = Http::asForm()
                ->withBasicAuth($this->clientId, $this->clientSecret)
                ->withHeaders(['Accept' => 'application/json'])
                ->post("{$this->baseUrl}/connect/oauth2/token", [
                    'grant_type' => 'client_credentials',
                    'scope' => 'product.compact',
                ]);

            if (! $response->successful()) {
                Log::error('Kroger OAuth failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            return $response->json('access_token');
        });
    }

    private function authenticatedRequest(string $method, string $path, array $params = []): ?array
    {
        $token = $this->getAccessToken();

        if (! $token) {
            return null;
        }

        $response = Http::withToken($token)
            ->timeout(10)
            ->retry(2, 1000)
            ->{strtolower($method)}("{$this->baseUrl}{$path}", $params);

        if (! $response->successful()) {
            Log::warning('Kroger API error', [
                'path' => $path,
                'status' => $response->status(),
                'body' => substr($response->body(), 0, 300),
            ]);

            return null;
        }

        return $response->json();
    }

    private function formatAddress(array $address): string
    {
        return trim(implode(', ', array_filter([
            $address['addressLine1'] ?? '',
            $address['city'] ?? '',
            $address['state'] ?? '',
            $address['zipCode'] ?? '',
        ])));
    }
}
