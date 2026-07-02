<?php

namespace Tests\Feature;

use App\Enums\ArticleStatus;
use App\Models\NewsArticle;
use App\Services\FacebookWindowScheduler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class FacebookWindowSchedulerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['curvia.facebook.windows' => [['09:00', '11:00'], ['18:00', '20:00']]]);
    }

    public function test_no_window_outside_configured_hours(): void
    {
        $scheduler = new FacebookWindowScheduler;

        $this->assertNull($scheduler->currentWindow(Carbon::parse('2026-06-21 12:30')));
        $this->assertFalse($scheduler->isDue(Carbon::parse('2026-06-21 12:30')));
    }

    public function test_current_window_is_detected_inside_hours(): void
    {
        $window = (new FacebookWindowScheduler)->currentWindow(Carbon::parse('2026-06-21 18:42'));

        $this->assertNotNull($window);
        $this->assertSame('18:00', $window[0]->format('H:i'));
        $this->assertSame('20:00', $window[1]->format('H:i'));
    }

    public function test_target_minute_is_stable_for_a_day_and_within_the_window(): void
    {
        $scheduler = new FacebookWindowScheduler;
        $window = $scheduler->currentWindow(Carbon::parse('2026-06-21 09:30'));
        $now = Carbon::parse('2026-06-21 09:00');

        $first = $scheduler->targetMinute($now, $window);
        $second = $scheduler->targetMinute($now, $window);

        $this->assertEquals($first, $second);
        $this->assertTrue($first->betweenIncluded($window[0], $window[1]));
    }

    public function test_target_minute_spreads_across_days(): void
    {
        $scheduler = new FacebookWindowScheduler;
        $minutes = [];

        for ($day = 1; $day <= 12; $day++) {
            $date = Carbon::parse(sprintf('2026-06-%02d 09:00', $day));
            $window = $scheduler->currentWindow($date->copy()->setTime(9, 30));
            $minutes[] = $scheduler->targetMinute($date, $window)->format('H:i');
        }

        $this->assertGreaterThan(1, count(array_unique($minutes)));
    }

    public function test_target_minute_never_lands_on_the_last_window_minute(): void
    {
        $scheduler = new FacebookWindowScheduler;

        // The window closes the instant the clock passes its end minute, while the
        // cron fires seconds into a minute - so a target on the final minute would
        // be unreachable and the post silently dropped. Guard every day of a month.
        for ($day = 1; $day <= 31; $day++) {
            $date = Carbon::parse(sprintf('2026-07-%02d 18:30', $day));
            $window = $scheduler->currentWindow($date);
            $target = $scheduler->targetMinute($date, $window);

            $this->assertTrue(
                $target->lt($window[1]),
                "Target {$target->format('H:i')} landed on the window end on day {$day}."
            );
        }
    }

    public function test_recently_missed_window_reports_a_slot_closed_without_publishing(): void
    {
        $scheduler = new FacebookWindowScheduler;

        // First run after the window closes, nothing sent - the slot is flagged.
        $window = $scheduler->recentlyMissedWindow(Carbon::parse('2026-06-21 20:00:30'));
        $this->assertNotNull($window);
        $this->assertSame('18:00', $window[0]->format('H:i'));
        $this->assertSame('20:00', $window[1]->format('H:i'));

        // More than a minute past the close it is no longer freshly missed.
        $this->assertNull($scheduler->recentlyMissedWindow(Carbon::parse('2026-06-21 20:01:30')));

        // Still inside the window it has not been missed yet.
        $this->assertNull($scheduler->recentlyMissedWindow(Carbon::parse('2026-06-21 19:30:00')));
    }

    public function test_recently_missed_window_is_silent_when_the_slot_published(): void
    {
        $scheduler = new FacebookWindowScheduler;

        NewsArticle::create([
            'source_name' => 'Test',
            'title' => 'Test',
            'url' => 'https://example.test/'.uniqid(),
            'status' => ArticleStatus::Published->value,
            'posted_at' => Carbon::parse('2026-06-21 19:33'),
        ]);

        $this->assertNull($scheduler->recentlyMissedWindow(Carbon::parse('2026-06-21 20:00:30')));
    }

    public function test_due_only_after_target_and_at_most_once_per_window(): void
    {
        $scheduler = new FacebookWindowScheduler;
        $window = $scheduler->currentWindow(Carbon::parse('2026-06-21 09:30'));
        $target = $scheduler->targetMinute(Carbon::parse('2026-06-21 09:00'), $window);

        $this->assertTrue($scheduler->isDue($target->copy()));

        NewsArticle::create([
            'source_name' => 'Test',
            'title' => 'Test',
            'url' => 'https://example.test/'.uniqid(),
            'status' => ArticleStatus::Published->value,
            'posted_at' => $target->copy(),
        ]);

        $this->assertFalse($scheduler->isDue($target->copy()->addMinutes(5)));
    }
}
