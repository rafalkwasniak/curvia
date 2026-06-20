@extends('layouts.panel')

@section('content')
<div class="max-w-lg">
    <h1 class="mb-6 text-2xl font-semibold">{{ __('Edit source') }}</h1>

    <form method="POST" action="{{ route('rss.update', $source) }}" class="space-y-4 rounded-lg bg-white p-6 shadow-sm">
        @csrf

        <div>
            <label for="name" class="mb-1 block text-sm font-medium">{{ __('Name') }}</label>
            <input id="name" name="name" type="text" value="{{ old('name', $source->name) }}" required
                class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-gray-900 focus:outline-none">
            @error('name')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="url" class="mb-1 block text-sm font-medium">{{ __('Feed URL') }}</label>
            <input id="url" name="url" type="url" value="{{ old('url', $source->url) }}" required
                class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-gray-900 focus:outline-none">
            @error('url')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <label class="flex items-center gap-2 text-sm">
            <input name="active" type="checkbox" value="1" @checked(old('active', $source->active)) class="rounded border-gray-300">
            {{ __('Active') }}
        </label>

        <div class="flex items-center gap-3">
            <button type="submit"
                class="rounded-md bg-gray-900 px-4 py-2 font-medium text-white hover:bg-gray-800">
                {{ __('Save changes') }}
            </button>
            <a href="{{ route('rss.index') }}" class="text-sm text-gray-600 hover:text-gray-900">{{ __('Cancel') }}</a>
        </div>
    </form>
</div>
@endsection
