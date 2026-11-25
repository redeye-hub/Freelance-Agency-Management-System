<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ApplicationController;              // NEW
use App\Http\Controllers\Hr\CandidateController;             // NEW
use App\Http\Controllers\Hr\OffboardingController;           // NEW
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FreelancerProfileController;
use App\Http\Controllers\FreelancerSearchController;
use App\Http\Controllers\InquiryController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('welcome'))->name('home');

// Public inquiry form (visitors)
Route::get('/inquiry', [InquiryController::class, 'create'])->name('inquiry.create');
Route::post('/inquiry', [InquiryController::class, 'store'])->middleware('throttle:5,1')->name('inquiry.store');

// ---- HR (Hazrat): public freelancer application form ----            // NEW
Route::get('/join', [ApplicationController::class, 'create'])->name('apply.create');
Route::post('/join', [ApplicationController::class, 'store'])->middleware('throttle:5,1')->name('apply.store');

Route::middleware(['auth', 'verified', 'active'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Freelancer: own skills / rate / availability
    Route::middleware('role:freelancer')->group(function () {
        Route::get('/my-freelancer-profile', [FreelancerProfileController::class, 'edit'])->name('freelancer.profile.edit');
        Route::patch('/my-freelancer-profile', [FreelancerProfileController::class, 'update'])->name('freelancer.profile.update');
    });

    // Admin + Project Manager: leads, clients, freelancer search
    Route::middleware('role:super-admin|project-manager')->group(function () {
        Route::get('leads', [LeadController::class, 'index'])->name('leads.index');
        Route::get('leads/{lead}', [LeadController::class, 'show'])->name('leads.show');
        Route::patch('leads/{lead}', [LeadController::class, 'update'])->name('leads.update');
        Route::patch('leads/{lead}/stage', [LeadController::class, 'updateStage'])->name('leads.stage');
        Route::post('leads/{lead}/convert', [LeadController::class, 'convert'])->name('leads.convert');

        Route::resource('clients', ClientController::class)->except(['destroy']);

        Route::get('freelancers', [FreelancerSearchController::class, 'index'])->name('freelancers.index');
        Route::get('freelancers/{user}', [FreelancerSearchController::class, 'show'])->name('freelancers.show');
    });

    // ---- HR (Hazrat): recruitment pipeline + offboarding ----        // NEW
    Route::middleware('role:super-admin|hr')->prefix('hr')->name('hr.')->group(function () {
        Route::get('candidates', [CandidateController::class, 'index'])->name('candidates.index');
        Route::get('candidates/{candidate}', [CandidateController::class, 'show'])->name('candidates.show');
        Route::patch('candidates/{candidate}', [CandidateController::class, 'update'])->name('candidates.update');
        Route::patch('candidates/{candidate}/stage', [CandidateController::class, 'updateStage'])->name('candidates.stage');
        Route::post('candidates/{candidate}/approve', [CandidateController::class, 'approve'])->name('candidates.approve');
        Route::post('candidates/{candidate}/documents', [CandidateController::class, 'storeDocument'])->name('candidates.documents.store');
        Route::get('candidates/{candidate}/documents/{document}', [CandidateController::class, 'downloadDocument'])->name('candidates.documents.download');
        Route::delete('candidates/{candidate}/documents/{document}', [CandidateController::class, 'destroyDocument'])->name('candidates.documents.destroy');

        Route::get('offboarding', [OffboardingController::class, 'index'])->name('offboarding.index');
        Route::patch('offboarding/{user}', [OffboardingController::class, 'update'])->name('offboarding.update');
    });

    // Admin only: user management
    Route::middleware('role:super-admin')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('users', UserController::class)->except(['show', 'destroy']);
        Route::patch('users/{user}/status', [UserController::class, 'updateStatus'])->name('users.status');
        Route::post('users/{user}/invite', [UserController::class, 'resendInvite'])->name('users.invite');
    });
});

require __DIR__ . '/auth.php';