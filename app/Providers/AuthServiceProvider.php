<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules\Password;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        'App\Models\Post' => 'App\Policies\PostPolicy',
        'App\Models\Profile' => 'App\Policies\ProfilePolicy',
        'App\Models\Tag' => 'App\Policies\TagPolicy',
        'App\Models\User' => 'App\Policies\UserPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Password::defaults(function () {
            return Password::min(6);
        });

        Gate::define('uploadFiles', function ($user) {
            return true;
        });
    }
}
