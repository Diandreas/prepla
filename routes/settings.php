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
    Route::post('settings/subscription/upgrade', [\App\Http\Controllers\Settings\SubscriptionController::class, 'upgrade'])->name('subscription.upgrade');
    Route::post('settings/subscription/cancel', [\App\Http\Controllers\Settings\SubscriptionController::class, 'cancel'])->name('subscription.cancel');

    Route::get('settings/appearance', function () {
        return Inertia::render('settings/appearance');
    })->name('appearance');
});
