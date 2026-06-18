<?php

use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CvLocationController;
use App\Http\Controllers\CvPdfController;
use App\Http\Controllers\CvPhotoController;
use App\Http\Controllers\CvProfileController;
use App\Http\Controllers\CvPreviewController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])
        ->middleware('throttle:10,1')
        ->name('login.store');

    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register'])
        ->middleware('throttle:5,1')
        ->name('register.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])
        ->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:3,1')
        ->name('verification.send');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/cv/edit', [CvProfileController::class, 'edit'])->name('cv.edit');
    Route::post('/cv/draft', [CvProfileController::class, 'saveDraft'])->name('cv.draft.save');
    Route::post('/cv/summary', [CvProfileController::class, 'generateSummary'])->name('cv.summary.generate');
    Route::post('/cv/save-preview', [CvProfileController::class, 'saveAndPreview'])->name('cv.preview.save');
    Route::get('/cv/preview', [CvPreviewController::class, 'show'])->name('cv.preview');
    Route::get('/cv/download-pdf', [CvPdfController::class, 'download'])->name('cv.pdf.download');
    Route::get('/cv/photo', [CvPhotoController::class, 'show'])->name('cv.photo.show');
    Route::get('/cv/locations/provinces', [CvLocationController::class, 'provinces'])->name('cv.locations.provinces');
    Route::get('/cv/locations/regencies', [CvLocationController::class, 'regencies'])->name('cv.locations.regencies');
    Route::get('/cv/locations/districts', [CvLocationController::class, 'districts'])->name('cv.locations.districts');
    Route::get('/cv/locations/villages', [CvLocationController::class, 'villages'])->name('cv.locations.villages');
});
