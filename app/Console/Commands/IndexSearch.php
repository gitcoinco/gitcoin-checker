<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class IndexSearch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'index:search';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Index all the search models';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Indexing all search models...');
        $this->call('scout:import', ['model' => 'App\Models\Project']);
        $this->call('scout:import', ['model' => 'App\Models\Round']);
        $this->info('All done!');
    }
}
