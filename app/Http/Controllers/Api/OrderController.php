<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hold;
use App\Models\Order;
use App\Models\PaymentWebhook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\PaymentService;

class OrderController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'hold_id' => 'required|exists:holds,id'
        ]);
        
        $holdId = $request->hold_id;
        return DB::transaction(function () use ($holdId) {
            $hold = Hold::where('id', $holdId)->lockForUpdate()->first();
            if (!$hold || $hold->status !== 'active' || $hold->expires_at->isPast()) {
                return response()->json(['message' => 'Hold is invalid or has expired'], 409);
            }
            $hold->status = 'used';
            $hold->save();

            $amount = $hold->quantity * $hold->product->price;

            $order = Order::create([
                'hold_id' => $hold->id,
                'status' => 'pre_payment',
                'amount' => $amount
            ]);

            //proceed any pending webhooks for this
            $pending = PaymentWebhook::where('order_id', null)
            ->where('status','pending')
            ->get();


            foreach ($pending as $hook) {
            // naive match: if payload contains hold_id
            $payload = $hook->payload ?? [];
            if (isset($payload['hold_id']) && $payload['hold_id'] == $hold->id) {
            app(PaymentService::class)->processWebhookRecord($hook, $order);
            }
            }
            return response()->json([
                'order_id' => $order->id,
                'amount' => $order->amount,
                'status' => $order->status
            ], 201);

        });
    }
}
