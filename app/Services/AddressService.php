<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Str;
use kornrunner\Keccak;

class AddressService
{
    public function __construct()
    {
    }

    // Get the token name from the address
    public static function getTokenFromAddress($address)
    {
        $address = self::getAddress($address);

        $tokens = [
            "0x6B175474E89094C44Da98b954EedeAC495271d0F" => "DAI",
            "0x38e4adB44ef08F22F5B5b76A8f0c2d0dCbE7DcA1" => "CVP",
            "0xDA10009cBd5D07dd0CeCc66161FC93D7c9000da1" => "DAI",
            // ... add other addresses and names here
            "0x21be370D5312f44cB42ce377BC9b8a0cEF1A4C83" => "WFTM",
            "0xC931f61B1534EB21D8c11B24f3f5Ab2471d4aB50" => "BUSD",
            "0x8d11ec38a3eb5e956b052f67da8bdc9bef8abf3e" => "DAI",
            "0x83791638da5EB2fAa432aff1c65fbA47c5D29510" => "GcV",
            "0xa7c3bf25ffea8605b516cf878b7435fe1768c89b" => "BUSD",
            "0x11fE4B6AE13d2a6055C8D9cF65c55bac32B5d844" => "DAI",
            "0xEdE59D58d9B8061Ff7D22E629AB2afa01af496f4" => "DAI",
            "0x5FbDB2315678afecb367f032d93F642f64180aa3" => "TEST",
            "0x7c6b91D9Be155A6Db01f749217d76fF02A7227F2" => "GTC",
            "0x7f9a7DB853Ca816B9A138AEe3380Ef34c437dEe0" => "GTC",
            "0x7f9a7db853ca816b9a138aee3380ef34c437dee0" => "GTC",
            "0x6C121674ba6736644A7e73A8741407fE8a5eE5BA" => "DAI",
            "0xaf88d065e77c8cc2239327c5edb3a432268e5831" => "USDC",
            "0x912CE59144191C1204E64559FE8253a0e49E6548" => "ARB",
            "0xB97EF9Ef8734C71904D8002F8b6Bc66Dd9c48a6E" => "USDC",
            "0x5425890298aed601595a70ab815c96711a31bc65" => "USDC",
            "0x3c499c542cEF5E3811e1192ce70d8cC03d5c3359" => "USDC",
            "0x9999f7Fea5938fD3b1E26A12c3f2fb024e194f97" => "USDC",
            "ethers.constants.AddressZero" => "ETH" // This should be replaced with the actual zero address if applicable
        ];

        return isset($tokens[$address]) ? $tokens[$address] : "Unknown Token";
    }

    // Get a Keccak256 hash of the address
    public static function getAddress($address)
    {
        if (Str::length($address) !== 42) {
            return $address;
        }

        // Remove any leading '0x'
        if (strpos($address, '0x') === 0) {
            $address = substr($address, 2);
        }

        // Ensure the address is 40 characters long (20 bytes)
        if (strlen($address) !== 40) {
            throw new Exception("Invalid address length");
        }

        // Convert the address to lowercase
        $address = strtolower($address);

        // Calculate the keccak256 hash of the address
        $hash = Keccak::hash($address, 256);

        // Initialize an empty checksum address
        $checksumAddress = '0x';

        // Iterate over each character in the original address
        for ($i = 0; $i < 40; $i++) {
            // If the ith bit of the hash is 1, uppercase the ith character, otherwise leave it as is
            $checksumAddress .= (hexdec($hash[$i]) >= 8)
                ? strtoupper($address[$i])
                : $address[$i];
        }

        return $checksumAddress;
    }
}
