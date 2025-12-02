<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Hold;
use Illuminate\Foundation\Testing\RefreshDatabase;

class HoldControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_a_hold_successfully()
    {
        $response = $this->postJson('/api/holds', [
            'user_id' => 1,
            'product_id' => 10,
            'quantity' => 2,
            'expires_at' => now()->addMinutes(5)->toDateTimeString(),
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseCount('holds', 1);
    }

    /** @test */
    public function it_returns_error_if_product_already_held()
    {
        Hold::factory()->create([
            'user_id' => 1,
            'product_id' => 10,
            'quantity' => 1
        ]);

        $response = $this->postJson('/api/holds', [
            'user_id' => 1,
            'product_id' => 10,
            'quantity' => 2,
            'expires_at' => now()->addMinutes(5)->toDateTimeString(),
        ]);

        $response->assertStatus(409);
    }
}
