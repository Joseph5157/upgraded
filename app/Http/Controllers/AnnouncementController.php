<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\AnnouncementDismissal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AnnouncementController extends Controller
{
    // Admin — list all announcements
    public function index()
    {
        $announcements = Announcement::with('creator')->latest()->get();
        return view('admin.announcements', compact('announcements'));
    }

    // Admin — create announcement
    public function store(Request $request)
    {
        $request->validate([
            'title'      => 'required|string|max:255',
            'message'    => 'required|string|max:1000',
            'target'     => 'required|in:all,vendor,client',
            'type'       => 'required|in:info,warning,success,danger',
            'expires_at' => 'nullable|date|after:now',
        ]);

        Announcement::create([
            'title'      => $request->title,
            'message'    => $request->message,
            'target'     => $request->target,
            'type'       => $request->type,
            'active'     => true,
            'expires_at' => $request->expires_at,
            'created_by' => Auth::id(),
        ]);

        return back()->with('success', 'Announcement broadcast successfully.');
    }

    // Admin — toggle active/inactive
    public function toggle(Announcement $announcement)
    {
        $announcement->update(['active' => !$announcement->active]);
        return back()->with('success', 'Announcement status updated.');
    }

    // Admin — delete announcement
    public function destroy(Announcement $announcement)
    {
        $announcement->delete();
        return back()->with('success', 'Announcement deleted.');
    }

    // Any user — dismiss an announcement
    public function dismiss(Announcement $announcement)
    {
        AnnouncementDismissal::firstOrCreate([
            'announcement_id' => $announcement->id,
            'user_id'         => Auth::id(),
        ], [
            'dismissed_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }
}