@extends('layouts.app')

@php
    $navLink = fn (bool $active) => 'rounded-md px-3 py-2 text-sm font-medium '
        .($active ? 'bg-gray-900 text-white' : 'text-gray-700 hover:bg-gray-100');
@endphp

@section('body')
<div class="min-h-screen">
    <nav class="border-b border-gray-200 bg-white">
        <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-3">
            <div class="flex items-center gap-1">
                <span class="mr-3 font-semibold">{{ config('app.name') }}</span>
                <a href="{{ route('dashboard') }}" class="{{ $navLink(request()->routeIs('dashboard')) }}">{{ __('Dashboard') }}</a>
                <a href="{{ route('rss.index') }}" class="{{ $navLink(request()->routeIs('rss.*')) }}">{{ __('RSS sources') }}</a>
                <a href="{{ route('profile.edit') }}" class="{{ $navLink(request()->routeIs('profile.*')) }}">{{ __('My account') }}</a>
            </div>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="rounded-md border border-gray-300 px-3 py-2 text-sm hover:bg-gray-50">
                    {{ __('Log out') }}
                </button>
            </form>
        </div>
    </nav>

    <main class="mx-auto max-w-5xl px-4 py-8">
        @yield('content')
    </main>
</div>
@endsection
