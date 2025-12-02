<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hold;
use App\Models\Order;
use App\Models\PaymentWebhook;
use Illuminate\Http\Request;
use App\Services\PaymentService;

class PaymentWebhookController extends Controller
{
    public function handle(Request $request)
    {
    $request->validate(['idempotency_key' => 'required|string', 'result' => 'required|in:success,failure']);


    $key = $request->idempotency_key;


    // try to create webhook record
    try {
        $hook = PaymentWebhook::create([
        'idempotency_key' => $key,
        'order_id' => $request->order_id ?? null,
        'payload' => $request->all(),
        'status' => 'pending'
        ]);
    } catch (\Illuminate\Database\QueryException $e) {
        $hook = PaymentWebhook::where('idempotency_key', $key)->first();
        if ($hook && $hook->status === 'processed') {
            return response()->json(['message' => 'already processed'], 200);
        }
    }
    // find order if exists
    $order = null;
    if ($hook->order_id) {
        $order = Order::find($hook->order_id);
    }elseif (isset($request->hold_id)) {
        $hold = Hold::find($request->hold_id);
        if ($hold) {
        $order = Order::where('hold_id', $hold->id)->first();
        }
    }
    if(!$order){
        return response()->json(['message' => 'Webhook recorded, pending order association'], 202);
    }
    app(PaymentService::class)->processWebhookRecord($hook, $order);
    return response()->json(['message' => 'Webhook processed'], 200);
}
}
