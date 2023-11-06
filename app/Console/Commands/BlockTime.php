<?php

namespace App\Console\Commands;

use App\Models\Chain;
use App\Services\BlockTimeService;
use Illuminate\Console\Command;

class BlockTime extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:block-time';

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
        $blockTimeService = new BlockTimeService();

        $chain = Chain::where('chain_id', 421613)->first();
        $block = 41180904;


        $timestamp = $blockTimeService->getBlockTime($chain, $block);

        dd($timestamp);
    }
}
