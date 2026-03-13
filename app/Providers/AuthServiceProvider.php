<?php

namespace App\Providers;

use App\Models\Client;
use App\Models\Order;
use App\Models\RefundRequest;
use App\Models\TopupRequest;
use App\Models\User;
use App\Models\VendorPayout;
use App\Policies\ClientPolicy;
use App\Policies\OrderPolicy;
use App\Policies\RefundRequestPolicy;
use App\Policies\TopupRequestPolicy;
use App\Policies\UserPolicy;
use App\Policies\VendorPayoutPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model-to-policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Order::class         => OrderPolicy::class,
        User::class          => UserPolicy::class,
        Client::class        => ClientPolicy::class,
        RefundRequest::class => RefundRequestPolicy::class,
        TopupRequest::class  => TopupRequestPolicy::class,
        VendorPayout::class  => VendorPayoutPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Gates for actions not tied to a specific model instance
        Gate::define('create-admin', fn (User $user): bool => $user->isSuperAdmin());
        Gate::define('manage-payouts', fn (User $user): bool => $user->role === 'admin');
        Gate::define('manage-refunds', fn (User $user): bool => $user->role === 'admin');
        Gate::define('manage-announcements', fn (User $user): bool => $user->role === 'admin');
        Gate::define('view-financial-matrix', fn (User $user): bool => $user->role === 'admin');
    }
}
