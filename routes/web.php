<?php

use App\Http\Controllers\AccessControlController;
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

Route::post('login-web3', \App\Actions\LoginUsingWeb3::class);

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::prefix('access-control')->group(function () {
        Route::get('/', [AccessControlController::class, 'index'])->name('access-control.index');
        Route::post('/upsert', [AccessControlController::class, 'upsert'])->name('access-control.upsert');
        Route::delete('/delete/{accessControl}', [AccessControlController::class, 'destroy'])->name('access-control.delete');
    });
});
