<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Client;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class AdminDashboardController extends Controller
{
    public function index()
    {
        // Cache for 60 seconds — the dashboard runs 6 separate aggregate queries
        // on every page load. A short TTL keeps counts fresh while cutting DB
        // pressure dramatically under concurrent admin sessions.
        $stats = Cache::remember('admin_dashboard_stats', 60, function () {
            return [
                'total_processed_today' => Order::where('status', OrderStatus::Delivered)
                    ->whereDate('delivered_at', today())
                    ->count(),
                'pending_pool'    => Order::where('status', OrderStatus::Pending)->whereNull('claimed_by')->count(),
                'active_vendors'  => User::where('role', 'vendor')
                    ->whereHas('orders', fn($q) => $q->whereDate('delivered_at', today()))
                    ->count(),
                'new_clients_today' => Client::whereDate('created_at', today())->count(),
                'total_clients'   => Client::count(),
                'total_vendors'   => User::where('role', 'vendor')->count(),
            ];
        });

        $vendorPerformance = User::where('role', 'vendor')
            ->withCount([
                'orders as total_jobs' => fn($q) => $q->where('status', OrderStatus::Delivered),
                'orders as today_jobs' => fn($q) => $q->where('status', OrderStatus::Delivered)->whereDate('delivered_at', today()),
            ])
            ->orderByDesc('today_jobs')
            ->take(10)
            ->get();

        $recentOrders = Order::with(['client', 'vendor'])
            ->latest()
            ->take(10)
            ->get();

        $activeOrders = Order::with(['client', 'vendor', 'files'])
            ->where('status', OrderStatus::Processing)
            ->whereNotNull('claimed_by')
            ->orderBy('claimed_at', 'asc') // Oldest claimed first
            ->get();

        return view('admin.dashboard', compact('stats', 'vendorPerformance', 'recentOrders', 'activeOrders'));
    }
}
