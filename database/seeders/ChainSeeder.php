<?php

namespace Database\Seeders;

use App\Models\Chain;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ChainSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $chains = [
            1 => ['name' => 'Ethereum', 'rpc_endpoint' => 'https://rpc.flashbots.net'],
            10 => ['name' => 'Optimism', 'rpc_endpoint' => 'https://optimism.meowrpc.com'],
            137 => ['name' => 'Polygon', 'rpc_endpoint' => 'https://rpc-mainnet.maticvigil.com'],
            250 => ['name' => 'Fantom', 'rpc_endpoint' => 'https://rpc.ftm.tools'],
            42161 => ['name' => 'Arbitrum', 'rpc_endpoint' => 'https://arb1.arbitrum.io/rpc'],
            421613 => ['name' => 'Arbitrum Goerli', 'rpc_endpoint' => 'https://arbitrum-goerli.publicnode.com'],
            424 => ['name' => 'PGN Mainnet', 'rpc_endpoint' => 'https://rpc.publicgoods.network'],
            5 => ['name' => 'Goerli', 'rpc_endpoint' => 'https://goerli.infura.io/v3/'],
            58008 => ['name' => 'PGN Testnet', 'rpc_endpoint' => 'https://sepolia.publicgoods.network'],
            80001 => ['name' => 'Polygon Mumbai', 'rpc_endpoint' => 'https://polygon-mumbai-pokt.nodies.app'],
        ];

        foreach ($chains as $chainId => $chain) {
            $chainModel = new Chain();
            $chainModel->chain_id = $chainId;
            $chainModel->name = $chain['name'];
            $chainModel->rpc_endpoint = $chain['rpc_endpoint'];
            $chainModel->save();
        }
    }
}
