<?php

namespace App\Http\Controllers\Api\V1\Barcode;

use App\Http\Controllers\Controller;
use App\Http\Requests\Barcode\BarcodeLookupRequest;
use App\Services\BarcodeService;
use Illuminate\Http\JsonResponse;

class BarcodeLookupController extends Controller
{
    public function __invoke(BarcodeLookupRequest $request, BarcodeService $service): JsonResponse
    {
        $result = $service->lookup($request->input('barcode'));

        if (!$result) {
            return response()->json([
                'message' => 'Product not found. Try entering the ingredient manually.',
            ], 404);
        }

        return response()->json($result);
    }
}
