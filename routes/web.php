<?php

use App\Http\Controllers\AccessControlController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\RoundController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RoundPromptController;
use App\Http\Controllers\RoundApplicationController;
use App\Http\Controllers\RoundEvaluationController;
use App\Http\Controllers\ChainController;
use App\Http\Controllers\UserPreferenceController;
use App\Http\Controllers\RoundApplicationEvaluationAnswersController;
use App\Http\Controllers\GPTController;
use App\Http\Controllers\NotificationSetupController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\RoundRoleController;
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

// Check if the user is an admin or a round operator and redirect accordingly
Route::get('/dashboard', function () {
    if (auth()->check()) {
        if (auth()->user()->is_admin) {
            return redirect()->route('admin.dashboard');
        } else if (auth()->user()->is_round_operator) {
            return redirect()->route('ro.dashboard');
        } else {
            return redirect()->route('noaccess');
        }
    }
})->name('dashboard');


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

        Route::get('/', [PublicController::class, 'home'])->name('public.projects.home');
        Route::get('/gmv', [PublicController::class, 'gmv'])->name('public.gmv');


        Route::get('/projects', [ProjectController::class, 'homePublic'])->name('public.projects.home');
        Route::get('/project/random', [ProjectController::class, 'randomProjectPublic'])->name('public.project.random');
        Route::get('/projects/list', [ProjectController::class, 'listPublic'])->name('public.projects.list');
        Route::get('/project/show/{project}', [ProjectController::class, 'showPublic'])->name('public.project.show');

        Route::get('/rounds/list', [RoundController::class, 'listPublic'])->name('public.rounds.list');
        Route::get('/round/{round}/show', [RoundController::class, 'showPublic'])->name('public.round.show');
        Route::get('/application/{application}/show', [RoundApplicationController::class, 'showPublic'])->name('public.application.show');

        Route::get('/projects/sitemap.xml', [ProjectController::class, 'sitemapPublic'])->name('public.projects.sitemap');
        Route::get('/projects/sitemap-{index}.xml', [ProjectController::class, 'sitemapIndexPublic'])->where('index', '[0-9]+')->name('public.projects.sitemap.index');

        Route::get('/github', [ProjectController::class, 'githubIndex'])->name('public.projects.github');
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

    Route::prefix('admin')->middleware('is_admin')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
        Route::get('/gpt/models', [GPTController::class, 'models'])->name('gpt.models');
        Route::get('applications', [RoundApplicationController::class, 'index'])->name('round.application.index');

        Route::prefix('chain')->group(function () {
            Route::get('/', [ChainController::class, 'index'])->name('chain.index');
            Route::post('/update-all', [ChainController::class, 'updateAll'])->name('chain.update-all');
        });
    });

    // ro = round operator
    Route::prefix('ro')->middleware('is_round_operator')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'indexRO'])->name('ro.dashboard');
        Route::prefix('application')->group(function () {
            Route::get('/{application}', [RoundApplicationController::class, 'show'])->name('application.show');
            Route::get('/set/filters', [RoundApplicationController::class, 'setFilters'])->name('round.application.set.filters');
            Route::prefix('application')->group(function () {
                Route::get('/evaluation/{application}', [RoundApplicationEvaluationAnswersController::class, 'index'])->name('round.application.user.evaluation.index');
                Route::post('/evaluation/{application}', [RoundApplicationEvaluationAnswersController::class, 'upsert'])->name('round.application.evaluation.answers.upsert');
            });
        });

        Route::get('notificationsetup', [NotificationSetupController::class, 'index'])->name('notificationsetup.index');
        Route::post('notificationsetup', [NotificationSetupController::class, 'upsert'])->name('notificationsetup.upsert');
        Route::delete('notificationsetup/{notificationSetup}', [NotificationSetupController::class, 'delete'])->name('notificationsetup.delete');

        Route::get('rounds', [RoundController::class, 'index'])->name('round.index');

        Route::prefix('round')->group(function () {
            Route::get('/{round}/roundroles', [RoundRoleController::class, 'show'])->name('round.roles.show');
            Route::post('/{round}/roundroles/upsert', [RoundRoleController::class, 'upsert'])->name('round.role.upsert');
            Route::delete('/{roundRole}/roundroles/delete', [RoundRoleController::class, 'destroy'])->name('round.role.delete');

            Route::get('/evaluation/{round}', [RoundEvaluationController::class, 'show'])->name('round.evaluation.show');
            Route::get('/evaluation/qa/{round}', [RoundEvaluationController::class, 'showQA'])->name('round.evaluation.show.qa');
            Route::post('/evaluation/qa/{round}', [RoundEvaluationController::class, 'upsert'])->name('round.evaluation.upsert');
            Route::get('/evaluation/gpt/{round}', [RoundPromptController::class, 'show'])->name('round.prompt.show');
            Route::get('/evaluation/randomapplicationresults/{round}', [RoundPromptController::class, 'getRandomApplicationPrompt'])->name('round.prompt.randomapplicationresults');
            Route::post('/evaluation/gpt/{round}', [RoundPromptController::class, 'upsert'])->name('round.prompt.upsert');
            Route::get('/evaluation/gpt/reset/{round}', [RoundPromptController::class, 'reset'])->name('round.prompt.reset');
            Route::get('/{round}/evaluation/setup', [RoundEvaluationController::class, 'setup'])->name('round.evaluation.setup');


            Route::get('/show/{round}', [RoundController::class, 'show'])->name('round.show');
            Route::get('/search/{search?}', [RoundController::class, 'search'])->name('round.search');
            Route::post('/flag/{id}', [RoundController::class, 'flag']);
            Route::prefix('application')->group(function () {
                Route::get('/{application}/details', [RoundApplicationController::class, 'details'])->name('round.application.details');
                Route::get('/evaluate-all/{round}', [RoundApplicationController::class, 'evaluateAllShow'])->name('round.evaluate.all.show');
                Route::get('/evaluate/{application}', [RoundApplicationController::class, 'evaluate'])->name('round.application.evaluate');
                Route::post('/evaluate/chatgpt/{application}', [RoundApplicationController::class, 'checkAgainstChatGPT'])->name('round.application.chatgpt');
                Route::post('/evaluate/chatgpt/{application}/list', [RoundApplicationController::class, 'checkAgainstChatGPTList'])->name('round.application.chatgpt.list');
                Route::delete('/evaluate/chatgpt/{application}/delete', [RoundApplicationController::class, 'deleteGPTResult'])->name('round.application.evaluation.results.destroy');
                Route::delete('/evaluate/evaluation-answer/{answer}/delete', [RoundApplicationEvaluationAnswersController::class, 'delete'])->name('round.application.evaluation-answer.destroy');
            });
        });

        Route::prefix('project')->group(function () {
            Route::get('/', [ProjectController::class, 'index'])->name('project.index');
            Route::get('/show/{project}', [ProjectController::class, 'show'])->name('project.show');
            Route::get('/search/{search?}', [ProjectController::class, 'search'])->name('project.search');
        });

        Route::prefix('api')->group(function () {
            Route::get('/application/{application}/show', [RoundApplicationController::class, 'apiShow'])->name('api.round.application.show');
        });

        Route::prefix('access-control')->group(function () {
            Route::get('/', [AccessControlController::class, 'index'])->name('access-control.index');
            Route::post('/upsert', [AccessControlController::class, 'upsert'])->name('access-control.upsert');
            Route::delete('/delete/{accessControl}', [AccessControlController::class, 'destroy'])->name('access-control.delete');
        });

        Route::prefix('access-control')->group(function () {
            Route::get('/stats/history', [RoundApplicationController::class, 'statsHistory'])->name('api.applications.stats.history');
        });
    });


    Route::prefix('user-preferences')->group(function () {
        Route::get('/rounds', [UserPreferenceController::class, 'roundsSearch'])->name('user-preferences.rounds.search');
        Route::get('/round/toggle/{round}', [UserPreferenceController::class, 'roundToggle'])->name('user-preferences.round.toggle');
        Route::get('/rounds/selectedApplicationRoundType', [UserPreferenceController::class, 'selectedApplicationRoundType'])->name('user-preferences.rounds.selectedApplicationRoundType');
    });
});
