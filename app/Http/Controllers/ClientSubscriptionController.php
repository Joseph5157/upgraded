<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Enums\OrderStatus;
use Illuminate\Support\Facades\Auth;

class ClientSubscriptionController extends Controller
{
    public function index()
    {
        $user   = Auth::user();
        $client = $user->client;

        if (!$client) {
            abort(403, 'No client account linked.');
        }

        $slotsUsed = (int) $client->slots_consumed;
        $slotsRemaining = max(0, (int) $client->slots - $slotsUsed);

        $topupHistory = $client->topupRequests()
            ->latest()
            ->get();

        $refundHistory = $client->refundRequests()
            ->with('order')
            ->latest()
            ->get();

        $lastTopup = $topupHistory->where('status', 'approved')->first();

        return view('client.subscription', compact(
            'client',
            'slotsUsed',
            'slotsRemaining',
            'topupHistory',
            'refundHistory',
            'lastTopup'
        ));
    }
}
