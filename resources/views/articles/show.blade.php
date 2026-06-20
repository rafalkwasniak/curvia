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
        <h3 class="mb-3 font-medium">{{ $article->ai_title }}</h3>
        <div class="whitespace-pre-line rounded-md bg-gray-50 p-4 text-sm text-gray-800">{{ $article->ai_post }}</div>
    </div>
</div>

<div class="mt-6 flex flex-wrap items-center gap-3">
    <form method="POST" action="{{ route('articles.accept', $article) }}">
        @csrf
        <button type="submit" class="rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">
            {{ __('Approve') }}
        </button>
    </form>

    <form method="POST" action="{{ route('articles.reject', $article) }}">
        @csrf
        <button type="submit" class="rounded-md border border-gray-300 px-4 py-2 text-sm hover:bg-gray-50">
            {{ __('Reject') }}
        </button>
    </form>

    <form method="POST" action="{{ route('articles.regenerate', $article) }}">
        @csrf
        <button type="submit" class="rounded-md border border-gray-300 px-4 py-2 text-sm hover:bg-gray-50">
            {{ __('Regenerate') }}
        </button>
    </form>
</div>
@endsection
