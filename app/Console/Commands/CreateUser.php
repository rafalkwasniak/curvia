<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreateUser extends Command
{
    protected $signature = 'curvia:create-user
        {email? : Account email (login)}
        {--name= : Display name}
        {--password= : Plain password (prompted if omitted)}';

    protected $description = 'Create or update the single admin account (closed system, no registration)';

    public function handle(): int
    {
        $email = $this->argument('email') ?? $this->ask('Email');
        $name = $this->option('name') ?: $this->ask('Nazwa', 'Administrator');
        $password = $this->option('password') ?: $this->secret('Hasło');

        $validator = Validator::make(
            ['email' => $email, 'name' => $name, 'password' => $password],
            [
                'email' => ['required', 'string', 'email', 'max:255'],
                'name' => ['required', 'string', 'max:255'],
                'password' => ['required', 'string', 'min:8'],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $existing = User::where('email', $email)->exists();

        User::updateOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => $password],
        );

        $this->info($existing
            ? "Zaktualizowano konto: {$email}"
            : "Utworzono konto: {$email}");

        return self::SUCCESS;
    }
}
