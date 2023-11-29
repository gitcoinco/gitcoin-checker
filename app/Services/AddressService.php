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
