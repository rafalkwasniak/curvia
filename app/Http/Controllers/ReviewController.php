<?php

namespace App\Http\Controllers;

use App\Enums\ArticleStatus;
use App\Models\NewsArticle;
use App\Services\ArticleScraper;
use App\Services\ImageGenerator;
use App\Services\PostGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Throwable;

class ReviewController extends Controller
{
    public function index(): View
    {
        return view('articles.index', [
            'articles' => NewsArticle::where('status', '!=', ArticleStatus::Rejected)
                ->orderByDesc('published_at')
                ->orderByDesc('id')
                ->paginate(10),
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

    public function unapprove(NewsArticle $article): RedirectResponse
    {
        $article->update(['status' => ArticleStatus::WaitingReview]);

        return back()->with('status', __('Moved back to review.'));
    }

    public function generate(NewsArticle $article, ArticleScraper $scraper, PostGenerator $generator): RedirectResponse
    {
        try {
            if ($article->content === null) {
                $content = $scraper->scrape($article->url);

                if ($content === null) {
                    return back()->with('error', __('Could not fetch article content.'));
                }

                $article->content = $content;
                $article->save();
            }

            $generated = $generator->generate($article);

            $article->update([
                'ai_title' => $generated['title'],
                'ai_post' => $generated['post'],
                'status' => ArticleStatus::WaitingReview,
            ]);

            return back()->with('status', __('Post generated.'));
        } catch (Throwable) {
            return back()->with('error', __('Generation failed, try again.'));
        }
    }

    public function generateImage(NewsArticle $article, ImageGenerator $generator): RedirectResponse
    {
        if ($article->ai_post === null) {
            return back()->with('error', __('Generate the post first.'));
        }

        try {
            $previous = $article->ai_image_path;
            $generated = $generator->generate($article);

            $article->update([
                'ai_image_path' => $generated['path'],
                'ai_image_prompt' => $generated['prompt'],
            ]);

            if ($previous !== null && $previous !== $generated['path']) {
                Storage::disk('public')->delete($previous);
            }

            return back()->with('status', __('Image generated.'));
        } catch (Throwable) {
            return back()->with('error', __('Image generation failed, try again.'));
        }
    }
}
