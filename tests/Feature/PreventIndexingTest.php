<?php

namespace Tests\Feature;

use Tests\TestCase;

class PreventIndexingTest extends TestCase
{
    public function test_responses_carry_the_noindex_header(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_layout_renders_the_robots_meta_tag(): void
    {
        $this->get('/login')
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }

    public function test_robots_txt_disallows_everything(): void
    {
        $contents = file_get_contents(public_path('robots.txt'));

        $this->assertStringContainsString('Disallow: /', $contents);
    }
}
