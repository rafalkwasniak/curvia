<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_shown_to_the_authenticated_user(): void
    {
        $user = User::factory()->create(['name' => 'Rafał', 'email' => 'rafal@kwasniak.org']);

        $this->actingAs($user)->get('/profile')
            ->assertOk()
            ->assertSee('Rafał')
            ->assertSee('rafal@kwasniak.org');
    }

    public function test_guests_cannot_open_the_profile(): void
    {
        $this->get('/profile')->assertRedirect('/login');
    }

    public function test_name_and_email_can_be_updated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/profile', [
            'name' => 'Nowa Nazwa',
            'email' => 'nowy@example.com',
        ])->assertRedirect(route('profile.edit'))->assertSessionHas('status');

        $user->refresh();
        $this->assertSame('Nowa Nazwa', $user->name);
        $this->assertSame('nowy@example.com', $user->email);
    }

    public function test_keeping_the_same_email_is_allowed(): void
    {
        $user = User::factory()->create(['email' => 'rafal@kwasniak.org']);

        $this->actingAs($user)->post('/profile', [
            'name' => $user->name,
            'email' => 'rafal@kwasniak.org',
        ])->assertSessionHasNoErrors();
    }

    public function test_email_must_be_unique_across_users(): void
    {
        $other = User::factory()->create(['email' => 'taken@example.com']);
        $user = User::factory()->create();

        $this->actingAs($user)->post('/profile', [
            'name' => $user->name,
            'email' => 'taken@example.com',
        ])->assertSessionHasErrors('email');
    }

    public function test_password_is_updated_only_when_provided(): void
    {
        $user = User::factory()->create(['password' => 'stare-haslo']);

        $this->actingAs($user)->post('/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'password' => 'nowe-haslo-123',
            'password_confirmation' => 'nowe-haslo-123',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('nowe-haslo-123', $user->refresh()->password));
    }

    public function test_blank_password_keeps_the_current_one(): void
    {
        $user = User::factory()->create(['password' => 'stare-haslo']);

        $this->actingAs($user)->post('/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'password' => '',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('stare-haslo', $user->refresh()->password));
    }

    public function test_password_change_requires_matching_confirmation(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'password' => 'nowe-haslo-123',
            'password_confirmation' => 'co-innego',
        ])->assertSessionHasErrors('password');
    }

    public function test_panel_navigation_links_are_present(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/')
            ->assertSee('Moje dane')
            ->assertSee('Wyloguj');
    }
}
