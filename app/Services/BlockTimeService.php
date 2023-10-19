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
            $response = Cache::remember('blockTime-' . $blockNumber, now()->addYears(1), function () use ($client, $blockNumber) {
                return $client->get('/api', [
                    'query' => [
                        'module' => 'block',
                        'action' => 'getblockreward',
                        'blockno' => $blockNumber, // Assuming this is the correct parameter for the block number
                        'apikey' => env('ETHERSCAN_API_KEY'),
                    ]
                ]);

                // If we're making a request, add a slight delay so that we don't go over Etherscan's rate limit
                sleep(1);
            });

            $responseStatus = $response->getStatusCode();

            if ($responseStatus !== 200) {
                return null;
            }

            // Get the body of the response
            $body = $response->getBody();

            if (!is_string($body) || !is_array(json_decode($body, true))) {
                // Find the closest block to this one and estimate the time
                $closestBlockMin = BlockTime::where('chain_id', $chain->id)
                    ->where('block_number', '<', $blockNumber)
                    ->orderBy('block_number', 'desc')
                    ->first();
                $closestBlockMax = BlockTime::where('chain_id', $chain->id)->where('block_number', '>', $blockNumber)->orderBy('block_number', 'asc')->first();

                if ($closestBlockMin && $closestBlockMax) {
                    $blockTimeMin = $closestBlockMin->timestamp;
                    $blockTimeMax = $closestBlockMax->timestamp;

                    $blockTime = BlockTime::create([
                        'chain_id' => $chain->id,
                        'block_number' => $blockNumber,
                        'timestamp' => intval($blockTimeMin + (($blockTimeMax - $blockTimeMin) / 2)),
                        'is_estimate' => true,
                    ]);
                    return $blockTime->timestamp;
                }
            }

            // Decode the JSON response
            $data = json_decode($body, true);

            if (isset($data['result']['timeStamp'])) {
                $timeStamp = $data['result']['timeStamp'];

                $blockTime = BlockTime::create([
                    'chain_id' => $chain->id,
                    'block_number' => $blockNumber,
                    'timestamp' => $timeStamp,
                ]);
                return $timeStamp;
            }
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            echo 'Request failed: ' . $e->getMessage();
            return null;
        }
    }
}
