<?php

namespace App\Console\Commands;

use App\Http\Controllers\GPTController;
use Illuminate\Console\Command;

class TestGPT extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:gpt';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing GPT...');

        $gpt = new GPTController();
        dd($gpt->models());

        $this->info('Done!');
    }
}
