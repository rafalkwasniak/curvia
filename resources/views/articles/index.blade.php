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
                <th class="px-4 py-3 font-medium">{{ __('Title') }}</th>
                <th class="px-4 py-3 font-medium">{{ __('Source') }}</th>
                <th class="px-4 py-3 font-medium">{{ __('Status') }}</th>
                <th class="px-4 py-3 font-medium">{{ __('Date') }}</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($articles as $article)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium">
                        <a href="{{ route('articles.show', $article) }}" class="hover:underline">
                            {{ $article->ai_title ?? $article->title }}
                        </a>
                    </td>
                    <td class="px-4 py-3 text-gray-500">{{ $article->source_name }}</td>
                    <td class="px-4 py-3">
                        <span class="rounded-full px-2 py-1 text-xs font-medium {{ $article->status->badgeClasses() }}">
                            {{ $article->status->label() }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-500">{{ $article->published_at?->format('Y-m-d H:i') ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="px-4 py-6 text-center text-gray-500">{{ __('No generated posts yet.') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">
    {{ $articles->links() }}
</div>
@endsection
