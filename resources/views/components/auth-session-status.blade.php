@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'font-medium text-sm']) }} style="color:#34d399;">
        {{ $status }}
    </div>
@endif
