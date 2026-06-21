<?php

namespace App\Services;

use App\Models\NewsArticle;
use Carbon\CarbonInterface;

/**
 * Decides whether a post is due to be published right now. Each day, for each
 * configured window, ONE target minute is picked deterministically from the date
 * (so it is stable through the day but differs day to day and per window). A post
 * goes out as soon as "now" reaches that target, at most once per window - which
 * keeps the daily send time varied without ever sending twice in a window.
 */
class FacebookWindowScheduler
{
    public function isDue(CarbonInterface $now): bool
    {
        $window = $this->currentWindow($now);

        if ($window === null) {
            return false;
        }

        if ($now->lt($this->targetMinute($now, $window))) {
            return false;
        }

        return ! $this->alreadyPublishedInWindow($window[0], $now);
    }

    /**
     * The window that contains $now, as [start, end] for today, or null.
     *
     * @return array{0: CarbonInterface, 1: CarbonInterface}|null
     */
    public function currentWindow(CarbonInterface $now): ?array
    {
        foreach ((array) config('curvia.facebook.windows', []) as $window) {
            [$start, $end] = $window;
            $startAt = $now->copy()->setTimeFromTimeString($start)->startOfMinute();
            $endAt = $now->copy()->setTimeFromTimeString($end)->startOfMinute();

            if ($now->betweenIncluded($startAt, $endAt)) {
                return [$startAt, $endAt];
            }
        }

        return null;
    }

    /**
     * Deterministic random minute inside the window for $now's date. Same date +
     * window always yields the same minute; different dates spread it out.
     *
     * @param  array{0: CarbonInterface, 1: CarbonInterface}  $window
     */
    public function targetMinute(CarbonInterface $now, array $window): CarbonInterface
    {
        [$startAt, $endAt] = $window;
        $span = (int) $startAt->diffInMinutes($endAt);

        mt_srand((int) crc32($now->toDateString().'|'.$startAt->format('H:i')));
        $offset = $span > 0 ? mt_rand(0, $span) : 0;
        mt_srand(); // restore unseeded randomness for the rest of the request

        return $startAt->copy()->addMinutes($offset);
    }

    private function alreadyPublishedInWindow(CarbonInterface $start, CarbonInterface $now): bool
    {
        return NewsArticle::whereNotNull('posted_at')
            ->whereBetween('posted_at', [$start, $now])
            ->exists();
    }
}
