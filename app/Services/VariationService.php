<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class VariationService
{

    public function generate(array $data): array
    {

    }

    private function call(array $data): array
    {
        $response = Http::retry(3, 2000)->withHeaders([
            'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
            'Content-Type' => 'application/json',
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $this->createMessage($data)
                ],
            ],
        ]);
        return $response->json();
    }

    private function createMessage(array $data): string
    {
        return 'Hello';
    }
}