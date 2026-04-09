<?php

namespace App\Services;

class BarcodeService
{
    public function __construct(private NutritionService $nutritionService) {}

    /**
     * Look up a product by barcode and return nutrition + product info.
     */
    public function lookup(string $barcode): ?array
    {
        return $this->nutritionService->lookupByBarcode($barcode);
    }
}
