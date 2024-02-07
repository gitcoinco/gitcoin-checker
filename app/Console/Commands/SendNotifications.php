<?php

namespace App\Console\Commands;

use App\Http\Controllers\NotificationSetupController;
use Illuminate\Console\Command;

class SendNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send Notifications';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Sending notifications...');
        $notificationController = new NotificationSetupController();
        $notificationController->sendNotifications();
        $this->info('Notifications sent!');
    }
}
