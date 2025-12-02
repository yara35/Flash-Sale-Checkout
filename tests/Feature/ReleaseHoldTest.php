<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Hold;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ReleaseHoldTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_releases_a_hold_successfully()
    {
        $hold = Hold::factory()->create();

        $response = $this->deleteJson("/api/holds/{$hold->id}");

        $response->assertStatus(200);
        $this->assertDatabaseCount('holds', 0);
    }

    /** @test */
    public function it_returns_404_if_hold_not_found()
    {
        $response = $this->deleteJson('/api/holds/999');

        $response->assertStatus(404);
    }
}
