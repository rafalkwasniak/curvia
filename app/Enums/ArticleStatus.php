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
}
