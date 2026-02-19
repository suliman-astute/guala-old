<?php

namespace App\Providers;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use JeroenNoten\LaravelAdminLte\Events\BuildingMenu;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(Dispatcher $events): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
        if (
            strpos(request()->getHost(), 'ngrok-free.dev') !== false ||
            strpos(request()->getHost(), 'trycloudflare.com') !== false
        ) {
            URL::forceScheme('https');
        }
        Gate::define('ADMIN', function () {
            return Auth::user()->admin;
        });




        $events->listen(BuildingMenu::class, function (BuildingMenu $event) {

            if (!Auth::user()->admin) {


                $pivot = "";


                foreach (Auth::user()->active_apps()->orderby("site_id")->orderby("name_en")->get() as $active_app) {

                    if ($pivot != $active_app->site->name) {
                        $pivot = $active_app->site->name;
                        $event->menu->add([
                            'header' => $active_app->site->name,
                        ]);
                    }

                    $event->menu->add(

                        [
                            'text' => $active_app->name,
                            'url' => '/' . $active_app->code,
                            'icon' => 'fas fa-fw fa-circle',
                        ],
                    );
                }
            }
        });
    }
}
