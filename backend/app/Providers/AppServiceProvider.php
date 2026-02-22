<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $fetcher = $this->app->environment('testing')
            ? \App\Services\SERP\HttpSERPFetcher::class
            : \App\Services\SERP\MockSERPFetcher::class;

        $this->app->bind(
            \App\Services\SERPFetcherInterface::class,
            $fetcher
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
