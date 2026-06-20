<?php

namespace App\Http\Controllers;

use App\Enums\ArticleStatus;
use App\Models\NewsArticle;
use App\Services\PostGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Throwable;

class ReviewController extends Controller
{
    public function index(): View
    {
        return view('articles.index', [
            'articles' => NewsArticle::whereNotNull('ai_post')
                ->latest('published_at')
                ->paginate(20),
        ]);
    }

    public function show(NewsArticle $article): View
    {
        return view('articles.show', ['article' => $article]);
    }

    public function accept(NewsArticle $article): RedirectResponse
    {
        $article->update(['status' => ArticleStatus::Approved]);

        return back()->with('status', __('Post approved.'));
    }

    public function reject(NewsArticle $article): RedirectResponse
    {
        $article->update(['status' => ArticleStatus::Rejected]);

        return back()->with('status', __('Post rejected.'));
    }

    public function regenerate(NewsArticle $article, PostGenerator $generator): RedirectResponse
    {
        if ($article->content === null) {
            return back()->with('error', __('No article content to generate from.'));
        }

        try {
            $generated = $generator->generate($article);

            $article->update([
                'ai_title' => $generated['title'],
                'ai_post' => $generated['post'],
                'status' => ArticleStatus::WaitingReview,
            ]);

            return back()->with('status', __('Post regenerated.'));
        } catch (Throwable) {
            return back()->with('error', __('Generation failed, try again.'));
        }
    }
}
