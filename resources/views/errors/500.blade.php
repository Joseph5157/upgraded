@extends('layouts.app')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50">
    <div class="max-w-md w-full bg-white p-8 rounded-xl shadow text-center">
        <h1 class="text-6xl font-bold text-red-600 mb-4">500</h1>
        <h2 class="text-2xl font-semibold mb-4">Something went wrong</h2>
        <p class="text-gray-600 mb-8">
            We hit an internal issue.<br>
            Our team has been notified. Please try again in a moment.
        </p>
        <div class="flex gap-4 justify-center">
            <a href="/"
               class="px-6 py-3 border border-gray-300 rounded-lg hover:bg-gray-50">
                Go Home
            </a>
            <button onclick="window.history.back()"
                    class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                Go Back
            </button>
        </div>
    </div>
</div>
@endsection
