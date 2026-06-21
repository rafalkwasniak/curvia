@extends('layouts.panel')

@section('content')
<div class="mb-6 flex items-center justify-between">
    <a href="{{ route('articles.index') }}" class="text-sm text-gray-600 hover:text-gray-900">&larr; {{ __('Back to list') }}</a>
    <span class="rounded-full px-3 py-1 text-xs font-medium {{ $article->status->badgeClasses() }}">
        {{ $article->status->label() }}
    </span>
</div>

@if (session('status'))
    <div class="mb-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
@endif
@if (session('error'))
    <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
@endif

<div class="grid gap-6 md:grid-cols-2">
    <div class="rounded-lg bg-white p-6 shadow-sm">
        <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500">{{ __('Original') }}</h2>
        <p class="mb-1 text-xs text-gray-500">{{ $article->source_name }} · {{ $article->published_at?->format('Y-m-d H:i') }}</p>
        <h3 class="mb-3 font-medium">{{ $article->title }}</h3>
        @if ($article->excerpt)
            <p class="mb-4 text-sm text-gray-600">{{ $article->excerpt }}</p>
        @endif
        <a href="{{ $article->url }}" target="_blank" rel="noopener noreferrer"
            class="text-sm text-blue-600 hover:underline">{{ __('Open original article') }} &rarr;</a>
    </div>

    <div class="rounded-lg bg-white p-6 shadow-sm">
        <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500">{{ __('Generated post') }}</h2>
        @if ($article->ai_post)
            <h3 class="mb-3 font-medium">{{ $article->ai_title }}</h3>
            <div class="whitespace-pre-line rounded-md bg-gray-50 p-4 text-sm text-gray-800">{{ $article->ai_post }}</div>
        @else
            <p class="text-sm text-gray-500">{{ __('Not generated yet.') }}</p>
        @endif
    </div>
</div>

@if ($article->ai_post)
<div class="mt-6 rounded-lg bg-white p-6 shadow-sm">
    <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500">{{ __('Generated image') }}</h2>

    @if ($article->ai_image_path)
        <img src="{{ Storage::disk('public')->url($article->ai_image_path) }}" alt="{{ $article->ai_title }}"
            class="mb-4 w-full max-w-2xl rounded-md border border-gray-200" style="max-width:42rem">
    @else
        <p class="mb-4 text-sm text-gray-500">{{ __('No image yet.') }}</p>
    @endif

    @if ($article->ai_image_prompt)
        <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('Image prompt (EN)') }}</p>
        <p class="mb-4 max-w-2xl text-sm italic text-gray-600" style="max-width:42rem;font-style:italic">{{ $article->ai_image_prompt }}</p>
    @endif

    <form method="POST" action="{{ route('articles.generate-image', $article) }}">
        @csrf
        <button type="submit" class="rounded-md border border-gray-300 px-4 py-2 text-sm hover:bg-gray-50">
            {{ $article->ai_image_path ? __('Regenerate image') : __('Generate image') }}
        </button>
    </form>
</div>
@endif

<div class="mt-6 flex flex-wrap items-center gap-3">
    @if ($article->ai_post)
        @if ($article->status === \App\Enums\ArticleStatus::Approved)
            <form method="POST" action="{{ route('articles.unapprove', $article) }}">
                @csrf
                <button type="submit" class="rounded-md bg-amber-100 px-4 py-2 text-sm font-medium text-amber-800">
                    {{ __('Back to review') }}
                </button>
            </form>
        @else
            <form method="POST" action="{{ route('articles.accept', $article) }}">
                @csrf
                <button type="submit" class="rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">
                    {{ __('Approve') }}
                </button>
            </form>
        @endif

        <form method="POST" action="{{ route('articles.reject', $article) }}">
            @csrf
            <button type="submit" class="rounded-md border border-gray-300 px-4 py-2 text-sm hover:bg-gray-50">
                {{ __('Reject') }}
            </button>
        </form>

        @if ($article->ai_image_path)
            <form method="POST" action="{{ route('articles.publish', $article) }}">
                @csrf
                <button type="submit" class="rounded-md px-4 py-2 text-sm font-medium text-white" style="background-color:#1877F2">
                    {{ __('Publish to Facebook') }}
                </button>
            </form>
        @endif
    @endif

    <form method="POST" action="{{ route('articles.generate', $article) }}">
        @csrf
        <button type="submit" class="rounded-md border border-gray-300 px-4 py-2 text-sm hover:bg-gray-50">
            {{ $article->ai_post ? __('Regenerate') : __('Generate post') }}
        </button>
    </form>
</div>
@endsection
