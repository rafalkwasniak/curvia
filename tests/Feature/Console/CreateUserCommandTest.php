<?php

namespace Tests\Feature\Console;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateUserCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_account(): void
    {
        $this->artisan('curvia:create-user', [
            'email' => 'rafal@kwasniak.org',
            '--name' => 'Rafał',
            '--password' => 'tajne-haslo',
        ])->assertSuccessful();

        $user = User::where('email', 'rafal@kwasniak.org')->firstOrFail();

        $this->assertSame('Rafał', $user->name);
        $this->assertTrue(Hash::check('tajne-haslo', $user->password));
    }

    public function test_it_updates_an_existing_account_instead_of_duplicating(): void
    {
        User::factory()->create(['email' => 'rafal@kwasniak.org']);

        $this->artisan('curvia:create-user', [
            'email' => 'rafal@kwasniak.org',
            '--name' => 'Nowa Nazwa',
            '--password' => 'nowe-haslo',
        ])->assertSuccessful();

        $this->assertSame(1, User::where('email', 'rafal@kwasniak.org')->count());
        $this->assertTrue(Hash::check('nowe-haslo', User::firstOrFail()->password));
    }

    public function test_it_rejects_a_short_password(): void
    {
        $this->artisan('curvia:create-user', [
            'email' => 'rafal@kwasniak.org',
            '--name' => 'Rafał',
            '--password' => 'krotkie',
        ])->assertFailed();

        $this->assertSame(0, User::count());
    }
}
