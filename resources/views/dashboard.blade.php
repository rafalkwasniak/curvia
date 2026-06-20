@extends('layouts.panel')

@section('content')
<h1 class="text-2xl font-semibold">{{ __('Dashboard') }}</h1>
<p class="mt-2 text-gray-600">{{ __('Welcome, :name', ['name' => auth()->user()->name]) }}</p>
@endsection
