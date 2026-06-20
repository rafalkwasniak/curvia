<?php

namespace Tests\Feature;

use App\Models\RssSource;
use Database\Seeders\RssSourceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RssSourceSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_the_verified_sources(): void
    {
        $this->seed(RssSourceSeeder::class);

        $this->assertSame(7, RssSource::count());
        $this->assertDatabaseHas('rss_sources', ['name' => 'RideApart']);
    }

    public function test_it_is_idempotent(): void
    {
        $this->seed(RssSourceSeeder::class);
        $this->seed(RssSourceSeeder::class);

        $this->assertSame(7, RssSource::count());
    }
}
