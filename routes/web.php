<?php

use App\Http\Controllers\ProfileController;
use App\Models\Tenant;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

foreach (config('tenancy.central_domains', ['localhost', '127.0.0.1']) as $domain) {
    Route::domain($domain)->group(function () {
        Route::get('/', function () {
            if (auth()->check()) {
                return redirect()->route('dashboard');
            }

            return redirect()->route('login');
        });

        Route::get('/dashboard', function () {
            return Inertia::render('CentralDashboard', [
                'tenantCount' => Tenant::count(),
            ]);
        })->middleware(['auth', 'verified'])->name('dashboard');

        Route::middleware('auth')->group(function () {
            Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
            Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
            Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
        });

        require __DIR__.'/auth.php';
    });
}
