@extends('layouts.app')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50">
    <div class="max-w-md w-full bg-white p-8 rounded-xl shadow text-center">
        <h1 class="text-6xl font-bold text-blue-600 mb-4">419</h1>
        <h2 class="text-2xl font-semibold mb-4">Session Expired</h2>
        <p class="text-gray-600 mb-8">
            Your session timed out for security reasons.<br>
            Please refresh the page and try again.
        </p>
        <div class="flex gap-4 justify-center">
            <a href="/"
               class="px-6 py-3 border border-gray-300 rounded-lg hover:bg-gray-50">
                Go Home
            </a>
            <a href="{{ route('login') }}"
               class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                Log In
            </a>
        </div>
    </div>
</div>
@endsection
