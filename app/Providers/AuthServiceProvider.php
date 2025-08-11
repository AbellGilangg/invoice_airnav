<?php

namespace App\Providers;

use App\Models\User;
use App\Models\Invoice; // <-- Pastikan ini di-import
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Gate untuk Master
        Gate::define('is-master', function (User $user) {
            return $user->role === 'master';
        });

        // Gate untuk Admin (termasuk Master)
        Gate::define('is-admin', function (User $user) {
            return in_array($user->role, ['master', 'admin']);
        });

        // Gate untuk membuat dan mengelola bandara (hanya Master dan Admin)
        Gate::define('manage-airports', function (User $user) {
            return in_array($user->role, ['master', 'admin']);
        });
        
        // Gate untuk membuat invoice (hanya Master dan Admin)
        Gate::define('create-invoice', function (User $user) {
            return in_array($user->role, ['master', 'admin']);
        });

        // Gate untuk mengedit invoice
        // Logika: Master bisa edit semua. Admin hanya bisa edit invoice dari bandaranya sendiri.
        Gate::define('update-invoice', function (User $user, Invoice $invoice) {
            if ($user->role === 'master') {
                return true;
            }
            if ($user->role === 'admin') {
                return $user->airport_id === $invoice->airport_id;
            }
            return false;
        });

        // Gate untuk mengelola user lain (hanya Master)
        Gate::define('manage-users', function (User $user) {
            return $user->role === 'master';
        });
        Gate::define('view-invoice', function (User $user, Invoice $invoice) {
            return true; // Izinkan semua user yang sudah login untuk melihat detail
        });
    }
}