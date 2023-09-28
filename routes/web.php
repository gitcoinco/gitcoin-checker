<?php

use App\Http\Controllers\AccessControlController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\RoundController;
use App\Http\Controllers\DashboardController;
use App\Http\Middleware\CheckAccessControl;
use App\Models\AccessControl;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

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
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/noaccess', function () {
    return Inertia::render('AccessControl/NoAccess');
})->name('noaccess');

Route::post('login-web3', \App\Actions\LoginUsingWeb3::class);

Route::middleware([
    'auth:sanctum',
    CheckAccessControl::class,
    'verified',
])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Route::get('/dashboard', function () {

    //     return Inertia::render('Dashboard', [
    //         'indexData' => env('INDEXER_URL'),
    //     ]);
    // })->name('dashboard');

    // Route::get('/dashboard', function () {
    //     return Inertia::render('Dashboard');
    // })->name('dashboard');

    Route::prefix('round')->group(function () {
        Route::get('/', [RoundController::class, 'index'])->name('round.index');
        Route::get('/show/{round}', [RoundController::class, 'show'])->name('round.show');
        Route::get('/search/{search?}', [RoundController::class, 'search'])->name('round.search');
        Route::post('/flag/{id}', [RoundController::class, 'flag']);
    });

    Route::prefix('project')->group(function () {
        Route::get('/', [ProjectController::class, 'index'])->name('project.index');
        Route::get('/show/{project}', [ProjectController::class, 'show'])->name('project.show');
        Route::get('/search/{search?}', [ProjectController::class, 'search'])->name('project.search');
    });


    Route::prefix('access-control')->group(function () {
        Route::get('/', [AccessControlController::class, 'index'])->name('access-control.index');
        Route::post('/upsert', [AccessControlController::class, 'upsert'])->name('access-control.upsert');
        Route::delete('/delete/{accessControl}', [AccessControlController::class, 'destroy'])->name('access-control.delete');
    });
});
