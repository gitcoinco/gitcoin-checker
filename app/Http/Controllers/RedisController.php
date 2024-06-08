<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Redis;

class RedisController extends Controller
{
    public function test()
    {
        try {
            // Set a test key
            Redis::set('test_key', 'test_value');

            // Retrieve the test key
            $value = Redis::get('test_key');

            return response()->json([
                'status' => 'success',
                'message' => 'Connected to Redis successfully',
                'test_key_value' => $value
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
}
