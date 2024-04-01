<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        'App\Models\AccessControl' => 'App\Policies\AccessControlPolicy',
        'App\Models\RoundApplicationEvaluationAnswers' => 'App\Policies\RoundApplicationEvaluationAnswersPolicy',
        'App\Models\NotificationSetup' => 'App\Policies\NotificationSetupPolicy',
        'App\Models\Round' => 'App\Policies\RoundPolicy',
        'App\Models\RoundApplication' => 'App\Policies\RoundApplicationPolicy',
        'App\Models\User' => 'App\Policies\UserPolicy',
        'App\Models\RoundRole' => 'App\Policies\RoundRolePolicy',
    ];
    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
    }
}
