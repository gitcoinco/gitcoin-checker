<?php

use App\Http\Controllers\AccessControlController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\RoundController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RoundPromptController;
use App\Http\Controllers\RoundApplicationController;
use App\Http\Controllers\ChainController;
use App\Http\Controllers\UserPreferenceController;
use App\Http\Controllers\RoundApplicationUserScoreController;
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

Route::prefix('public')->group(
    function () {
        Route::get('/', function () {
            return redirect()->route('public.projects.index');
        });
        Route::get('/projects', [ProjectController::class, 'indexPublic'])->name('public.projects.index');
        Route::get('/project/show/{project}', [ProjectController::class, 'showPublic'])->name('public.project.show');

        Route::get('/round/show/{round}', [RoundController::class, 'showPublic'])->name('public.round.show');

        Route::get('/projects/sitemap.xml', [ProjectController::class, 'sitemapPublic'])->name('public.projects.sitemap');
    }
);

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


    Route::prefix('application')->group(function () {
        Route::get('/', [RoundApplicationController::class, 'index'])->name('round.application.index');
        Route::prefix('application')->group(function () {

            Route::get('/{application}', [RoundApplicationUserScoreController::class, 'index'])->name('round.application.user.score.index');
            Route::post('/{application}', [RoundApplicationUserScoreController::class, 'upsert'])->name('round.application.user.score.upsert');
            Route::delete('/{application}', [RoundApplicationUserScoreController::class, 'destroy'])->name('round.application.user.score.delete');
        });
    });

    Route::prefix('user-preferences')->group(function () {
        Route::get('/rounds', [UserPreferenceController::class, 'roundsSearch'])->name('user-preferences.rounds.search');
        Route::get('/round/toggle/{round}', [UserPreferenceController::class, 'roundToggle'])->name('user-preferences.round.toggle');
        Route::get('/rounds/selectedApplicationRoundType', [UserPreferenceController::class, 'selectedApplicationRoundType'])->name('user-preferences.rounds.selectedApplicationRoundType');
    });


    Route::prefix('chain')->group(function () {
        Route::get('/', [ChainController::class, 'index'])->name('chain.index');
        Route::post('/update-all', [ChainController::class, 'updateAll'])->name('chain.update-all');
    });


    Route::prefix('round')->group(function () {
        Route::get('/', [RoundController::class, 'index'])->name('round.index');
        Route::get('/show/{round}', [RoundController::class, 'show'])->name('round.show');
        Route::get('/prompt/{round}', [RoundPromptController::class, 'show'])->name('round.prompt.show');
        Route::post('/prompt/{round}', [RoundPromptController::class, 'upsert'])->name('round.prompt.upsert');
        Route::get('/search/{search?}', [RoundController::class, 'search'])->name('round.search');
        Route::post('/flag/{id}', [RoundController::class, 'flag']);
        Route::prefix('application')->group(function () {
            Route::get('/evaluate-all/{round}', [RoundApplicationController::class, 'evaluateAllShow'])->name('round.evaluate.all.show');
            Route::get('/evaluate/{application}', [RoundApplicationController::class, 'evaluate'])->name('round.application.evaluate');
            Route::post('/evaluate/chatgpt/{application}', [RoundApplicationController::class, 'checkAgainstChatGPT'])->name('round.application.chatgpt');
            Route::post('/evaluate/chatgpt/{application}/list', [RoundApplicationController::class, 'checkAgainstChatGPTList'])->name('round.application.chatgpt.list');
        });
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
