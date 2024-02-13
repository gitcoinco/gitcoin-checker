<?php

use App\Http\Controllers\RoundApplicationController;
use App\Models\RoundApplication;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private $notificationService;

    public function __construct()
    {
        $this->notificationService = app(\App\Services\NotificationService::class);
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (app()->environment('production')) {
            $applications = RoundApplication::whereDoesntHave('results')->get();
            foreach ($applications as $application) {
                echo "Checking application {$application->id} against ChatGPT" . PHP_EOL;
                $roundApplicationController = new RoundApplicationController($this->notificationService);
                $roundApplicationController->checkAgainstChatGPT($application);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
