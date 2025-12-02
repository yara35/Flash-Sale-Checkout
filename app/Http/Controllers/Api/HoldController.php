<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ReleaseHoldJob;
use App\Models\Hold;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HoldController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request-> validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $productId = $request->product_id;
        $quantity = $request->quantity;

       return DB::transaction(function () use ($productId, $quantity) {
            $updated = DB::update(
                'UPDATE products 
                SET available_stock = available_stock - ? 
                WHERE id = ? AND available_stock >= ?',
                [$quantity, $productId, $quantity]
            );
            if ($updated === 0) {
                return response()->json([
                    'message' => 'Insufficient stock available'
                ], 400);
            }

            $expiresAt = now()->addMinutes(2);
            $hold = Hold::create([
            'product_id' => $productId,
            'quantity' => $quantity,
            'expires_at' => $expiresAt,
            'status' => 'active'
            ]);

            //queue job to release hold after expiry
            ReleaseHoldJob::dispatch($hold)->delay($expiresAt->diffInSeconds(now()));
            Cache::forget("product:{$productId}");
            return response()->json(['hold_id' => $hold->id, 'expires_at' => $expiresAt->toIso8601String()], 201);

    });

    }

}
