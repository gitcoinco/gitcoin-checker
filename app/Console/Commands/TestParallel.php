<?php

namespace App\Console\Commands;

use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Promise\Utils;
use Throwable;

class TestParallel extends Command
{
    protected $signature = 'test:parallel';
    protected $description = 'Test parallel vs sequential processing';

    public function handle()
    {
        $urls = [];
        for ($i = 1; $i <= 10; $i++) {
            $urls[] = 'https://postman-echo.com/delay/3';
        }

        $this->info('Starting sequential processing...');
        $startTime = microtime(true);
        $this->sequentialProcessing($urls);
        $endTime = microtime(true);
        $this->info("Sequential processing time: " . ($endTime - $startTime) . " seconds");

        $this->info('Starting parallel processing...');
        $startTime = microtime(true);
        $this->parallelProcessing($urls);
        $endTime = microtime(true);
        $this->info("Parallel processing time: " . ($endTime - $startTime) . " seconds");
    }

    private function sequentialProcessing($urls)
    {
        $client = new Client();

        foreach ($urls as $key => $url) {
            $this->info('Request #' . ($key + 1) . ' sent!');
            try {
                $response = $client->get($url);
                $this->info('Response received for Request #' . ($key + 1));
            } catch (Throwable $e) {
                $this->error('Error on Request #' . ($key + 1) . ': ' . $e->getMessage());
            }
        }
    }

    private function parallelProcessing($urls)
    {
        $client = Http::async();
        $client = $client->setClient(new Client([
            'handler' => $client->buildHandlerStack(),
            'cookies' => true,
        ]));


        $promises = array_map(function ($url) use ($client) {
            return $client->get($url);
        }, $urls);

        $combinedPromise = Utils::all($promises);

        $results = $combinedPromise->wait();
        return $results;
    }
}
