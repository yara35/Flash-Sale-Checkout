<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Product;
use App\Models\Hold;

class HoldExpiryTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function expired_holds_are_automatically_released()
    {
        Queue::fake();

        $product = Product::factory()->create([
            'stock' => 10
        ]);

        // Create a hold for qty = 3
        $response = $this->post('/api/holds', [
            'product_id' => $product->id,
            'qty' => 3
        ])->assertStatus(201);

        $holdId = $response->json('hold_id');

        // simulate time passing → expire hold
        $this->travel(3)->minutes();

        // run expiry command or job manually
        $this->artisan('holds:expire');

        $product->refresh();

        // stock should be fully restored
        $this->assertEquals(10, $product->available_stock);

        // hold should be expired
        $this->assertEquals('expired', Hold::find($holdId)->status);
    }
}
