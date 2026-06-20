@extends('layouts.app')

@section('body')
<div class="flex min-h-screen items-center justify-center px-4">
    <div class="w-full max-w-sm">
        <h1 class="mb-6 text-center text-2xl font-semibold tracking-tight">{{ config('app.name') }}</h1>

        <form method="POST" action="{{ route('login') }}" class="space-y-4 rounded-lg bg-white p-6 shadow-sm">
            @csrf

            <div>
                <label for="email" class="mb-1 block text-sm font-medium">{{ __('Email') }}</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                    class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-gray-900 focus:outline-none">
                @error('email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="mb-1 block text-sm font-medium">{{ __('Password') }}</label>
                <input id="password" name="password" type="password" required
                    class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-gray-900 focus:outline-none">
                @error('password')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <label class="flex items-center gap-2 text-sm">
                <input name="remember" type="checkbox" class="rounded border-gray-300">
                {{ __('Remember me') }}
            </label>

            <button type="submit"
                class="w-full rounded-md bg-gray-900 px-4 py-2 font-medium text-white hover:bg-gray-800">
                {{ __('Sign in') }}
            </button>
        </form>
    </div>
</div>
@endsection
