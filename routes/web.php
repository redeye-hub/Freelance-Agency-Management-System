
<?php

use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\MilestoneController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TimesheetController;
use App\Http\Controllers\TimesheetReviewController;
use App\Http\Controllers\ProjectBoardController;
use App\Http\Controllers\TaskAttachmentController;
use App\Http\Controllers\TaskCommentController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\ProjectReviewController;

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\Hr\CandidateController;
use App\Http\Controllers\Hr\OffboardingController;
use App\Http\Controllers\Hr\ContractController;
use App\Http\Controllers\Hr\LeaveReviewController;
use App\Http\Controllers\Hr\ScorecardController;
use App\Http\Controllers\Hr\WorkloadController;

use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\MyContractController;

use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FreelancerProfileController;
use App\Http\Controllers\FreelancerSearchController;
use App\Http\Controllers\InquiryController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\ProfileController;

use Illuminate\Support\Facades\Route;


// Public Routes

Route::get('/', fn () => view('welcome'))->name('home');

// Public inquiry form
Route::get('/inquiry', [InquiryController::class, 'create'])
    ->name('inquiry.create');

Route::post('/inquiry', [InquiryController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('inquiry.store');

// Public freelancer application form
Route::get('/join', [ApplicationController::class, 'create'])
    ->name('apply.create');

Route::post('/join', [ApplicationController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('apply.store');


// Authenticated Routes

Route::middleware(['auth', 'verified', 'active'])->group(function () {

    Route::get('/dashboard', DashboardController::class)
        ->name('dashboard');


    // Profile

    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');


    // Freelancer Profile

    Route::middleware('role:freelancer')->group(function () {

        Route::get('/my-freelancer-profile', [FreelancerProfileController::class, 'edit'])
            ->name('freelancer.profile.edit');

        Route::patch('/my-freelancer-profile', [FreelancerProfileController::class, 'update'])
            ->name('freelancer.profile.update');
    });


    // Leads, Clients & Freelancer Search

    Route::middleware('role:super-admin|project-manager')->group(function () {

        Route::get('leads', [LeadController::class, 'index'])
            ->name('leads.index');

        Route::get('leads/{lead}', [LeadController::class, 'show'])
            ->name('leads.show');

        Route::patch('leads/{lead}', [LeadController::class, 'update'])
            ->name('leads.update');

        Route::patch('leads/{lead}/stage', [LeadController::class, 'updateStage'])
            ->name('leads.stage');

        Route::post('leads/{lead}/convert', [LeadController::class, 'convert'])
            ->name('leads.convert');

        Route::resource('clients', ClientController::class)
            ->except(['destroy']);

        Route::get('freelancers', [FreelancerSearchController::class, 'index'])
            ->name('freelancers.index');

        Route::get('freelancers/{user}', [FreelancerSearchController::class, 'show'])
            ->name('freelancers.show');
    });


    // HR Module (Hazrat)
    // Recruitment Pipeline & Offboarding

    Route::middleware('role:super-admin|hr')
        ->prefix('hr')
        ->name('hr.')
        ->group(function () {

            // Recruitment
            Route::get('candidates', [CandidateController::class, 'index'])
                ->name('candidates.index');

            Route::get('candidates/{candidate}', [CandidateController::class, 'show'])
                ->name('candidates.show');

            Route::patch('candidates/{candidate}', [CandidateController::class, 'update'])
                ->name('candidates.update');

            Route::patch('candidates/{candidate}/stage', [CandidateController::class, 'updateStage'])
                ->name('candidates.stage');

            Route::post('candidates/{candidate}/approve', [CandidateController::class, 'approve'])
                ->name('candidates.approve');

            // Candidate documents
            Route::post('candidates/{candidate}/documents', [CandidateController::class, 'storeDocument'])
                ->name('candidates.documents.store');

            Route::get('candidates/{candidate}/documents/{document}', [CandidateController::class, 'downloadDocument'])
                ->name('candidates.documents.download');

            Route::delete('candidates/{candidate}/documents/{document}', [CandidateController::class, 'destroyDocument'])
                ->name('candidates.documents.destroy');

            // Offboarding
            Route::get('offboarding', [OffboardingController::class, 'index'])
                ->name('offboarding.index');

            Route::patch('offboarding/{user}', [OffboardingController::class, 'update'])
                ->name('offboarding.update');


            // HR Leave Management

            Route::get('leave', [LeaveReviewController::class, 'index'])
                ->name('leave.index');

            Route::patch('leave/{leave}/approve', [LeaveReviewController::class, 'approve'])
                ->name('leave.approve');

            Route::patch('leave/{leave}/reject', [LeaveReviewController::class, 'reject'])
                ->name('leave.reject');


            // HR Workload

            Route::get('workload', [WorkloadController::class, 'index'])
                ->name('workload.index');


            // HR Contracts

            Route::get('contracts', [ContractController::class, 'index'])
                ->name('contracts.index');

            Route::post('contracts', [ContractController::class, 'store'])
                ->name('contracts.store');

            Route::get('contracts/{contract}/pdf', [ContractController::class, 'pdf'])
                ->name('contracts.pdf');

            Route::delete('contracts/{contract}', [ContractController::class, 'withdraw'])
                ->name('contracts.withdraw');


            // HR Scorecards

            Route::get('scorecards', [ScorecardController::class, 'index'])
                ->name('scorecards.index');

            Route::get('scorecards/{user}', [ScorecardController::class, 'show'])
                ->name('scorecards.show');
        });


    // Module 2 (Resat)
    // Projects, Milestones, Assignments & Timesheets

    Route::middleware('role:super-admin|project-manager')->group(function () {

        Route::resource('projects', ProjectController::class)
            ->except(['destroy']);

        Route::scopeBindings()->group(function () {

            // Milestones
            Route::post('projects/{project}/milestones', [MilestoneController::class, 'store'])
                ->name('projects.milestones.store');

            Route::patch('projects/{project}/milestones/{milestone}', [MilestoneController::class, 'update'])
                ->name('projects.milestones.update');

            Route::delete('projects/{project}/milestones/{milestone}', [MilestoneController::class, 'destroy'])
                ->name('projects.milestones.destroy');

            // Assignments
            Route::post('projects/{project}/assignments', [AssignmentController::class, 'store'])
                ->name('projects.assignments.store');

            Route::patch('projects/{project}/assignments/{assignment}', [AssignmentController::class, 'update'])
                ->name('projects.assignments.update');
        });

        // Timesheet review
        Route::get('timesheets/review', [TimesheetReviewController::class, 'index'])
            ->name('timesheets.review');

        Route::patch('timesheets/{timesheet}/approve', [TimesheetReviewController::class, 'approve'])
            ->name('timesheets.approve');

        Route::patch('timesheets/{timesheet}/reject', [TimesheetReviewController::class, 'reject'])
            ->name('timesheets.reject');
    });


    // Freelancer: Own timesheets
    Route::middleware('role:freelancer')->group(function () {

        Route::get('my/timesheet', [TimesheetController::class, 'index'])
            ->name('timesheet.index');

        Route::post('my/timesheet/entries', [TimesheetController::class, 'storeEntry'])
            ->name('timesheet.entries.store');

        Route::delete('my/timesheet/entries/{entry}', [TimesheetController::class, 'destroyEntry'])
            ->name('timesheet.entries.destroy');

        Route::post('my/timesheet/submit', [TimesheetController::class, 'submit'])
            ->name('timesheet.submit');
    });


    // Module 2 (Barson)
    // Kanban Board, Tasks, Comments & Attachments

    Route::get('my/projects', [ProjectBoardController::class, 'mine'])
        ->middleware('role:freelancer')
        ->name('my.projects');

    Route::scopeBindings()->group(function () {

        // Project board
        Route::get('projects/{project}/board', [ProjectBoardController::class, 'show'])
            ->name('projects.board');

        // Tasks
        Route::post('projects/{project}/tasks', [TaskController::class, 'store'])
            ->name('projects.tasks.store');

        Route::get('projects/{project}/tasks/{task}', [TaskController::class, 'show'])
            ->name('projects.tasks.show');

        Route::patch('projects/{project}/tasks/{task}', [TaskController::class, 'update'])
            ->name('projects.tasks.update');

        Route::patch('projects/{project}/tasks/{task}/status', [TaskController::class, 'updateStatus'])
            ->name('projects.tasks.status');

        Route::delete('projects/{project}/tasks/{task}', [TaskController::class, 'destroy'])
            ->name('projects.tasks.destroy');

        // Comments
        Route::post('projects/{project}/tasks/{task}/comments', [TaskCommentController::class, 'store'])
            ->name('projects.tasks.comments.store');

        // Attachments
        Route::post('projects/{project}/tasks/{task}/attachments', [TaskAttachmentController::class, 'store'])
            ->name('projects.tasks.attachments.store');

        Route::get('projects/{project}/tasks/{task}/attachments/{attachment}', [TaskAttachmentController::class, 'download'])
            ->name('projects.tasks.attachments.download');

        Route::delete('projects/{project}/tasks/{task}/attachments/{attachment}', [TaskAttachmentController::class, 'destroy'])
            ->name('projects.tasks.attachments.destroy');
    });


    // Module 2 (Hazrat)
    // Freelancer Leave & Contracts

    Route::middleware('role:freelancer')->group(function () {

        // Leave requests
        Route::get('my/leave', [LeaveRequestController::class, 'index'])
            ->name('leave.index');

        Route::post('my/leave', [LeaveRequestController::class, 'store'])
            ->name('leave.store');

        Route::patch('my/leave/{leave}/cancel', [LeaveRequestController::class, 'cancel'])
            ->name('leave.cancel');


        // Freelancer contracts
        Route::get('my/contracts', [MyContractController::class, 'index'])
            ->name('my.contracts');

        Route::patch('my/contracts/{contract}/accept', [MyContractController::class, 'accept'])
            ->name('my.contracts.accept');

        Route::patch('my/contracts/{contract}/decline', [MyContractController::class, 'decline'])
            ->name('my.contracts.decline');

        Route::get('my/contracts/{contract}/pdf', [MyContractController::class, 'pdf'])
            ->name('my.contracts.pdf');
    });


    // Module 2 (Hazrat)
    // Manager Freelancer Project Reviews

    Route::middleware('role:super-admin|project-manager')->group(function () {

        Route::get('projects/{project}/reviews', [ProjectReviewController::class, 'edit'])
            ->name('projects.reviews.edit');

        Route::put('projects/{project}/reviews', [ProjectReviewController::class, 'update'])
            ->name('projects.reviews.update');
    });


    // Admin: User Management

    Route::middleware('role:super-admin')
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {

            Route::resource('users', UserController::class)
                ->except(['show', 'destroy']);

            Route::patch('users/{user}/status', [UserController::class, 'updateStatus'])
                ->name('users.status');

            Route::post('users/{user}/invite', [UserController::class, 'resendInvite'])
                ->name('users.invite');
        });

});


// Authentication routes
require __DIR__ . '/auth.php';
