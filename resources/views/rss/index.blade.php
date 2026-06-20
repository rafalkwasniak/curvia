@extends('layouts.panel')

@section('content')
<h1 class="mb-6 text-2xl font-semibold">{{ __('RSS sources') }}</h1>

@if (session('status'))
    <div class="mb-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
        {{ session('status') }}
    </div>
@endif

<form method="POST" action="{{ route('rss.store') }}" class="mb-8 rounded-lg bg-white p-6 shadow-sm">
    @csrf
    <h2 class="mb-4 font-medium">{{ __('Add source') }}</h2>

    <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
        <div class="sm:w-1/3">
            <label for="name" class="mb-1 block text-sm font-medium">{{ __('Name') }}</label>
            <input id="name" name="name" type="text" value="{{ old('name') }}" required
                class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-gray-900 focus:outline-none">
            @error('name')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex-1">
            <label for="url" class="mb-1 block text-sm font-medium">{{ __('Feed URL') }}</label>
            <input id="url" name="url" type="url" value="{{ old('url') }}" required placeholder="https://..."
                class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-gray-900 focus:outline-none">
            @error('url')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <button type="submit"
        class="mt-4 rounded-md bg-gray-900 px-4 py-2 font-medium text-white hover:bg-gray-800">
        {{ __('Add source') }}
    </button>
</form>

<div class="overflow-hidden rounded-lg bg-white shadow-sm">
    <table class="w-full text-left text-sm">
        <thead class="border-b border-gray-200 text-gray-500">
            <tr>
                <th class="px-4 py-3 font-medium">{{ __('Name') }}</th>
                <th class="px-4 py-3 font-medium">{{ __('Feed URL') }}</th>
                <th class="px-4 py-3 font-medium">{{ __('Status') }}</th>
                <th class="px-4 py-3 font-medium">{{ __('Last fetched') }}</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($sources as $source)
                <tr>
                    <td class="px-4 py-3 font-medium">{{ $source->name }}</td>
                    <td class="px-4 py-3 text-gray-500">
                        <span class="block max-w-xs truncate">{{ $source->url }}</span>
                    </td>
                    <td class="px-4 py-3">
                        @if ($source->active)
                            <span class="rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-800">{{ __('Active') }}</span>
                        @else
                            <span class="rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600">{{ __('Disabled') }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-gray-500">
                        {{ $source->last_fetched_at?->diffForHumans() ?? __('Never') }}
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('rss.edit', $source) }}" class="text-gray-600 hover:text-gray-900">{{ __('Edit') }}</a>

                            <form method="POST" action="{{ route('rss.toggle', $source) }}">
                                @csrf
                                <button type="submit" class="text-gray-600 hover:text-gray-900">
                                    {{ $source->active ? __('Disable') : __('Enable') }}
                                </button>
                            </form>

                            <form method="POST" action="{{ route('rss.destroy', $source) }}"
                                onsubmit="return confirm('{{ __('Remove this source?') }}')">
                                @csrf
                                <button type="submit" class="text-red-600 hover:text-red-800">{{ __('Remove') }}</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-6 text-center text-gray-500">{{ __('No sources yet.') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
