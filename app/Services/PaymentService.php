<?php


namespace App\Services;


use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\PaymentWebhook;


class PaymentService
{
    public function processWebhookRecord(PaymentWebhook $hook, $order)
    {
        DB::transaction(function () use ($hook, $order) {
        // ensure processed once
        $exists = PaymentWebhook::where('idempotency_key', $hook->idempotency_key)
        ->where('status', 'processed')->first();
        if ($exists) return;


        $payload = $hook->payload ?? [];
        $result = $payload['result'] ?? ($hook->result ?? null);


        if ($result === 'success') {
            $order->status = 'paid';
            $order->save();
        } else {
            $order->status = 'cancelled';
            $order->save();


            $hold = $order->hold()->lockForUpdate()->first();
            if ($hold && $hold->status === 'used') {
                $hold->status = 'cancelled';
                $hold->save();


                DB::update('UPDATE products SET available_stock = available_stock + ? WHERE id = ?', [$hold->qty, $hold->product_id]);
                Cache::forget("product:{$hold->product_id}");
            }
        }


        $hook->status = 'processed';
        $hook->result = $result;
        $hook->order_id = $order->id;
        $hook->save();
        });
    }
}