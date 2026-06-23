@extends('layouts.panel')

@section('content')
<h1 class="mb-6 text-2xl font-semibold">{{ __('Articles') }}</h1>

@if (session('status'))
    <div class="mb-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
        {{ session('status') }}
    </div>
@endif

<div class="overflow-hidden rounded-lg bg-white shadow-sm">
    <table class="w-full text-left text-sm">
        <thead class="border-b border-gray-200 text-gray-500">
            <tr>
                <th class="px-4 py-3 font-medium">{{ __('Image') }}</th>
                <th class="px-4 py-3 font-medium">{{ __('Title') }}</th>
                <th class="px-4 py-3 font-medium">{{ __('Status') }}</th>
                <th class="px-4 py-3 font-medium">{{ __('Date') }}</th>
                <th class="px-4 py-3 font-medium text-right">{{ __('Actions') }}</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($articles as $article)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">
                        <a href="{{ route('articles.show', $article) }}">
                            @if ($article->ai_image_path)
                                <img src="{{ Storage::disk('public')->url($article->ai_image_path) }}"
                                    alt="{{ $article->ai_title ?? $article->title }}"
                                    class="h-11 w-20 rounded object-cover">
                            @else
                                <div class="flex h-11 w-20 items-center justify-center rounded bg-gray-100 text-xs text-gray-400">—</div>
                            @endif
                        </a>
                    </td>
                    <td class="px-4 py-3 font-medium">
                        <a href="{{ route('articles.show', $article) }}" class="hover:underline">
                            {{ $article->ai_title ?? $article->title }}
                        </a>
                    </td>
                    <td class="px-4 py-3">
                        <span class="rounded-full px-2 py-1 text-xs font-medium {{ $article->status->badgeClasses() }}">
                            {{ $article->status->label() }}
                        </span>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-gray-500">{{ $article->published_at?->format('Y-m-d') ?? '—' }}</td>
                    <td class="px-4 py-3 text-right">
                        @if ($article->status !== \App\Enums\ArticleStatus::Published)
                            <form action="{{ route('articles.reject', $article) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit"
                                    title="{{ __('Reject') }}"
                                    aria-label="{{ __('Reject') }}"
                                    class="rounded-md p-2 text-gray-400 transition hover:bg-red-50 hover:text-red-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 7.5h12M9.5 7.5V6a1.5 1.5 0 0 1 1.5-1.5h2A1.5 1.5 0 0 1 14.5 6v1.5m-7 0 .7 11a1.5 1.5 0 0 0 1.5 1.4h4.6a1.5 1.5 0 0 0 1.5-1.4l.7-11" />
                                    </svg>
                                </button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-6 text-center text-gray-500">{{ __('No generated posts yet.') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">
    {{ $articles->links() }}
</div>
@endsection
