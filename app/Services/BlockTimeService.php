<?php

namespace App\Services;

use App\Models\BlockTime;
use App\Models\BlockTimes;
use GuzzleHttp\Client;
use App\Models\BlockTimestamp;
use App\Models\Chain;
use DateTime;
use Illuminate\Support\Facades\Cache;

class BlockTimeService
{
    public function __construct()
    {
    }

    public function getBlockTime(Chain $chain, $blockNumber)
    {
        $blockTime = BlockTime::where('chain_id', $chain->id)
            ->where('block_number', $blockNumber)
            ->first();

        if ($blockTime) {
            return $blockTime->timestamp;
        }

        // Create a client with a base URI
        $client = new Client(['base_uri' => 'https://api.etherscan.io']);

        try {
            // Send a request to Etherscan API
            $response = $client->get('/api', [
                'query' => [
                    'module' => 'block',
                    'action' => 'getblockreward',
                    'blockno' => $blockNumber, // Assuming this is the correct parameter for the block number
                    'apikey' => env('ETHERSCAN_API_KEY'),
                ]
            ]);

            // If we're making a request, add a slight delay so that we don't go over Etherscan's rate limit
            sleep(1);

            // Get the body of the response
            $body = $response->getBody();

            // Decode the JSON response
            $data = json_decode($body, true);

            $timeStamp = $data['result']['timeStamp'];

            $blockTime = BlockTime::create([
                'chain_id' => $chain->id,
                'block_number' => $blockNumber,
                'timestamp' => $timeStamp,
            ]);

            return $timeStamp;
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            echo 'Request failed: ' . $e->getMessage();
            return null;
        }
    }
}
