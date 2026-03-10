@php
    $announcements = \App\Models\Announcement::activeForUser(auth()->user())->latest()->get();
@endphp

@foreach($announcements as $announcement)
    @php
        $colors = match($announcement->type) {
            'success' => ['bg' => 'bg-[#F0FDF4] dark:bg-green-500/10', 'border' => 'border-[#BBF7D0] dark:border-green-500/20', 'text' => 'text-green-700', 'icon' => 'check-circle'],
            'warning' => ['bg' => 'bg-[#FFFBEB] dark:bg-amber-500/10', 'border' => 'border-[#FDE68A] dark:border-amber-500/20', 'text' => 'text-amber-700', 'icon' => 'alert-triangle'],
            'danger'  => ['bg' => 'bg-[#FEF2F2] dark:bg-red-500/10', 'border' => 'border-[#FECACA] dark:border-red-500/20', 'text' => 'text-red-600', 'icon' => 'alert-octagon'],
            default   => ['bg' => 'bg-[#EEF2FF] dark:bg-indigo-500/10', 'border' => 'border-[#C7D2FE] dark:border-indigo-500/20', 'text' => 'text-[#4F6EF7]', 'icon' => 'megaphone'],
        };
    @endphp

    <div id="announcement-{{ $announcement->id }}"
        class="flex items-start gap-4 p-4 {{ $colors['bg'] }} border {{ $colors['border'] }} rounded-2xl">
        <i data-lucide="{{ $colors['icon'] }}" class="w-4 h-4 {{ $colors['text'] }} flex-shrink-0 mt-0.5"></i>
        <div class="flex-1 min-w-0">
            <p class="text-sm font-bold {{ $colors['text'] }}">{{ $announcement->title }}</p>
            <p class="text-xs text-[#6B7280] mt-0.5">{{ $announcement->message }}</p>
        </div>
        <button onclick="dismissAnnouncement({{ $announcement->id }})"
            class="{{ $colors['text'] }} opacity-60 hover:opacity-100 transition-opacity flex-shrink-0">
            <i data-lucide="x" class="w-4 h-4"></i>
        </button>
    </div>
@endforeach

<script>
function dismissAnnouncement(id) {
    fetch(`/announcements/${id}/dismiss`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json',
        }
    }).then(() => {
        const el = document.getElementById('announcement-' + id);
        if (el) el.remove();
    });
}
</script>