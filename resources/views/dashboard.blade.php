@extends('layouts.app')

@section('body')
<div class="mx-auto max-w-3xl px-4 py-10">
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-semibold">{{ __('Dashboard') }}</h1>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm hover:bg-gray-50">
                {{ __('Log out') }}
            </button>
        </form>
    </div>

    <p class="text-gray-600">{{ __('Welcome, :name', ['name' => auth()->user()->name]) }}</p>
</div>
@endsection
