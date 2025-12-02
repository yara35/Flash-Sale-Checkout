<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Product;
use App\Models\Hold;

class ParallelHoldTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_prevents_overselling_when_multiple_parallel_holds_are_created()
    {
        // product with stock = 5
        $product = Product::factory()->create([
            'stock' => 5
        ]);

        $requests = 10; // 10 clients trying to hold stock at same time
        $successful = 0;

        DB::beginTransaction();
        for ($i = 0; $i < $requests; $i++) {
            try {
                $response = $this->post('/api/holds', [
                    'product_id' => $product->id,
                    'qty' => 1
                ]);

                if ($response->status() === 201) {
                    $successful++;
                }
            } catch (\Throwable $e) {
                // ignore failures
            }
        }
        DB::commit();

        $this->assertEquals(5, Hold::count()); // stock was 5 → only 5 holds allowed
        $this->assertEquals(5, $successful);
    }
}
