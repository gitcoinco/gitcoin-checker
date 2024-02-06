<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use \App\Models\User;
use \App\Mail\TestEmail as TestEmailMailable;
use Snowfire\Beautymail\Beautymail;

class TestEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:email';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test sending an email';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Sending test email...');

        $email = $this->ask('Please enter the email to send to:');

        $beautymail = app()->make(Beautymail::class);
        $beautymail->send('emails.test', [], function ($message) {
            $message
                ->from('bar@example.com')
                ->to('foo@example.com', 'John Smith')
                ->subject('Welcome!');
        });


        // // send the TestEmail
        // \Mail::to($email)->send(new TestEmailMailable());



        $this->info('Test email sent!');
    }
}
