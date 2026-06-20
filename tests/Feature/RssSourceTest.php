<?php

namespace Tests\Feature;

use App\Models\RssSource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RssSourceTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsUser(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        return $user;
    }

    public function test_guests_cannot_access_the_sources_list(): void
    {
        $this->get('/rss')->assertRedirect('/login');
    }

    public function test_list_shows_existing_sources(): void
    {
        $this->actingAsUser();
        RssSource::create(['name' => 'RideApart', 'url' => 'https://www.rideapart.com/rss/category/news/']);

        $this->get('/rss')->assertOk()->assertSee('RideApart');
    }

    public function test_a_source_can_be_added_and_is_active_by_default(): void
    {
        $this->actingAsUser();

        $this->post('/rss', [
            'name' => 'Visordown',
            'url' => 'https://www.visordown.com/rss',
        ])->assertRedirect(route('rss.index'));

        $source = RssSource::firstOrFail();
        $this->assertSame('Visordown', $source->name);
        $this->assertTrue($source->active);
    }

    public function test_name_and_url_are_required(): void
    {
        $this->actingAsUser();

        $this->post('/rss', ['name' => '', 'url' => ''])
            ->assertSessionHasErrors(['name', 'url']);
    }

    public function test_url_must_be_valid(): void
    {
        $this->actingAsUser();

        $this->post('/rss', ['name' => 'Bad', 'url' => 'not-a-url'])
            ->assertSessionHasErrors('url');
    }

    public function test_url_must_be_unique(): void
    {
        $this->actingAsUser();
        RssSource::create(['name' => 'Visordown', 'url' => 'https://www.visordown.com/rss']);

        $this->post('/rss', ['name' => 'Duplicate', 'url' => 'https://www.visordown.com/rss'])
            ->assertSessionHasErrors('url');
    }

    public function test_a_source_can_be_updated(): void
    {
        $this->actingAsUser();
        $source = RssSource::create(['name' => 'Old', 'url' => 'https://example.com/old']);

        $this->post(route('rss.update', $source), [
            'name' => 'New Name',
            'url' => 'https://example.com/new',
            'active' => '1',
        ])->assertRedirect(route('rss.index'));

        $source->refresh();
        $this->assertSame('New Name', $source->name);
        $this->assertSame('https://example.com/new', $source->url);
        $this->assertTrue($source->active);
    }

    public function test_updating_without_the_active_checkbox_disables_the_source(): void
    {
        $this->actingAsUser();
        $source = RssSource::create(['name' => 'Src', 'url' => 'https://example.com/feed', 'active' => true]);

        $this->post(route('rss.update', $source), [
            'name' => 'Src',
            'url' => 'https://example.com/feed',
        ]);

        $this->assertFalse($source->refresh()->active);
    }

    public function test_keeping_the_same_url_on_update_is_allowed(): void
    {
        $this->actingAsUser();
        $source = RssSource::create(['name' => 'Src', 'url' => 'https://example.com/feed']);

        $this->post(route('rss.update', $source), [
            'name' => 'Src renamed',
            'url' => 'https://example.com/feed',
            'active' => '1',
        ])->assertSessionHasNoErrors();
    }

    public function test_toggle_flips_active_state(): void
    {
        $this->actingAsUser();
        $source = RssSource::create(['name' => 'Src', 'url' => 'https://example.com/feed', 'active' => true]);

        $this->post(route('rss.toggle', $source));
        $this->assertFalse($source->refresh()->active);

        $this->post(route('rss.toggle', $source));
        $this->assertTrue($source->refresh()->active);
    }

    public function test_a_source_can_be_removed(): void
    {
        $this->actingAsUser();
        $source = RssSource::create(['name' => 'Src', 'url' => 'https://example.com/feed']);

        $this->post(route('rss.destroy', $source))->assertRedirect(route('rss.index'));

        $this->assertDatabaseMissing('rss_sources', ['id' => $source->id]);
    }
}
