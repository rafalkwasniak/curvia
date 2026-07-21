<?php

namespace App\Console\Commands;

use App\Services\TasteProfileBuilder;
use Illuminate\Console\Command;
use Throwable;

class BuildTasteProfile extends Command
{
    protected $signature = 'curvia:build-taste-profile {--sample=60 : Ile przykładów na klasę (publikowane / odrzucone)}';

    protected $description = 'Distill Rafał\'s publish/reject history into an editable taste profile for post scoring';

    public function handle(TasteProfileBuilder $builder): int
    {
        $this->info('Buduję profil gustu na podstawie historii decyzji...');

        try {
            $profile = $builder->build((int) $this->option('sample'));
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->line($profile);
        $this->newLine();
        $this->info('Profil zapisany. Edytuj go ręcznie w storage/app/'.config('curvia.scoring.profile_path'));

        return self::SUCCESS;
    }
}
