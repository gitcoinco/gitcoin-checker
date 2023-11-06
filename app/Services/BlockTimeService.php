<?php

namespace App\Services;

use App\Models\BlockTime;
use App\Models\BlockTimes;
use GuzzleHttp\Client;
use App\Models\BlockTimestamp;
use App\Models\Chain;
use DateTime;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;


class BlockTimeService
{
    public function __construct()
    {
    }

    // Make an RPC call to get the block time
    private function getBlockTimeFromRPC($rpcEndpoint, $blockNumber)
    {
        $cacheKey = "blockTimeF:{$rpcEndpoint}:{$blockNumber}";
        $timestamp = Cache::remember($cacheKey, now()->addDay(), function () use ($rpcEndpoint, $blockNumber, $cacheKey) {
            $payload = json_encode([
                'jsonrpc' => '2.0',
                'method'  => 'eth_getBlockByNumber',
                'params'  => ['0x' . dechex($blockNumber), true],
                'id'      => 1,
            ]);

            $ch = curl_init($rpcEndpoint);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

            $response = curl_exec($ch);
            curl_close($ch);

            if (curl_errno($ch)) {
                // Clear cache
                throw new \Exception(curl_error($ch));
                Cache::forget($cacheKey);
                return null;
            } else {
                $decoded = json_decode($response, true);
                $timestamp = hexdec($decoded['result']['timestamp']);
                return $timestamp;
            }
        });

        return $timestamp;
    }

    private function getBlockTimeFromChain(Chain $chain, $blockNumber)
    {

        if (!$chain->chain_id) {
            throw new \Exception('Chain ID not set');
        }

        if (!$chain->rpc_endpoint) {
            throw new \Exception('RPC endpoint not set for chainId ' . $chain->chain_id);
        }

        return $this->getBlockTimeFromRPC($chain->rpc_endpoint, $blockNumber);
    }

    public function getBlockTime(Chain $chain, $blockNumber)
    {
        if (!$chain->chain_id) {
            return null;
        }


        $blockTime = BlockTime::where('chain_id', $chain->chain_id)
            ->where('block_number', $blockNumber)
            ->first();

        if ($blockTime) {
            return $blockTime->timestamp;
        } else {
            $currentBlockTime = $this->getBlockTimeFromChain($chain, $blockNumber);
            $previousBlockTime = $this->getBlockTimeFromChain($chain, $blockNumber - 1);

            if (!$currentBlockTime || !$previousBlockTime) {
                // Estimate by looking at 10 blocks before
                $currentBlockTime = $this->getBlockTimeFromChain($chain, $blockNumber - 10);
                $previousBlockTime = $this->getBlockTimeFromChain($chain, $blockNumber - 11);
                $diff = $currentBlockTime - $previousBlockTime;
                $currentBlockTime = $currentBlockTime + (10 * $diff);
                $previousBlockTime = $previousBlockTime + (10 * $diff);
            }

            if (!$currentBlockTime || !$previousBlockTime) {
                return null;
            }

            $blockTimeEstimate = $currentBlockTime - $previousBlockTime;

            // Add before and after based on $blockTimeEstimate to reduce calls to alchemy
            $blockTimes = [];
            for ($i = -1000; $i <= 1000; $i++) {
                // Check if the block exists
                $blockExists = BlockTime::where('chain_id', $chain->chain_id)
                    ->where('block_number', $blockNumber + $i)
                    ->exists();

                if ($blockExists) {
                    continue;
                }

                if ($i == 0) {
                    $blockTimes[] = [
                        'chain_id' => $chain->chain_id,
                        'block_number' => $blockNumber,
                        'timestamp' => $currentBlockTime,
                        'is_estimate' => false,
                    ];
                } else {
                    $blockTimes[] = [
                        'chain_id' => $chain->chain_id,
                        'block_number' => $blockNumber + $i,
                        'timestamp' => $currentBlockTime + ($i * $blockTimeEstimate),
                        'is_estimate' => true,
                    ];
                }
            }
            BlockTime::insert($blockTimes);
            return $currentBlockTime;
        }
    }
}
