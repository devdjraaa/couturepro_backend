<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WebhookController extends Controller
{
    public function __construct(private PaymentService $paymentService) {}

    public function handle(Request $request, string $provider): Response
    {
        $rawPayload = $request->getContent();
        $signature  = $request->header('X-FedaPay-Signature', '');

        $this->paymentService->handleWebhook($provider, $rawPayload, $signature);

        return response('OK', 200);
    }
}
