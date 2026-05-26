<?php

declare(strict_types=1);

use App\Http\Controllers\Tenant\AssetController;
use App\Http\Controllers\Tenant\AssetQrCodeController;
use App\Http\Controllers\Tenant\DashboardController;
use App\Http\Controllers\Tenant\FieldLogController;
use App\Http\Controllers\Tenant\FieldLogPdfController;
use App\Http\Controllers\Tenant\FinancialTransactionController;
use App\Http\Controllers\Tenant\GlobalSearchController;
use App\Http\Controllers\Tenant\PlotController;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

Route::domain('{tenant_domain}')
    ->where(['tenant_domain' => '^(?!localhost$)(?!127\\.0\\.0\\.1$).+'])
    ->middleware([
        'web',
        InitializeTenancyByDomain::class,
        PreventAccessFromCentralDomains::class,
    ])->group(function () {

        // Rotas de autenticação do Breeze no contexto de tenant
        require __DIR__.'/auth.php';

        Route::get('/', function () {
            if (auth()->check()) {
                return redirect()->route('dashboard');
            }

            return redirect()->route('login');
        });

        // ─── Rotas autenticadas do tenant ────────────────────────────────────────
        Route::middleware(['auth', 'verified'])->group(function () {

            Route::get('/dashboard', DashboardController::class)
                ->name('dashboard');

            // Ativos agrícolas
            Route::resource('assets', AssetController::class)
                ->except(['show']);
            Route::get('assets/{asset}/qrcode', AssetQrCodeController::class)
                ->name('assets.qrcode');

            // Talhões
            Route::resource('plots', PlotController::class)
                ->except(['show']);

            // Caderno de Campo — exportação antes do resource (evita conflito de parâmetro)
            Route::get('field-logs/export/pdf', FieldLogPdfController::class)
                ->name('field-logs.export.pdf');
            Route::resource('field-logs', FieldLogController::class)
                ->except(['show']);

            // Busca global (Spotlight — Ctrl+K)
            Route::get('/search', GlobalSearchController::class)
                ->name('search');

            // Financeiro
            Route::resource('financial-transactions', FinancialTransactionController::class)
                ->except(['show']);
        });
    });
