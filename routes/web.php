<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Auth::routes(['register' => false]);

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\HomeController::class, 'index'])->name('dashboard');
    Route::get('manual-book', [App\Http\Controllers\ManualBookController::class, 'index'])->name('manual-book.index');
    Route::resource('clients', App\Http\Controllers\ClientController::class);
    Route::resource('applications', App\Http\Controllers\ApplicationController::class);
    Route::post('applications/{application}/gateway/sync', [App\Http\Controllers\ApplicationGatewayController::class, 'sync'])
        ->name('applications.gateway.sync');
    Route::post('applications/{application}/gateway/retry', [App\Http\Controllers\ApplicationGatewayController::class, 'retry'])
        ->name('applications.gateway.retry');
    Route::put('applications/{application}/access', [App\Http\Controllers\ApplicationAccessController::class, 'update'])
        ->name('applications.access.update');
    Route::get('gateway', [App\Http\Controllers\GatewayController::class, 'index'])->name('gateway.index');
    Route::post('gateway/agent/start', [App\Http\Controllers\GatewayController::class, 'startAgent'])->name('gateway.agent.start');
    Route::post('gateway/agent/restart', [App\Http\Controllers\GatewayController::class, 'restartAgent'])->name('gateway.agent.restart');
    Route::get('monitoring', [App\Http\Controllers\MonitoringController::class, 'index'])->name('monitoring.index');
    Route::post('monitoring/health-check', [App\Http\Controllers\MonitoringController::class, 'runHealthChecks'])->name('monitoring.health-check');

    Route::prefix('billing')->name('billing.')->group(function () {
        Route::get('plans', [App\Http\Controllers\SubscriptionPlanController::class, 'index'])->name('plans.index');
        Route::get('plans/create', [App\Http\Controllers\SubscriptionPlanController::class, 'create'])->name('plans.create');
        Route::post('plans', [App\Http\Controllers\SubscriptionPlanController::class, 'store'])->name('plans.store');

        Route::get('subscriptions', [App\Http\Controllers\SubscriptionController::class, 'index'])->name('subscriptions.index');
        Route::get('subscriptions/create', [App\Http\Controllers\SubscriptionController::class, 'create'])->name('subscriptions.create');
        Route::post('subscriptions', [App\Http\Controllers\SubscriptionController::class, 'store'])->name('subscriptions.store');
        Route::get('subscriptions/{subscription}/edit', [App\Http\Controllers\SubscriptionController::class, 'edit'])->name('subscriptions.edit');
        Route::put('subscriptions/{subscription}', [App\Http\Controllers\SubscriptionController::class, 'update'])->name('subscriptions.update');
        Route::get('subscriptions/{subscription}', [App\Http\Controllers\SubscriptionController::class, 'show'])->name('subscriptions.show');
        Route::delete('subscriptions/{subscription}/invoices/{invoice}', [App\Http\Controllers\InvoiceController::class, 'destroy'])->name('subscriptions.invoices.destroy');

        Route::get('invoices', [App\Http\Controllers\InvoiceController::class, 'index'])->name('invoices.index');
        Route::get('invoices/export', [App\Http\Controllers\InvoiceController::class, 'export'])->name('invoices.export');
        Route::get('invoices/{invoice}/pdf', [App\Http\Controllers\InvoiceController::class, 'pdf'])->name('invoices.pdf');
        Route::get('invoices/{invoice}/download', [App\Http\Controllers\InvoiceController::class, 'download'])->name('invoices.download');
        Route::get('invoices/{invoice}', [App\Http\Controllers\InvoiceController::class, 'show'])->name('invoices.show');
        Route::post('subscriptions/{subscription}/invoices', [App\Http\Controllers\InvoiceController::class, 'store'])->name('invoices.store');

        Route::get('payments', [App\Http\Controllers\PaymentController::class, 'index'])->name('payments.index');
        Route::get('payments/create', [App\Http\Controllers\PaymentController::class, 'create'])->name('payments.create');
        Route::post('payments', [App\Http\Controllers\PaymentController::class, 'store'])->name('payments.store');
        Route::post('payments/{payment}/verify', [App\Http\Controllers\PaymentController::class, 'verify'])->name('payments.verify');
        Route::post('payments/{payment}/reject', [App\Http\Controllers\PaymentController::class, 'reject'])->name('payments.reject');
    });
});

Route::redirect('/home', '/dashboard');
