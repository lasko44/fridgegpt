<?php

namespace App\Http\Controllers;

use App\Services\StripeWebhookService;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierWebhookController;
use Symfony\Component\HttpFoundation\Response;

class WebhookController extends CashierWebhookController
{
    protected function handleCheckoutSessionCompleted(array $payload): Response
    {
        $session = $payload['data']['object'] ?? [];

        $webhookService = app(StripeWebhookService::class);
        $webhookService->processTokenPurchase($session);

        return $this->successMethod();
    }
}
