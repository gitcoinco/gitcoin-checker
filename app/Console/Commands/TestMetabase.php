<?php

namespace App\Console\Commands;

use App\Services\MetabaseService;
use Illuminate\Console\Command;

class TestMetabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:metabase';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make a request to metabase';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $metabase = new MetabaseService();

        $alientBusterProjectAddress = '0x56bd4e84be7e1b79cfaa85dd34f591d294b5629d41a645d87591041378d0dabd';
        $chainId = 1;
        $applicationId = 35;

        // $response = $metabase->getMatchingDistribution($chainId, $alientBusterProjectAddress, $applicationId);
        // dd($response);

        $response = $metabase->getDonorAmountUSD($chainId, $alientBusterProjectAddress, $applicationId);
        dd($response);


        $this->info('Response: ' . json_encode($response));
    }
}
