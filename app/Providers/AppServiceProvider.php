<?php

namespace App\Providers;

use App\Models\FieldLog;
use App\Observers\FieldLogObserver;
use Illuminate\Support\Facades\Vite;
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
        Vite::prefetch(concurrency: 3);

        // Registra o Observer do Caderno de Campo.
        // Responsável por criar/sincronizar FinancialTransactions automaticamente.
        FieldLog::observe(FieldLogObserver::class);
    }
}
