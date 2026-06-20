@extends('layouts.panel')

@section('content')
<div class="max-w-lg">
    <h1 class="mb-6 text-2xl font-semibold">{{ __('My account') }}</h1>

    @if (session('status'))
        <div class="mb-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('profile.update') }}" class="space-y-4 rounded-lg bg-white p-6 shadow-sm">
        @csrf

        <div>
            <label for="name" class="mb-1 block text-sm font-medium">{{ __('Name') }}</label>
            <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required
                class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-gray-900 focus:outline-none">
            @error('name')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="email" class="mb-1 block text-sm font-medium">{{ __('Email') }}</label>
            <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required
                class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-gray-900 focus:outline-none">
            @error('email')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <hr class="border-gray-200">

        <div>
            <label for="password" class="mb-1 block text-sm font-medium">{{ __('New password') }}</label>
            <input id="password" name="password" type="password" autocomplete="new-password"
                class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-gray-900 focus:outline-none">
            <p class="mt-1 text-xs text-gray-500">{{ __('Leave blank to keep your current password.') }}</p>
            @error('password')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password_confirmation" class="mb-1 block text-sm font-medium">{{ __('Confirm new password') }}</label>
            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password"
                class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-gray-900 focus:outline-none">
        </div>

        <button type="submit"
            class="rounded-md bg-gray-900 px-4 py-2 font-medium text-white hover:bg-gray-800">
            {{ __('Save changes') }}
        </button>
    </form>
</div>
@endsection
