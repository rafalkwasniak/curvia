<?php

namespace App\Console\Commands;

use App\Services\NewsFetcher;
use Illuminate\Console\Command;

class FetchNews extends Command
{
    protected $signature = 'curvia:fetch-news';

    protected $description = 'Fetch motorcycle articles from active RSS sources';

    public function handle(NewsFetcher $fetcher): int
    {
        $s = $fetcher->fetch();

        $this->info(sprintf(
            'Źródła: %d | nowe: %d | odfiltrowane: %d | duplikaty: %d | błędy: %d',
            $s['sources'], $s['created'], $s['filtered'], $s['duplicates'], $s['errors'],
        ));

        return self::SUCCESS;
    }
}
