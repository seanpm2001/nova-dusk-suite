<?php

namespace App\Providers;

use App\Nova\Dashboards\Main;
use App\Nova\Dashboards\Posts;
use Carbon\CarbonInterval;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Laravel\Nova\Events\ServingNova;
use Laravel\Nova\Events\StartedImpersonating;
use Laravel\Nova\Events\StoppedImpersonating;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Menu\Menu;
use Laravel\Nova\Menu\MenuItem;
use Laravel\Nova\Menu\MenuSection;
use Laravel\Nova\Nova;
use Laravel\Nova\NovaApplicationServiceProvider;
use Otwell\SidebarTool\SidebarTool;

class NovaServiceProvider extends NovaApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        // Nova::remoteStyle(mix('css/nova.css'));
        // Nova::remoteScript(mix('js/nova.js'));

        Event::listen(StartedImpersonating::class, function ($event) {
            config([
                'nova.impersonation.started' => '/?'.http_build_query([
                    'impersonated' => $event->impersonated->getKey(),
                    'impersonator' => $event->impersonator->getKey(),
                ]),
            ]);
        });

        Event::listen(StoppedImpersonating::class, function ($event) {
            $resource = Nova::resourceForModel($event->impersonated);

            config([
                'nova.impersonation.stopped' => route('nova.pages.detail', [
                    'resource' => $resource::uriKey(),
                    'resourceId' => $event->impersonated->getKey(),
                ]),
            ]);
        });

        Nova::mainMenu(function (Request $request, Menu $menu) {
            if ($user = $request->user()) {
                $menu->append(
                    MenuSection::make('Account Verification', [
                        MenuItem::externalLink('Verify Using Inertia', "/tests/verify-user/{$user->id}")->method('POST', ['_token' => csrf_token()], []),
                    ])->canSee(function () use ($user) {
                        return ! $user->active;
                    })
                )->append(
                    MenuSection::make('Links', [
                        MenuItem::externalLink('Dashboard', url('/dashboard'), 'self'),
                        MenuItem::externalLink('Nova Website', 'https://nova.laravel.com')->openInNewTab(),
                    ])
                );
            }

            return $menu;
        });

        Nova::userMenu(function (Request $request, Menu $menu) {
            if ($user = $request->user()) {
                $menu->append(
                    MenuItem::make('My Account')->path('/resources/users/'.$request->user()->id)
                )->append(
                    MenuItem::externalLink('Verify Account', "/tests/verify-user/{$user->id}")->method('POST', ['_token' => csrf_token()])
                        ->canSee(function () use ($user) {
                            return ! $user->active;
                        })
                )->append(
                    MenuItem::externalLink('Dashboard', route('dashboard'), 'self')
                );
            }

            return $menu;
        });

        Field::macro('showWhen', function (bool $condition) {
            if ($condition === true) {
                $this->show();
            } else {
                $this->hide();
            }
        });

        Field::macro('showUnless', function (bool $condition) {
            if ($condition === true) {
                $this->hide();
            } else {
                $this->show();
            }
        });
    }

    /**
     * Register the Nova routes.
     *
     * @return void
     */
    protected function routes()
    {
        Nova::routes()
                ->withAuthenticationRoutes()
                ->withPasswordResetRoutes()
                ->register();
    }

    /**
     * Register the Nova gate.
     *
     * This gate determines who can access Nova in non-local environments.
     *
     * @return void
     */
    protected function gate()
    {
        Gate::define('viewNova', function ($user) {
            return true;
        });
    }

    /**
     * Get the extra dashboards that should be displayed on the Nova dashboard.
     *
     * @return array
     */
    protected function dashboards()
    {
        return [
            new Main,
            new Posts,
        ];
    }

    /**
     * Get the tools that should be listed in the Nova sidebar.
     *
     * @return array
     */
    public function tools()
    {
        return [
            (new SidebarTool)->canSee(function (Request $request) {
                return ! (optional($request->user())->isBlockedFrom('sidebarTool') || false);
            }),
        ];
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        Nova::notificationPollingInterval(CarbonInterval::days(1)->totalSeconds);

        Nova::serving(function (ServingNova $event) {
            if (! is_null($pagination = data_get($event->request->user(), 'settings.pagination'))) {
                config(['nova.pagination' => $pagination]);
            }
        });

        Nova::userTimezone(function (Request $request) {
            /** @param  string|null  $default */
            $default = config('app.timezone');

            return $request->user()->profile->timezone ?? $default;
        });
    }
}
