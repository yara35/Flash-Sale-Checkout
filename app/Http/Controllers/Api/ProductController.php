<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ProductController extends Controller
{
    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $key = "product:{$id}";
        $product = Cache::remember($key, 10, function () use ($id) {
        return Product::select('id','name','price','available_stock')->findOrFail($id);
        });
        return response()->json(
            [
                'message' => 'Product retrieved successfully',
                'data' => $product
            ]
        );
    }
}
