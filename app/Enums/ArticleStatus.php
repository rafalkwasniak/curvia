<?php

namespace App\Enums;

enum ArticleStatus: string
{
    case New = 'new';
    case Generated = 'generated';
    case WaitingReview = 'waiting_review';
    case Approved = 'approved';
    case Published = 'published';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Nowy',
            self::Generated => 'Wygenerowany',
            self::WaitingReview => 'Do akceptacji',
            self::Approved => 'Zaakceptowany',
            self::Published => 'Opublikowany',
            self::Rejected => 'Odrzucony',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::WaitingReview => 'bg-amber-100 text-amber-800',
            self::Approved => 'bg-green-100 text-green-800',
            self::Published => 'bg-blue-100 text-blue-800',
            self::Rejected => 'bg-red-100 text-red-700',
            default => 'bg-gray-100 text-gray-600',
        };
    }
}
