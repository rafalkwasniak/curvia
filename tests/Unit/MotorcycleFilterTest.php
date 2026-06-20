<?php

namespace Tests\Unit;

use App\Services\MotorcycleFilter;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MotorcycleFilterTest extends TestCase
{
    private MotorcycleFilter $filter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->filter = new MotorcycleFilter;
    }

    /**
     * @return array<string, array{string}>
     */
    public static function motorcycleTitles(): array
    {
        return [
            'two-stroke + motogp' => ['This Tiny Two-Stroke Is A Reminder Of How MotoGP Champions Used To Get Their Start'],
            'brand in name' => ['Indian Motorcycle adds Holywood Service Station to growing UK network'],
            'ducati' => ['Ducati\'s past rolls back into Misano for WDW 2026'],
            'generic word' => ['First ride: the new adventure bike everyone is talking about'],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function nonMotorcycleTitles(): array
    {
        return [
            'phone' => ['Apple unveils the new iPhone with a bigger screen'],
            'finance' => ['Stock market hits record high amid rate cuts'],
            'laptops' => ['The best laptops you can buy in 2026'],
            'empty' => ['   '],
        ];
    }

    #[DataProvider('motorcycleTitles')]
    public function test_it_matches_motorcycle_titles(string $title): void
    {
        $this->assertTrue($this->filter->matches($title));
    }

    #[DataProvider('nonMotorcycleTitles')]
    public function test_it_rejects_non_motorcycle_titles(string $title): void
    {
        $this->assertFalse($this->filter->matches($title));
    }

    public function test_substring_alone_does_not_match(): void
    {
        // "moto" must not match inside an unrelated word.
        $this->assertFalse($this->filter->matches('Emotion and locomotion in modern design'));
    }
}
