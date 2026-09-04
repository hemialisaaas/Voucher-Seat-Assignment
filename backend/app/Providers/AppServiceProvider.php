<?php

namespace App\Providers;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\ServiceProvider;

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
        // The assessment spec's response bodies (e.g. {"exists": true} and
        // {"success": true, "seats": [...]}) are flat, with no top-level
        // "data" wrapper. Disable JsonResource's default wrapping so API
        // responses match that shape exactly.
        JsonResource::withoutWrapping();
    }
}
