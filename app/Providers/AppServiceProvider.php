<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

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
    public function boot(): void
    {
        Schema::defaultStringLength(125);

        // Subdirectory installs (e.g. /netbill/public) need an explicit
        // root or route()/url() generate host-only URLs that 404.
        if (str_starts_with((string) config('app.url'), 'http')) {
            URL::forceRootUrl(rtrim(config('app.url'), '/'));
        }
    }
}
