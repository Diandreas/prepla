<?php

use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('auth')->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('settings/profile/learning', [ProfileController::class, 'updateLearning'])->name('profile.update_learning');
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('settings/password', [PasswordController::class, 'update'])->name('password.update');

    Route::get('settings/subscription', [\App\Http\Controllers\Settings\SubscriptionController::class, 'index'])->name('subscription.index');
    Route::post('settings/subscription/checkout', [\App\Http\Controllers\Settings\SubscriptionController::class, 'checkout'])->name('subscription.checkout');
    Route::post('settings/subscription/cancel', [\App\Http\Controllers\Settings\SubscriptionController::class, 'cancel'])->name('subscription.cancel');
    Route::post('settings/subscription/resume', [\App\Http\Controllers\Settings\SubscriptionController::class, 'resume'])->name('subscription.resume');
    Route::get('settings/subscription/invoice/{invoiceId}', [\App\Http\Controllers\Settings\SubscriptionController::class, 'downloadInvoice'])->name('subscription.invoice');
    Route::get('settings/subscription/invoices', [\App\Http\Controllers\Settings\SubscriptionController::class, 'invoices'])->name('subscription.invoices');

    Route::get('settings/appearance', function () {
        return Inertia::render('settings/appearance');
    })->name('appearance');
});
